<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_id',
        'category_id',
        'description',
        'notes',
        'amount',
        'type',
        'transaction_date',
        'status',
        // Compat: alguns controladores usam destination_account_id
        'destination_account_id',
        // legado/compat
        'transfer_account_id',
        'transfer_transaction_id',
        'reference',
        'tags',
        'is_recurring',
        'recurring_type',
        'recurring_interval',
        'recurring_until',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'recurring_until' => 'date',
        'tags' => 'array',
        'is_recurring' => 'boolean',
    ];

    /**
     * Relacionamentos
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transferAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'transfer_account_id');
    }

    public function transferTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transfer_transaction_id');
    }

    /**
     * Alias/novo nome usado pelo TransactionController
     */
    public function destinationAccount(): BelongsTo
    {
        // Banco padronizado para destination_account_id
        return $this->belongsTo(Account::class, 'destination_account_id');
    }

    /**
     * Scopes
     */
    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    public function scopeTransfer(Builder $query): Builder
    {
        return $query->where('type', 'transfer');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeForCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeInDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeCurrentMonth(Builder $query): Builder
    {
        return $query->whereBetween('transaction_date', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }

    public function scopeCurrentYear(Builder $query): Builder
    {
        return $query->whereBetween('transaction_date', [
            now()->startOfYear(),
            now()->endOfYear()
        ]);
    }

    public function scopeRecurring(Builder $query): Builder
    {
        return $query->where('is_recurring', true);
    }

    public function scopeWithTags(Builder $query, array $tags): Builder
    {
        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    /**
     * Métodos auxiliares
     */
    public function isIncome(): bool
    {
        return $this->type === 'income';
    }

    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }

    public function isTransfer(): bool
    {
        return $this->type === 'transfer';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Marca a transação como concluída e atualiza o saldo da conta
     */
    public function markAsCompleted(): void
    {
        if ($this->status !== 'completed') {
            $this->status = 'completed';
            $this->save();
            
            // Atualiza o saldo da conta
            $this->account->updateCurrentBalance();
            
            // Se for transferência, atualiza também a conta de destino
            if ($this->isTransfer() && ($this->destinationAccount || $this->transferAccount)) {
                ($this->destinationAccount ?? $this->transferAccount)->updateCurrentBalance();
            }
        }
    }

    /**
     * Cancela a transação
     */
    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->save();
        
        // Atualiza o saldo da conta
        $this->account->updateCurrentBalance();
        
        // Se for transferência, cancela também a transação vinculada
        if ($this->isTransfer() && $this->transferTransaction) {
            $this->transferTransaction->update(['status' => 'cancelled']);
            if ($this->destinationAccount || $this->transferAccount) {
                ($this->destinationAccount ?? $this->transferAccount)->updateCurrentBalance();
            }
        }
    }

    /**
     * Obtém o valor formatado
     */
    public function getFormattedAmountAttribute(): string
    {
        $currency = $this->account->currency ?? 'BRL';
        return number_format($this->amount, 2, ',', '.') . ' ' . $currency;
    }

    /**
     * Verifica se a transação deve se repetir
     */
    public function shouldRecur(): bool
    {
        if (!$this->is_recurring || !$this->recurring_type) {
            return false;
        }

        if ($this->recurring_until && now()->isAfter($this->recurring_until)) {
            return false;
        }

        return true;
    }

    /**
     * Calcula a próxima data de recorrência
     */
    public function getNextRecurringDate(): ?Carbon
    {
        if (!$this->shouldRecur()) {
            return null;
        }

        $interval = $this->recurring_interval ?? 1;
        $lastDate = Carbon::parse($this->transaction_date);

        return match($this->recurring_type) {
            'daily' => $lastDate->addDays($interval),
            'weekly' => $lastDate->addWeeks($interval),
            'monthly' => $lastDate->addMonths($interval),
            'yearly' => $lastDate->addYears($interval),
            default => null,
        };
    }

    /**
     * Cria a próxima transação recorrente
     */
    public function createNextRecurrence(): ?Transaction
    {
        $nextDate = $this->getNextRecurringDate();
        
        if (!$nextDate) {
            return null;
        }

        return static::create([
            'user_id' => $this->user_id,
            'account_id' => $this->account_id,
            'category_id' => $this->category_id,
            'description' => $this->description,
            'notes' => $this->notes,
            'amount' => $this->amount,
            'type' => $this->type,
            'transaction_date' => $nextDate,
            'status' => 'pending',
            'reference' => $this->reference,
            'tags' => $this->tags,
            'is_recurring' => true,
            'recurring_type' => $this->recurring_type,
            'recurring_interval' => $this->recurring_interval,
            'recurring_until' => $this->recurring_until,
        ]);
    }

    /**
     * Adiciona uma tag à transação
     */
    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->tags = $tags;
            $this->save();
        }
    }

    /**
     * Remove uma tag da transação
     */
    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        $tags = array_values(array_filter($tags, fn($t) => $t !== $tag));
        
        $this->tags = $tags;
        $this->save();
    }
}