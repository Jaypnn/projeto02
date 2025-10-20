<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'type',
        'initial_balance',
        'current_balance',
        'currency',
        'color',
        'icon',
        'is_active',
        'include_in_total',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'include_in_total' => 'boolean',
    ];

    /**
     * Relacionamentos
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(Transaction::class, 'account_id')
            ->where('type', 'transfer');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(Transaction::class, 'transfer_account_id')
            ->where('type', 'transfer');
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeIncludeInTotal(Builder $query): Builder
    {
        return $query->where('include_in_total', true);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Métodos auxiliares
     */
    public function isCreditCard(): bool
    {
        return $this->type === 'credit_card';
    }

    public function isCheckingAccount(): bool
    {
        return $this->type === 'checking';
    }

    public function isSavingsAccount(): bool
    {
        return $this->type === 'savings';
    }

    /**
     * Calcula o saldo atual baseado nas transações
     */
    public function calculateCurrentBalance(): float
    {
        $income = $this->transactions()
            ->where('type', 'income')
            ->where('status', 'completed')
            ->sum('amount');

        $expenses = $this->transactions()
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->sum('amount');

        $transfersIn = $this->transfersTo()
            ->where('status', 'completed')
            ->sum('amount');

        $transfersOut = $this->transfersFrom()
            ->where('status', 'completed')
            ->sum('amount');

        return (float) ($this->initial_balance + $income - $expenses + $transfersIn - $transfersOut);
    }

    /**
     * Atualiza o saldo atual
     */
    public function updateCurrentBalance(): void
    {
        $this->current_balance = $this->calculateCurrentBalance();
        $this->save();
    }

    /**
     * Realiza uma transferência para outra conta
     */
    public function transferTo(Account $destinationAccount, float $amount, string $description = null): array
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be positive');
        }

        if ($this->id === $destinationAccount->id) {
            throw new \InvalidArgumentException('Cannot transfer to the same account');
        }

        return DB::transaction(function () use ($destinationAccount, $amount, $description) {
            // Transação de saída (conta origem)
            $outTransaction = $this->transactions()->create([
                'user_id' => $this->user_id,
                'category_id' => null,
                'description' => $description ?? "Transfer to {$destinationAccount->name}",
                'amount' => $amount,
                'type' => 'transfer',
                'transaction_date' => now()->format('Y-m-d'),
                'status' => 'completed',
                'transfer_account_id' => $destinationAccount->id,
            ]);

            // Transação de entrada (conta destino)
            $inTransaction = $destinationAccount->transactions()->create([
                'user_id' => $destinationAccount->user_id,
                'category_id' => null,
                'description' => $description ?? "Transfer from {$this->name}",
                'amount' => $amount,
                'type' => 'transfer',
                'transaction_date' => now()->format('Y-m-d'),
                'status' => 'completed',
                'transfer_account_id' => $this->id,
                'transfer_transaction_id' => $outTransaction->id,
            ]);

            // Vincula as transações
            $outTransaction->update(['transfer_transaction_id' => $inTransaction->id]);

            // Atualiza os saldos
            $this->updateCurrentBalance();
            $destinationAccount->updateCurrentBalance();

            return [
                'out_transaction' => $outTransaction,
                'in_transaction' => $inTransaction,
            ];
        });
    }

    /**
     * Obtém o histórico de transações com filtros
     */
    public function getTransactionHistory(array $filters = []): Builder
    {
        $query = $this->transactions()
            ->with(['category'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        if (isset($filters['start_date'])) {
            $query->where('transaction_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('transaction_date', '<=', $filters['end_date']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * Obtém estatísticas da conta
     */
    public function getStats(string $startDate = null, string $endDate = null): array
    {
        $query = $this->transactions()->where('status', 'completed');

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        $income = (clone $query)->where('type', 'income')->sum('amount');
        $expenses = (clone $query)->where('type', 'expense')->sum('amount');

        return [
            'current_balance' => $this->current_balance,
            'total_income' => (float) $income,
            'total_expenses' => (float) $expenses,
            'net_flow' => (float) ($income - $expenses),
            'transaction_count' => $query->count(),
        ];
    }
}