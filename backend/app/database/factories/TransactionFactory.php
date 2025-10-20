<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Category;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['income', 'expense', 'transfer']);
        
        return [
            'user_id' => User::factory(),
            'account_id' => Account::factory(),
            'category_id' => Category::factory(),
            'destination_account_id' => null, // Será definido para transfers
            'amount' => $this->getAmountByType($type),
            'type' => $type,
            'status' => fake()->randomElement(['pending', 'completed', 'canceled']),
            'description' => $this->getDescriptionByType($type),
            'transaction_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'notes' => fake()->optional(0.3)->sentence(),
            'reference' => fake()->optional(0.2)->numerify('REF-#########'),
            'is_recurring' => fake()->boolean(10), // 10% são recorrentes
            'recurring_frequency' => null, // Será definido para recorrentes
            'recurring_end_date' => null,
        ];
    }

    /**
     * Obtém valor baseado no tipo de transação
     */
    private function getAmountByType(string $type): float
    {
        return match($type) {
            'income' => fake()->randomFloat(2, 500, 10000), // Receitas maiores
            'expense' => fake()->randomFloat(2, 10, 2000),  // Despesas menores
            'transfer' => fake()->randomFloat(2, 100, 5000), // Transferências médias
        };
    }

    /**
     * Obtém descrição baseada no tipo de transação
     */
    private function getDescriptionByType(string $type): string
    {
        $descriptions = [
            'income' => [
                'Salário do mês',
                'Freelance projeto X',
                'Rendimento investimentos',
                'Venda de produto',
                'Comissão de vendas',
                'Dividendos recebidos',
            ],
            'expense' => [
                'Compra no supermercado',
                'Combustível',
                'Almoço restaurante',
                'Conta de luz',
                'Internet',
                'Farmácia',
                'Uber',
                'Streaming Netflix',
                'Academia',
                'Compra online',
            ],
            'transfer' => [
                'Transferência entre contas',
                'Depósito na poupança',
                'Pagamento cartão de crédito',
                'Transferência para investimento',
            ],
        ];

        return fake()->randomElement($descriptions[$type]);
    }

    /**
     * Transação de receita
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'income',
            'amount' => fake()->randomFloat(2, 500, 10000),
            'category_id' => Category::factory()->income(),
            'description' => fake()->randomElement([
                'Salário do mês',
                'Freelance projeto X',
                'Rendimento investimentos',
                'Venda de produto',
                'Comissão de vendas',
                'Dividendos recebidos',
            ]),
        ]);
    }

    /**
     * Transação de despesa
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
            'amount' => fake()->randomFloat(2, 10, 2000),
            'category_id' => Category::factory()->expense(),
            'description' => fake()->randomElement([
                'Compra no supermercado',
                'Combustível',
                'Almoço restaurante',
                'Conta de luz',
                'Internet',
                'Farmácia',
                'Uber',
                'Streaming Netflix',
                'Academia',
                'Compra online',
            ]),
        ]);
    }

    /**
     * Transação de transferência
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'transfer',
            'amount' => fake()->randomFloat(2, 100, 5000),
            'destination_account_id' => Account::factory(),
            'category_id' => null, // Transferências podem não ter categoria
            'description' => fake()->randomElement([
                'Transferência entre contas',
                'Depósito na poupança',
                'Pagamento cartão de crédito',
                'Transferência para investimento',
            ]),
        ]);
    }

    /**
     * Transação pendente
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'transaction_date' => fake()->dateTimeBetween('now', '+30 days'),
        ]);
    }

    /**
     * Transação completada
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'transaction_date' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Transação cancelada
     */
    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'canceled',
        ]);
    }

    /**
     * Transação recorrente
     */
    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
            'recurring_frequency' => fake()->randomElement(['daily', 'weekly', 'monthly', 'quarterly', 'yearly']),
            'recurring_end_date' => fake()->optional(0.7)->dateTimeBetween('+1 month', '+2 years'),
        ]);
    }

    /**
     * Transação mensal recorrente
     */
    public function monthlyRecurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
            'recurring_frequency' => 'monthly',
            'recurring_end_date' => fake()->optional(0.7)->dateTimeBetween('+6 months', '+2 years'),
        ]);
    }

    /**
     * Transação de alto valor
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => fake()->randomFloat(2, 5000, 50000),
        ]);
    }

    /**
     * Transação de baixo valor
     */
    public function lowValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => fake()->randomFloat(2, 1, 50),
        ]);
    }

    /**
     * Transação recente (últimos 30 dias)
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_date' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Transação antiga (mais de 6 meses)
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_date' => fake()->dateTimeBetween('-2 years', '-6 months'),
        ]);
    }
}