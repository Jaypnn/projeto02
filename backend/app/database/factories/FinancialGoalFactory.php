<?php

namespace Database\Factories;

use App\Models\FinancialGoal;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinancialGoal>
 */
class FinancialGoalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $goalType = fake()->randomElement(['savings', 'investment', 'purchase', 'debt_payment', 'emergency_fund']);
        $goalData = $this->getGoalDataByType($goalType);
        
        $targetAmount = $goalData['target_amount'];
        $currentAmount = fake()->randomFloat(2, 0, $targetAmount * 0.6); // Até 60% do progresso
        
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory()->income(), // Geralmente associado a receitas
            'name' => $goalData['name'],
            'description' => $goalData['description'],
            'target_amount' => $targetAmount,
            'current_amount' => $currentAmount,
            'target_date' => fake()->dateTimeBetween('+3 months', '+5 years'),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'status' => fake()->randomElement(['active', 'paused', 'completed', 'canceled']),
            'goal_type' => $goalType,
            'monthly_contribution' => $goalData['monthly_contribution'],
            'notes' => fake()->optional(0.4)->paragraph(),
        ];
    }

    /**
     * Obtém dados específicos por tipo de meta
     */
    private function getGoalDataByType(string $goalType): array
    {
        return match($goalType) {
            'savings' => [
                'name' => fake()->randomElement([
                    'Reserva de Emergência',
                    'Poupança para Viagem',
                    'Fundo de Aposentadoria',
                    'Economia para Casa Nova',
                ]),
                'description' => fake()->randomElement([
                    'Construir uma reserva financeira sólida',
                    'Juntar dinheiro para objetivos futuros',
                    'Criar um fundo de segurança',
                ]),
                'target_amount' => fake()->randomFloat(2, 10000, 100000),
                'monthly_contribution' => fake()->randomFloat(2, 500, 3000),
            ],
            'investment' => [
                'name' => fake()->randomElement([
                    'Investimento em Ações',
                    'Fundo Imobiliário',
                    'Tesouro Direto',
                    'Criptomoedas',
                ]),
                'description' => fake()->randomElement([
                    'Diversificar investimentos',
                    'Aumentar patrimônio através de investimentos',
                    'Criar renda passiva',
                ]),
                'target_amount' => fake()->randomFloat(2, 5000, 200000),
                'monthly_contribution' => fake()->randomFloat(2, 300, 5000),
            ],
            'purchase' => [
                'name' => fake()->randomElement([
                    'Carro Novo',
                    'Casa Própria',
                    'Notebook Gamer',
                    'Viagem dos Sonhos',
                    'Móveis da Casa',
                ]),
                'description' => fake()->randomElement([
                    'Juntar dinheiro para uma compra importante',
                    'Realizar um sonho de consumo',
                    'Adquirir algo necessário',
                ]),
                'target_amount' => fake()->randomFloat(2, 2000, 500000),
                'monthly_contribution' => fake()->randomFloat(2, 200, 2000),
            ],
            'debt_payment' => [
                'name' => fake()->randomElement([
                    'Quitar Cartão de Crédito',
                    'Pagar Financiamento',
                    'Eliminar Dívidas',
                    'Quitar Empréstimo',
                ]),
                'description' => fake()->randomElement([
                    'Eliminar dívidas pendentes',
                    'Ficar livre de juros',
                    'Organizar vida financeira',
                ]),
                'target_amount' => fake()->randomFloat(2, 1000, 50000),
                'monthly_contribution' => fake()->randomFloat(2, 200, 1500),
            ],
            'emergency_fund' => [
                'name' => fake()->randomElement([
                    'Reserva de Emergência 6 meses',
                    'Fundo de Segurança',
                    'Reserva para Imprevistos',
                ]),
                'description' => fake()->randomElement([
                    'Ter 6 meses de gastos guardados',
                    'Proteção contra imprevistos',
                    'Segurança financeira',
                ]),
                'target_amount' => fake()->randomFloat(2, 15000, 60000),
                'monthly_contribution' => fake()->randomFloat(2, 500, 2000),
            ],
        };
    }

    /**
     * Meta de poupança
     */
    public function savings(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'goal_type' => 'savings',
                'name' => fake()->randomElement([
                    'Reserva de Emergência',
                    'Poupança para Viagem',
                    'Fundo de Aposentadoria',
                    'Economia para Casa Nova',
                ]),
                'target_amount' => fake()->randomFloat(2, 10000, 100000),
                'monthly_contribution' => fake()->randomFloat(2, 500, 3000),
            ];
        });
    }

    /**
     * Meta de investimento
     */
    public function investment(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'goal_type' => 'investment',
                'name' => fake()->randomElement([
                    'Investimento em Ações',
                    'Fundo Imobiliário',
                    'Tesouro Direto',
                    'Criptomoedas',
                ]),
                'target_amount' => fake()->randomFloat(2, 5000, 200000),
                'monthly_contribution' => fake()->randomFloat(2, 300, 5000),
            ];
        });
    }

    /**
     * Meta de compra
     */
    public function purchase(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'goal_type' => 'purchase',
                'name' => fake()->randomElement([
                    'Carro Novo',
                    'Casa Própria',
                    'Notebook Gamer',
                    'Viagem dos Sonhos',
                    'Móveis da Casa',
                ]),
                'target_amount' => fake()->randomFloat(2, 2000, 500000),
                'monthly_contribution' => fake()->randomFloat(2, 200, 2000),
            ];
        });
    }

    /**
     * Meta ativa
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $targetAmount = $attributes['target_amount'] ?? fake()->randomFloat(2, 5000, 50000);
            
            return [
                'status' => 'active',
                'current_amount' => fake()->randomFloat(2, 0, $targetAmount * 0.8),
                'target_date' => fake()->dateTimeBetween('+1 month', '+3 years'),
            ];
        });
    }

    /**
     * Meta completada
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $targetAmount = $attributes['target_amount'] ?? fake()->randomFloat(2, 5000, 50000);
            
            return [
                'status' => 'completed',
                'current_amount' => $targetAmount,
                'target_date' => fake()->dateTimeBetween('-1 year', 'now'),
            ];
        });
    }

    /**
     * Meta pausada
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
        ]);
    }

    /**
     * Meta cancelada
     */
    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'canceled',
        ]);
    }

    /**
     * Meta de alta prioridade
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * Meta de baixa prioridade
     */
    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'low',
        ]);
    }

    /**
     * Meta de curto prazo (até 1 ano)
     */
    public function shortTerm(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_date' => fake()->dateTimeBetween('+1 month', '+12 months'),
            'target_amount' => fake()->randomFloat(2, 1000, 10000),
            'monthly_contribution' => fake()->randomFloat(2, 200, 1000),
        ]);
    }

    /**
     * Meta de longo prazo (mais de 3 anos)
     */
    public function longTerm(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_date' => fake()->dateTimeBetween('+3 years', '+10 years'),
            'target_amount' => fake()->randomFloat(2, 50000, 500000),
            'monthly_contribution' => fake()->randomFloat(2, 1000, 5000),
        ]);
    }

    /**
     * Meta quase completada (90%+)
     */
    public function nearCompletion(): static
    {
        return $this->state(function (array $attributes) {
            $targetAmount = $attributes['target_amount'] ?? fake()->randomFloat(2, 5000, 50000);
            $currentAmount = $targetAmount * fake()->randomFloat(2, 0.9, 0.99);
            
            return [
                'current_amount' => $currentAmount,
                'status' => 'active',
            ];
        });
    }

    /**
     * Meta no início (menos de 20%)
     */
    public function justStarted(): static
    {
        return $this->state(function (array $attributes) {
            $targetAmount = $attributes['target_amount'] ?? fake()->randomFloat(2, 5000, 50000);
            $currentAmount = $targetAmount * fake()->randomFloat(2, 0, 0.2);
            
            return [
                'current_amount' => $currentAmount,
                'status' => 'active',
            ];
        });
    }

    /**
     * Meta atrasada
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            $targetAmount = $attributes['target_amount'] ?? fake()->randomFloat(2, 5000, 50000);
            
            return [
                'target_date' => fake()->dateTimeBetween('-6 months', '-1 day'),
                'current_amount' => fake()->randomFloat(2, 0, $targetAmount * 0.7), // Não completou
                'status' => 'active',
            ];
        });
    }
}