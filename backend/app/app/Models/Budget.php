<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'total_amount',
        'start_date',
        'end_date',
        'period_type',
        'is_active',
        'spent_amount',
        'remaining_amount',
        'settings',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Relacionamentos
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        $now = now();
        return $query->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
    }

    public function scopeByPeriodType(Builder $query, string $periodType): Builder
    {
        return $query->where('period_type', $periodType);
    }

    public function scopeInDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($qq) use ($startDate, $endDate) {
                  $qq->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Métodos auxiliares
     */
    public function isCurrent(): bool
    {
        $now = now();
        return $now->between($this->start_date, $this->end_date);
    }

    public function isExpired(): bool
    {
        return now()->isAfter($this->end_date);
    }

    public function isUpcoming(): bool
    {
        return now()->isBefore($this->start_date);
    }

    /**
     * Calcula o total gasto baseado nas transações das categorias
     */
    public function calculateSpentAmount(): float
    {
        $total = 0;

        foreach ($this->budgetCategories as $budgetCategory) {
            $categoryTransactions = Transaction::where('category_id', $budgetCategory->category_id)
                ->where('type', 'expense')
                ->where('status', 'completed')
                ->whereBetween('transaction_date', [$this->start_date, $this->end_date])
                ->sum('amount');

            $total += (float) $categoryTransactions;
        }

        return $total;
    }

    /**
     * Atualiza os valores calculados do orçamento
     */
    public function updateCalculatedAmounts(): void
    {
        $this->spent_amount = $this->calculateSpentAmount();
        $this->remaining_amount = $this->total_amount - $this->spent_amount;
        $this->save();

        // Atualiza também as categorias do orçamento
        foreach ($this->budgetCategories as $budgetCategory) {
            $budgetCategory->updateCalculatedAmounts();
        }
    }

    /**
     * Obtém a porcentagem gasta do orçamento
     */
    public function getSpentPercentage(): float
    {
        if ($this->total_amount <= 0) {
            return 0;
        }

        return ($this->spent_amount / $this->total_amount) * 100;
    }

    /**
     * Verifica se o orçamento foi excedido
     */
    public function isOverBudget(): bool
    {
        return $this->spent_amount > $this->total_amount;
    }

    /**
     * Calcula quantos dias restam no período do orçamento
     */
    public function getDaysRemaining(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        if ($this->isUpcoming()) {
            return now()->diffInDays($this->start_date);
        }

        return now()->diffInDays($this->end_date);
    }

    /**
     * Calcula a média diária que pode ser gasta
     */
    public function getDailyAverageAllowance(): float
    {
        $daysRemaining = $this->getDaysRemaining();
        
        if ($daysRemaining <= 0) {
            return 0;
        }

        return $this->remaining_amount / $daysRemaining;
    }

    /**
     * Obtém estatísticas do orçamento
     */
    public function getStats(): array
    {
        $this->updateCalculatedAmounts();

        return [
            'total_amount' => $this->total_amount,
            'spent_amount' => $this->spent_amount,
            'remaining_amount' => $this->remaining_amount,
            'spent_percentage' => $this->getSpentPercentage(),
            'is_over_budget' => $this->isOverBudget(),
            'days_remaining' => $this->getDaysRemaining(),
            'daily_average_allowance' => $this->getDailyAverageAllowance(),
            'categories_count' => $this->budgetCategories->count(),
        ];
    }

    /**
     * Cria categorias padrão para o orçamento baseadas nas categorias do usuário
     */
    public function createDefaultCategories(array $categoryIds = null): void
    {
        $categories = $categoryIds 
            ? Category::whereIn('id', $categoryIds)->where('user_id', $this->user_id)->get()
            : Category::where('user_id', $this->user_id)->where('type', 'expense')->active()->get();

        foreach ($categories as $category) {
            $this->budgetCategories()->firstOrCreate([
                'category_id' => $category->id,
            ], [
                'allocated_amount' => 0,
            ]);
        }
    }

    /**
     * Distribui o valor total proporcionalmente entre as categorias
     */
    public function distributeAmountProportionally(): void
    {
        $totalCategories = $this->budgetCategories()->count();
        
        if ($totalCategories === 0) {
            return;
        }

        $amountPerCategory = $this->total_amount / $totalCategories;

        $this->budgetCategories()->update([
            'allocated_amount' => $amountPerCategory
        ]);
    }

    /**
     * Verifica se o orçamento precisa de alerta
     */
    public function needsAlert(): bool
    {
        $settings = $this->settings ?? [];
        $alertThreshold = $settings['alert_threshold'] ?? 80; // 80% por padrão

        return $this->getSpentPercentage() >= $alertThreshold;
    }

    /**
     * Gera o próximo orçamento baseado neste
     */
    public function generateNext(): Budget
    {
        $nextStartDate = $this->getNextPeriodStartDate();
        $nextEndDate = $this->getNextPeriodEndDate($nextStartDate);

        $newBudget = static::create([
            'user_id' => $this->user_id,
            'name' => $this->name . ' (' . $nextStartDate->format('M Y') . ')',
            'description' => $this->description,
            'total_amount' => $this->total_amount,
            'start_date' => $nextStartDate,
            'end_date' => $nextEndDate,
            'period_type' => $this->period_type,
            'is_active' => true,
            'settings' => $this->settings,
        ]);

        // Copia as categorias do orçamento atual
        foreach ($this->budgetCategories as $budgetCategory) {
            $newBudget->budgetCategories()->create([
                'category_id' => $budgetCategory->category_id,
                'allocated_amount' => $budgetCategory->allocated_amount,
            ]);
        }

        return $newBudget;
    }

    /**
     * Calcula a data de início do próximo período
     */
    private function getNextPeriodStartDate(): Carbon
    {
        return match($this->period_type) {
            'weekly' => $this->end_date->addDay(),
            'monthly' => $this->start_date->addMonth(),
            'quarterly' => $this->start_date->addMonths(3),
            'yearly' => $this->start_date->addYear(),
            default => $this->end_date->addDay(),
        };
    }

    /**
     * Calcula a data de fim do próximo período
     */
    private function getNextPeriodEndDate(Carbon $startDate): Carbon
    {
        return match($this->period_type) {
            'weekly' => $startDate->copy()->addWeek()->subDay(),
            'monthly' => $startDate->copy()->endOfMonth(),
            'quarterly' => $startDate->copy()->addMonths(3)->subDay(),
            'yearly' => $startDate->copy()->endOfYear(),
            default => $startDate->copy()->addMonth()->subDay(),
        };
    }
}