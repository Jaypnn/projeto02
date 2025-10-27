<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class BudgetCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'category_id',
        'allocated_amount',
        'spent_amount',
        'remaining_amount',
        'notes',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    /**
     * Relacionamentos
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scopes
     */
    public function scopeForBudget(Builder $query, int $budgetId): Builder
    {
        return $query->where('budget_id', $budgetId);
    }

    public function scopeForCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeOverBudget(Builder $query): Builder
    {
        return $query->whereColumn('spent_amount', '>', 'allocated_amount');
    }

    public function scopeUnderBudget(Builder $query): Builder
    {
        return $query->whereColumn('spent_amount', '<', 'allocated_amount');
    }

    /**
     * Métodos auxiliares
     */
    public function isOverBudget(): bool
    {
        return $this->spent_amount > $this->allocated_amount;
    }

    public function isUnderBudget(): bool
    {
        return $this->spent_amount < $this->allocated_amount;
    }

    public function isOnBudget(): bool
    {
        return abs($this->spent_amount - $this->allocated_amount) < 0.01; // Considerando precisão decimal
    }

    /**
     * Calcula o valor gasto baseado nas transações
     */
    public function calculateSpentAmount(): float
    {
        if (!$this->budget || !$this->category) {
            return 0;
        }

        return (float) Transaction::where('category_id', $this->category_id)
            ->where('user_id', $this->budget->user_id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [
                $this->budget->start_date,
                $this->budget->end_date
            ])
            ->sum('amount');
    }

    /**
     * Atualiza os valores calculados
     */
    public function updateCalculatedAmounts(): void
    {
        $this->spent_amount = $this->calculateSpentAmount();
        $this->remaining_amount = $this->allocated_amount - $this->spent_amount;
        $this->save();
    }

    /**
     * Obtém a porcentagem gasta
     */
    public function getSpentPercentage(): float
    {
        if ($this->allocated_amount <= 0) {
            return 0;
        }

        return ($this->spent_amount / $this->allocated_amount) * 100;
    }

    /**
     * Obtém a porcentagem restante
     */
    public function getRemainingPercentage(): float
    {
        return max(0, 100 - $this->getSpentPercentage());
    }

    /**
     * Calcula quanto pode ser gasto por dia até o fim do orçamento
     */
    public function getDailyAllowance(): float
    {
        if (!$this->budget) {
            return 0;
        }

        $daysRemaining = $this->budget->getDaysRemaining();
        
        if ($daysRemaining <= 0) {
            return 0;
        }

        return max(0, $this->remaining_amount / $daysRemaining);
    }

    /**
     * Verifica se está próximo do limite (padrão 90%)
     */
    public function isNearLimit(float $threshold = 90): bool
    {
        return $this->getSpentPercentage() >= $threshold;
    }

    /**
     * Obtém o status da categoria no orçamento
     */
    public function getStatus(): string
    {
        $percentage = $this->getSpentPercentage();

        if ($percentage >= 100) {
            return 'over_budget';
        } elseif ($percentage >= 90) {
            return 'near_limit';
        } elseif ($percentage >= 75) {
            return 'on_track_high';
        } elseif ($percentage >= 25) {
            return 'on_track';
        } else {
            return 'under_utilized';
        }
    }

    /**
     * Obtém estatísticas da categoria no orçamento
     */
    public function getStats(): array
    {
        $this->updateCalculatedAmounts();

        return [
            'allocated_amount' => $this->allocated_amount,
            'spent_amount' => $this->spent_amount,
            'remaining_amount' => $this->remaining_amount,
            'spent_percentage' => $this->getSpentPercentage(),
            'remaining_percentage' => $this->getRemainingPercentage(),
            'daily_allowance' => $this->getDailyAllowance(),
            'status' => $this->getStatus(),
            'is_over_budget' => $this->isOverBudget(),
            'is_near_limit' => $this->isNearLimit(),
        ];
    }

    /**
     * Obtém as transações da categoria dentro do período do orçamento
     */
    public function getTransactions()
    {
        if (!$this->budget) {
            return collect();
        }

        return Transaction::where('category_id', $this->category_id)
            ->where('user_id', $this->budget->user_id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [
                $this->budget->start_date,
                $this->budget->end_date
            ])
            ->with(['account'])
            ->orderBy('transaction_date', 'desc')
            ->get();
    }

    /**
     * Ajusta o valor alocado baseado no histórico de gastos
     */
    public function suggestAllocation(): float
    {
        if (!$this->category || !$this->budget) {
            return $this->allocated_amount;
        }

        // Calcula a média dos últimos 3 meses na mesma categoria
        $historicalAverage = Transaction::where('category_id', $this->category_id)
            ->where('user_id', $this->budget->user_id)
            ->where('type', 'expense')
            ->where('transaction_date', '>=', now()->subMonths(3))
            ->avg('amount');

        if (!$historicalAverage) {
            return $this->allocated_amount;
        }

        // Ajusta baseado no tipo de período do orçamento
        $multiplier = match($this->budget->period_type) {
            'weekly' => 0.25, // ~1 semana por mês
            'monthly' => 1,
            'quarterly' => 3,
            'yearly' => 12,
            default => 1,
        };

        return (float) $historicalAverage * $multiplier;
    }

    /**
     * Cria alerta se necessário
     */
    public function needsAlert(): bool
    {
        return $this->isOverBudget() || $this->isNearLimit();
    }

    /**
     * Formata o valor para exibição
     */
    public function getFormattedAllocatedAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->allocated_amount, 2, ',', '.');
    }

    public function getFormattedSpentAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->spent_amount, 2, ',', '.');
    }

    public function getFormattedRemainingAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->remaining_amount, 2, ',', '.');
    }
}