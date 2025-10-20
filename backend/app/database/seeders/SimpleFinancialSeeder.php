<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Account;
use Illuminate\Database\Seeder;

class SimpleFinancialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Criando dados financeiros básicos...');

        // 1. Criar ou encontrar usuário de teste
        $user = User::firstOrCreate(
            ['email' => 'teste@example.com'],
            [
                'name' => 'Usuário de Teste',
                'password' => bcrypt('password'),
            ]
        );

        $this->command->info("Usuário criado/encontrado: {$user->name}");

        // 2. Criar categorias básicas (apenas se não existirem)
        if ($user->categories()->count() == 0) {
            $categories = [
                // Receitas
                ['name' => 'Salário', 'type' => 'income', 'color' => '#4CAF50', 'icon' => 'work'],
                ['name' => 'Freelance', 'type' => 'income', 'color' => '#2196F3', 'icon' => 'business'],
                
                // Despesas
                ['name' => 'Alimentação', 'type' => 'expense', 'color' => '#F44336', 'icon' => 'restaurant'],
                ['name' => 'Transporte', 'type' => 'expense', 'color' => '#FF5722', 'icon' => 'directions_car'],
                ['name' => 'Casa', 'type' => 'expense', 'color' => '#795548', 'icon' => 'home'],
            ];

            foreach ($categories as $categoryData) {
                Category::create([
                    'user_id' => $user->id,
                    'name' => $categoryData['name'],
                    'type' => $categoryData['type'],
                    'color' => $categoryData['color'],
                    'icon' => $categoryData['icon'],
                    'is_active' => true,
                ]);
            }

            $this->command->info('Categorias criadas: ' . count($categories));
        } else {
            $this->command->info('Usuário já tem categorias, pulando...');
        }

        // 3. Criar contas básicas (apenas se não existirem)
        if ($user->accounts()->count() == 0) {
            $accounts = [
                [
                    'name' => 'Conta Corrente',
                    'type' => 'checking',
                    'initial_balance' => 1500.00,
                    'current_balance' => 1500.00,
                ],
                [
                    'name' => 'Poupança',
                    'type' => 'savings',
                    'initial_balance' => 5000.00,
                    'current_balance' => 5000.00,
                ],
            ];

            foreach ($accounts as $accountData) {
                Account::create([
                    'user_id' => $user->id,
                    'name' => $accountData['name'],
                    'type' => $accountData['type'],
                    'initial_balance' => $accountData['initial_balance'],
                    'current_balance' => $accountData['current_balance'],
                    'currency' => 'BRL',
                    'is_active' => true,
                    'color' => '#000000',
                ]);
            }

            $this->command->info('Contas criadas: ' . count($accounts));
        } else {
            $this->command->info('Usuário já tem contas, pulando...');
        }

        $this->command->info('✅ Dados financeiros básicos criados com sucesso!');
        $this->command->info("📧 Login: {$user->email} | 🔑 Senha: password");
    }
}