<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Budget>
 */
class BudgetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodType = fake()->randomElement(['weekly', 'monthly', 'quarterly', 'yearly']);
        $dates = $this->getDatesByPeriodType($periodType);
        
        return [
            'user_id' => User::factory(),
            'name' => $this->getNameByPeriodType($periodType),
            'description' => fake()->optional(0.6)->sentence(),
            'total_amount' => fake()->randomFloat(2, 1000, 10000),
            'spent_amount' => function (array $attributes) {
                // Valor gasto geralmente menor que o total
                return fake()->randomFloat(2, 0, $attributes['total_amount'] * 0.9);
            },
            'remaining_amount' => function (array $attributes) {
                return $attributes['total_amount'] - $attributes['spent_amount'];
            },
            'period_type' => $periodType,
            'start_date' => $dates['start_date'],
            'end_date' => $dates['end_date'],
            'status' => fake()->randomElement(['draft', 'active', 'completed', 'paused']),
            'alert_percentage' => fake()->randomElement([75, 80, 85, 90]),
            'notes' => fake()->optional(0.4)->paragraph(),
        ];
    }

    /**
     * Obtém datas baseadas no tipo de período
     */
    private function getDatesByPeriodType(string $periodType): array
    {
        $startDate = fake()->dateTimeBetween('-3 months', 'now');
        
        $endDate = match($periodType) {
            'weekly' => (clone $startDate)->modify('+1 week'),
            'monthly' => (clone $startDate)->modify('+1 month'),
            'quarterly' => (clone $startDate)->modify('+3 months'),
            'yearly' => (clone $startDate)->modify('+1 year'),
        };

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    /**
     * Obtém nome baseado no tipo de período
     */
    private function getNameByPeriodType(string $periodType): string
    {
        $names = [
            'weekly' => [
                'Orçamento Semanal',
                'Gastos da Semana',
                'Planejamento Semanal',
            ],
            'monthly' => [
                'Orçamento Mensal',
                'Planejamento do Mês',
                'Gastos Mensais',
                'Orçamento Familiar',
            ],
            'quarterly' => [
                'Orçamento Trimestral',
                'Planejamento Trimestre',
                'Gastos do Trimestre',
            ],
            'yearly' => [
                'Orçamento Anual',
                'Planejamento do Ano',
                'Gastos Anuais',
            ],
        ];

        return fake()->randomElement($names[$periodType]);
    }

    /**
     * Orçamento semanal
     */
    public function weekly(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = fake()->dateTimeBetween('-4 weeks', 'now');
            return [
                'period_type' => 'weekly',
                'name' => fake()->randomElement([
                    'Orçamento Semanal',
                    'Gastos da Semana',
                    'Planejamento Semanal',
                ]),
                'total_amount' => fake()->randomFloat(2, 200, 1000),
                'start_date' => $startDate,
                'end_date' => (clone $startDate)->modify('+1 week'),
            ];
        });
    }

    /**
     * Orçamento mensal
     */
    public function monthly(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = fake()->dateTimeBetween('-6 months', 'now');
            return [
                'period_type' => 'monthly',
                'name' => fake()->randomElement([
                    'Orçamento Mensal',
                    'Planejamento do Mês',
                    'Gastos Mensais',
                    'Orçamento Familiar',
                ]),
                'total_amount' => fake()->randomFloat(2, 1000, 8000),
                'start_date' => $startDate,
                'end_date' => (clone $startDate)->modify('+1 month'),
            ];
        });
    }

    /**
     * Orçamento trimestral
     */
    public function quarterly(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = fake()->dateTimeBetween('-1 year', 'now');
            return [
                'period_type' => 'quarterly',
                'name' => fake()->randomElement([
                    'Orçamento Trimestral',
                    'Planejamento Trimestre',
                    'Gastos do Trimestre',
                ]),
                'total_amount' => fake()->randomFloat(2, 5000, 25000),
                'start_date' => $startDate,
                'end_date' => (clone $startDate)->modify('+3 months'),
            ];
        });
    }

    /**
     * Orçamento anual
     */
    public function yearly(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = fake()->dateTimeBetween('-2 years', 'now');
            return [
                'period_type' => 'yearly',
                'name' => fake()->randomElement([
                    'Orçamento Anual',
                    'Planejamento do Ano',
                    'Gastos Anuais',
                ]),
                'total_amount' => fake()->randomFloat(2, 20000, 100000),
                'start_date' => $startDate,
                'end_date' => (clone $startDate)->modify('+1 year'),
            ];
        });
    }

    /**
     * Orçamento ativo
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = fake()->dateTimeBetween('-1 month', 'now');
            $endDate = (clone $startDate)->modify('+1 month');
            
            return [
                'status' => 'active',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        });
    }

    /**
     * Orçamento completo
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = fake()->dateTimeBetween('-6 months', '-1 month');
            $endDate = (clone $startDate)->modify('+1 month');
            
            return [
                'status' => 'completed',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'spent_amount' => function (array $attributes) {
                    // Orçamentos completados geralmente gastaram mais
                    return fake()->randomFloat(2, $attributes['total_amount'] * 0.7, $attributes['total_amount']);
                },
            ];
        });
    }

    /**
     * Orçamento em rascunho
     */
    public function draft(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = fake()->dateTimeBetween('now', '+1 month');
            
            return [
                'status' => 'draft',
                'start_date' => $startDate,
                'spent_amount' => 0,
                'remaining_amount' => $attributes['total_amount'] ?? fake()->randomFloat(2, 1000, 10000),
            ];
        });
    }

    /**
     * Orçamento pausado
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
        ]);
    }

    /**
     * Orçamento com alto valor
     */
    public function highBudget(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_amount' => fake()->randomFloat(2, 20000, 100000),
        ]);
    }

    /**
     * Orçamento com baixo valor
     */
    public function lowBudget(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_amount' => fake()->randomFloat(2, 100, 500),
        ]);
    }

    /**
     * Orçamento quase estourando
     */
    public function nearLimit(): static
    {
        return $this->state(function (array $attributes) {
            $totalAmount = $attributes['total_amount'] ?? fake()->randomFloat(2, 1000, 10000);
            $spentAmount = $totalAmount * fake()->randomFloat(2, 0.85, 0.98);
            
            return [
                'total_amount' => $totalAmount,
                'spent_amount' => $spentAmount,
                'remaining_amount' => $totalAmount - $spentAmount,
            ];
        });
    }

    /**
     * Orçamento estourado
     */
    public function overBudget(): static
    {
        return $this->state(function (array $attributes) {
            $totalAmount = $attributes['total_amount'] ?? fake()->randomFloat(2, 1000, 10000);
            $spentAmount = $totalAmount * fake()->randomFloat(2, 1.05, 1.3);
            
            return [
                'total_amount' => $totalAmount,
                'spent_amount' => $spentAmount,
                'remaining_amount' => $totalAmount - $spentAmount, // Será negativo
            ];
        });
    }
}