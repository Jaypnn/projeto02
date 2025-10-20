<?php

namespace Database\Factories;

use App\Models\BudgetCategory;
use App\Models\Budget;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BudgetCategory>
 */
class BudgetCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'budget_id' => Budget::factory(),
            'category_id' => Category::factory()->expense(), // Geralmente são despesas
            'allocated_amount' => fake()->randomFloat(2, 100, 2000),
            'spent_amount' => function (array $attributes) {
                // Valor gasto geralmente menor que o alocado
                return fake()->randomFloat(2, 0, $attributes['allocated_amount'] * 0.8);
            },
            'remaining_amount' => function (array $attributes) {
                return $attributes['allocated_amount'] - $attributes['spent_amount'];
            },
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Categoria de orçamento com gastos controlados
     */
    public function underBudget(): static
    {
        return $this->state(function (array $attributes) {
            $allocatedAmount = $attributes['allocated_amount'] ?? fake()->randomFloat(2, 100, 2000);
            $spentAmount = $allocatedAmount * fake()->randomFloat(2, 0.1, 0.7); // 10% a 70% gasto
            
            return [
                'allocated_amount' => $allocatedAmount,
                'spent_amount' => $spentAmount,
                'remaining_amount' => $allocatedAmount - $spentAmount,
            ];
        });
    }

    /**
     * Categoria de orçamento próxima do limite
     */
    public function nearLimit(): static
    {
        return $this->state(function (array $attributes) {
            $allocatedAmount = $attributes['allocated_amount'] ?? fake()->randomFloat(2, 100, 2000);
            $spentAmount = $allocatedAmount * fake()->randomFloat(2, 0.85, 0.95); // 85% a 95% gasto
            
            return [
                'allocated_amount' => $allocatedAmount,
                'spent_amount' => $spentAmount,
                'remaining_amount' => $allocatedAmount - $spentAmount,
            ];
        });
    }

    /**
     * Categoria de orçamento estourada
     */
    public function overBudget(): static
    {
        return $this->state(function (array $attributes) {
            $allocatedAmount = $attributes['allocated_amount'] ?? fake()->randomFloat(2, 100, 2000);
            $spentAmount = $allocatedAmount * fake()->randomFloat(2, 1.05, 1.5); // 105% a 150% gasto
            
            return [
                'allocated_amount' => $allocatedAmount,
                'spent_amount' => $spentAmount,
                'remaining_amount' => $allocatedAmount - $spentAmount, // Será negativo
            ];
        });
    }

    /**
     * Categoria para alimentação
     */
    public function food(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'category_id' => Category::factory()->state([
                    'name' => 'Alimentação',
                    'type' => 'expense',
                    'icon' => 'restaurant',
                    'color' => '#F44336',
                ]),
                'allocated_amount' => fake()->randomFloat(2, 500, 1500),
                'notes' => fake()->optional(0.5)->randomElement([
                    'Supermercado e restaurantes',
                    'Inclui delivery e lanches',
                    'Orçamento para refeições',
                ]),
            ];
        });
    }

    /**
     * Categoria para transporte
     */
    public function transport(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'category_id' => Category::factory()->state([
                    'name' => 'Transporte',
                    'type' => 'expense',
                    'icon' => 'directions_car',
                    'color' => '#FF5722',
                ]),
                'allocated_amount' => fake()->randomFloat(2, 200, 800),
                'notes' => fake()->optional(0.5)->randomElement([
                    'Combustível e manutenção',
                    'Uber e transporte público',
                    'Gastos com veículo',
                ]),
            ];
        });
    }

    /**
     * Categoria para moradia
     */
    public function housing(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'category_id' => Category::factory()->state([
                    'name' => 'Moradia',
                    'type' => 'expense',
                    'icon' => 'home',
                    'color' => '#795548',
                ]),
                'allocated_amount' => fake()->randomFloat(2, 800, 3000),
                'notes' => fake()->optional(0.5)->randomElement([
                    'Aluguel e condomínio',
                    'Contas da casa',
                    'Manutenção e reparos',
                ]),
            ];
        });
    }

    /**
     * Categoria para lazer
     */
    public function entertainment(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'category_id' => Category::factory()->state([
                    'name' => 'Lazer',
                    'type' => 'expense',
                    'icon' => 'sports_esports',
                    'color' => '#9E9E9E',
                ]),
                'allocated_amount' => fake()->randomFloat(2, 100, 600),
                'notes' => fake()->optional(0.5)->randomElement([
                    'Cinema e entretenimento',
                    'Saídas e diversão',
                    'Hobbies e atividades',
                ]),
            ];
        });
    }

    /**
     * Categoria para saúde
     */
    public function health(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'category_id' => Category::factory()->state([
                    'name' => 'Saúde',
                    'type' => 'expense',
                    'icon' => 'local_hospital',
                    'color' => '#E91E63',
                ]),
                'allocated_amount' => fake()->randomFloat(2, 200, 1000),
                'notes' => fake()->optional(0.5)->randomElement([
                    'Consultas e exames',
                    'Medicamentos e farmácia',
                    'Plano de saúde',
                ]),
            ];
        });
    }

    /**
     * Categoria com alto valor alocado
     */
    public function highAllocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'allocated_amount' => fake()->randomFloat(2, 2000, 5000),
        ]);
    }

    /**
     * Categoria com baixo valor alocado
     */
    public function lowAllocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'allocated_amount' => fake()->randomFloat(2, 50, 200),
        ]);
    }

    /**
     * Categoria sem gastos ainda
     */
    public function noSpending(): static
    {
        return $this->state(function (array $attributes) {
            $allocatedAmount = $attributes['allocated_amount'] ?? fake()->randomFloat(2, 100, 2000);
            
            return [
                'allocated_amount' => $allocatedAmount,
                'spent_amount' => 0,
                'remaining_amount' => $allocatedAmount,
            ];
        });
    }

    /**
     * Categoria completamente gasta
     */
    public function fullySpent(): static
    {
        return $this->state(function (array $attributes) {
            $allocatedAmount = $attributes['allocated_amount'] ?? fake()->randomFloat(2, 100, 2000);
            
            return [
                'allocated_amount' => $allocatedAmount,
                'spent_amount' => $allocatedAmount,
                'remaining_amount' => 0,
            ];
        });
    }
}