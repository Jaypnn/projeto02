<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class FinancialGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'description',
        'target_amount',
        'current_amount',
        'target_date',
        'priority',
        'status',
        'goal_type',
        'monthly_contribution',
        'notes',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'monthly_contribution' => 'decimal:2',
        'target_date' => 'date',
    ];

    /**
     * Relacionamentos
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopePaused(Builder $query): Builder
    {
        return $query->where('status', 'paused');
    }

    public function scopeCanceled(Builder $query): Builder
    {
        return $query->where('status', 'canceled');
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('goal_type', $type);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('target_date', '<', now())
            ->where('status', '!=', 'completed');
    }

    public function scopeUpcoming(Builder $query, int $days = 30): Builder
    {
        return $query->whereBetween('target_date', [
            now(),
            now()->addDays($days)
        ])->where('status', 'active');
    }

    /**
     * Métodos auxiliares
     */
    public function isCompleted(): bool
    {
        return $this->current_amount >= $this->target_amount;
    }

    public function isOverdue(): bool
    {
        return $this->target_date < now() && !$this->isCompleted();
    }

    /**
     * Calcula o progresso em porcentagem
     */
    public function getProgressPercentage(): float
    {
        if ($this->target_amount <= 0) {
            return 0;
        }

        return min(100, ($this->current_amount / $this->target_amount) * 100);
    }

    /**
     * Calcula o valor restante para atingir a meta
     */
    public function getRemainingAmount(): float
    {
        return max(0, $this->target_amount - $this->current_amount);
    }

    /**
     * Calcula quantos dias restam para a meta
     */
    public function getDaysRemaining(): int
    {
        return max(0, now()->diffInDays($this->target_date, false));
    }

    /**
     * Calcula quantos meses restam para a meta
     */
    public function getMonthsRemaining(): float
    {
        return max(0, now()->diffInMonths($this->target_date, false));
    }

    /**
     * Calcula quanto precisa economizar por mês para atingir a meta
     */
    public function getRequiredMonthlySavings(): float
    {
        $monthsRemaining = $this->getMonthsRemaining();
        
        if ($monthsRemaining <= 0) {
            return $this->getRemainingAmount();
        }

        return $this->getRemainingAmount() / $monthsRemaining;
    }

    /**
     * Calcula quanto precisa economizar por dia para atingir a meta
     */
    public function getRequiredDailySavings(): float
    {
        $daysRemaining = $this->getDaysRemaining();
        
        if ($daysRemaining <= 0) {
            return $this->getRemainingAmount();
        }

        return $this->getRemainingAmount() / $daysRemaining;
    }

    /**
     * Verifica se está no caminho certo baseado na contribuição mensal
     */
    public function isOnTrack(): bool
    {
        if (!$this->monthly_contribution) {
            return false;
        }

        $monthsElapsed = now()->diffInMonths($this->created_at);
        $expectedAmount = $this->monthly_contribution * $monthsElapsed;
        
        return $this->current_amount >= $expectedAmount * 0.9; // 10% de margem
    }

    /**
     * Atualiza o valor atual baseado nas transações
     */
    public function updateCurrentAmount(): void
    {
        if (!$this->category_id) {
            return;
        }

        // Soma todas as receitas da categoria desde a criação da meta
        $totalIncome = Transaction::where('category_id', $this->category_id)
            ->where('user_id', $this->user_id)
            ->where('type', 'income')
            ->where('status', 'completed')
            ->where('transaction_date', '>=', $this->created_at)
            ->sum('amount');

        $this->current_amount = $totalIncome;

        // Atualiza o status se completou a meta
        if ($this->isCompleted() && $this->status === 'active') {
            $this->status = 'completed';
        }

        $this->save();
    }

    /**
     * Adiciona contribuição para a meta
     */
    public function addContribution(float $amount, string $description = null): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $this->current_amount += $amount;

        // Verifica se completou a meta
        if ($this->isCompleted() && $this->status === 'active') {
            $this->status = 'completed';
        }

        return $this->save();
    }

    /**
     * Obtém o status da meta
     */
    public function getGoalStatus(): string
    {
        if ($this->status === 'completed') {
            return 'completed';
        }

        if ($this->isOverdue()) {
            return 'overdue';
        }

        if ($this->status === 'paused') {
            return 'paused';
        }

        if ($this->status === 'canceled') {
            return 'canceled';
        }

        $progress = $this->getProgressPercentage();

        if ($progress >= 90) {
            return 'nearly_complete';
        } elseif ($progress >= 50) {
            return 'on_track';
        } elseif ($this->isOnTrack()) {
            return 'on_track';
        } else {
            return 'behind_schedule';
        }
    }

    /**
     * Obtém estatísticas da meta
     */
    public function getStats(): array
    {
        $this->updateCurrentAmount();

        return [
            'target_amount' => $this->target_amount,
            'current_amount' => $this->current_amount,
            'remaining_amount' => $this->getRemainingAmount(),
            'progress_percentage' => $this->getProgressPercentage(),
            'days_remaining' => $this->getDaysRemaining(),
            'months_remaining' => $this->getMonthsRemaining(),
            'required_monthly_savings' => $this->getRequiredMonthlySavings(),
            'required_daily_savings' => $this->getRequiredDailySavings(),
            'is_on_track' => $this->isOnTrack(),
            'is_completed' => $this->isCompleted(),
            'is_overdue' => $this->isOverdue(),
            'status' => $this->getGoalStatus(),
        ];
    }

    /**
     * Calcula a projeção de quando a meta será atingida
     */
    public function getProjectedCompletionDate(): ?Carbon
    {
        if (!$this->monthly_contribution || $this->monthly_contribution <= 0) {
            return null;
        }

        $remainingAmount = $this->getRemainingAmount();
        $monthsNeeded = ceil($remainingAmount / $this->monthly_contribution);
        
        return now()->addMonths($monthsNeeded);
    }

    /**
     * Verifica se precisa ajustar a contribuição mensal
     */
    public function needsAdjustment(): bool
    {
        if (!$this->monthly_contribution) {
            return true;
        }

        $requiredMonthly = $this->getRequiredMonthlySavings();
        
        // Precisa ajustar se a contribuição atual é 20% menor que o necessário
        return $this->monthly_contribution < ($requiredMonthly * 0.8);
    }

    /**
     * Sugere uma nova contribuição mensal
     */
    public function getSuggestedMonthlyContribution(): float
    {
        $required = $this->getRequiredMonthlySavings();
        
        // Adiciona 10% de margem de segurança
        return $required * 1.1;
    }

    /**
     * Obtém as transações relacionadas à meta
     */
    public function getTransactions()
    {
        if (!$this->category_id) {
            return collect();
        }

        return Transaction::where('category_id', $this->category_id)
            ->where('user_id', $this->user_id)
            ->where('type', 'income')
            ->where('transaction_date', '>=', $this->created_at)
            ->with(['account'])
            ->orderBy('transaction_date', 'desc')
            ->get();
    }

    /**
     * Cria marcos intermediários para a meta
     */
    public function getMilestones(): array
    {
        $milestones = [];
        $quarters = [25, 50, 75, 100];
        
        foreach ($quarters as $percentage) {
            $amount = ($this->target_amount * $percentage) / 100;
            $achieved = $this->current_amount >= $amount;
            
            $milestones[] = [
                'percentage' => $percentage,
                'amount' => $amount,
                'achieved' => $achieved,
                'formatted_amount' => 'R$ ' . number_format($amount, 2, ',', '.'),
            ];
        }
        
        return $milestones;
    }

    /**
     * Formata valores para exibição
     */
    public function getFormattedTargetAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->target_amount, 2, ',', '.');
    }

    public function getFormattedCurrentAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->current_amount, 2, ',', '.');
    }

    public function getFormattedRemainingAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->getRemainingAmount(), 2, ',', '.');
    }

    public function getFormattedMonthlyContributionAttribute(): string
    {
        return 'R$ ' . number_format($this->monthly_contribution, 2, ',', '.');
    }
}