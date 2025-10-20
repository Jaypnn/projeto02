<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $accountTypes = [
            'checking' => [
                'names' => ['Conta Corrente Banco do Brasil', 'Conta Itaú', 'Conta Santander', 'Conta Bradesco', 'Conta Caixa'],
                'initial_balance_range' => [500, 5000],
            ],
            'savings' => [
                'names' => ['Poupança BB', 'Poupança Itaú', 'Poupança Santander', 'Poupança Caixa'],
                'initial_balance_range' => [1000, 20000],
            ],
            'credit_card' => [
                'names' => ['Cartão Visa', 'Cartão Mastercard', 'Cartão American Express', 'Cartão Elo'],
                'initial_balance_range' => [-2000, 0],
            ],
            'investment' => [
                'names' => ['Conta Investimento', 'Corretora XP', 'Rico Investimentos', 'Clear Corretora'],
                'initial_balance_range' => [5000, 50000],
            ],
            'cash' => [
                'names' => ['Dinheiro na Carteira', 'Cofre em Casa', 'Dinheiro Guardado'],
                'initial_balance_range' => [50, 500],
            ],
        ];

        $type = fake()->randomElement(array_keys($accountTypes));
        $typeData = $accountTypes[$type];

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement($typeData['names']),
            'type' => $type,
            'initial_balance' => fake()->randomFloat(2, $typeData['initial_balance_range'][0], $typeData['initial_balance_range'][1]),
            'current_balance' => function (array $attributes) {
                // Saldo atual pode variar do inicial
                $variation = fake()->randomFloat(2, -500, 1000);
                return $attributes['initial_balance'] + $variation;
            },
            'account_number' => fake()->optional(0.8)->numerify('######-#'),
            'bank_name' => fake()->optional(0.7)->randomElement([
                'Banco do Brasil', 'Itaú', 'Santander', 'Bradesco', 'Caixa Econômica Federal',
                'Nubank', 'Inter', 'Original', 'Safra', 'BTG Pactual'
            ]),
            'currency' => 'BRL',
            'is_active' => fake()->boolean(95), // 95% ativas
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Conta corrente
     */
    public function checking(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'checking',
            'name' => fake()->randomElement([
                'Conta Corrente Banco do Brasil',
                'Conta Itaú',
                'Conta Santander',
                'Conta Bradesco',
                'Conta Caixa'
            ]),
            'initial_balance' => fake()->randomFloat(2, 500, 5000),
            'account_number' => fake()->numerify('######-#'),
        ]);
    }

    /**
     * Conta poupança
     */
    public function savings(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'savings',
            'name' => fake()->randomElement([
                'Poupança BB',
                'Poupança Itaú',
                'Poupança Santander',
                'Poupança Caixa'
            ]),
            'initial_balance' => fake()->randomFloat(2, 1000, 20000),
            'account_number' => fake()->numerify('######-#'),
        ]);
    }

    /**
     * Cartão de crédito
     */
    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'credit_card',
            'name' => fake()->randomElement([
                'Cartão Visa',
                'Cartão Mastercard',
                'Cartão American Express',
                'Cartão Elo'
            ]),
            'initial_balance' => 0,
            'current_balance' => fake()->randomFloat(2, -2000, 0),
            'account_number' => fake()->numerify('**** **** **** ####'),
        ]);
    }

    /**
     * Conta de investimento
     */
    public function investment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'investment',
            'name' => fake()->randomElement([
                'Conta Investimento',
                'Corretora XP',
                'Rico Investimentos',
                'Clear Corretora'
            ]),
            'initial_balance' => fake()->randomFloat(2, 5000, 50000),
            'account_number' => fake()->optional()->numerify('######'),
        ]);
    }

    /**
     * Dinheiro em espécie
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'cash',
            'name' => fake()->randomElement([
                'Dinheiro na Carteira',
                'Cofre em Casa',
                'Dinheiro Guardado'
            ]),
            'initial_balance' => fake()->randomFloat(2, 50, 500),
            'account_number' => null,
            'bank_name' => null,
        ]);
    }

    /**
     * Conta inativa
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Conta com saldo alto
     */
    public function highBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'initial_balance' => fake()->randomFloat(2, 10000, 100000),
            'current_balance' => function (array $attributes) {
                return $attributes['initial_balance'] + fake()->randomFloat(2, -1000, 5000);
            },
        ]);
    }

    /**
     * Conta com saldo baixo
     */
    public function lowBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'initial_balance' => fake()->randomFloat(2, 10, 100),
            'current_balance' => function (array $attributes) {
                return $attributes['initial_balance'] + fake()->randomFloat(2, -50, 50);
            },
        ]);
    }
}