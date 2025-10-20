<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'type',
        'parent_id',
        'color',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relacionamentos
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgetCategories(): HasMany
    {
        return $this->hasMany(BudgetCategory::class);
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
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

    /**
     * Calcula o total de transações desta categoria em um período
     */
    public function getTotalTransactions(string $startDate = null, string $endDate = null): float
    {
        $query = $this->transactions()->where('status', 'completed');
        
        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }
        
        return (float) $query->sum('amount');
    }

    /**
     * Calcula o total gasto desta categoria no mês atual
     */
    public function getCurrentMonthTotal(): float
    {
        return $this->getTotalTransactions(
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d')
        );
    }

    /**
     * Verifica se a categoria está sendo usada em transações
     */
    public function hasTransactions(): bool
    {
        return $this->transactions()->exists();
    }

    /**
     * Obtém as estatísticas da categoria
     */
    public function getStats(): array
    {
        return [
            'total_transactions' => $this->transactions()->count(),
            'total_amount' => $this->getTotalTransactions(),
            'current_month_amount' => $this->getCurrentMonthTotal(),
            'avg_transaction_amount' => $this->transactions()->avg('amount') ?? 0,
        ];
    }
}