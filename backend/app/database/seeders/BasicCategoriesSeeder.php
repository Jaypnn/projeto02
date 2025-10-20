<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BasicCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar usuário padrão se não existir
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
            ]
        );

        // Categorias básicas de receita
        $incomeCategories = [
            ['name' => 'Salário', 'icon' => 'work', 'color' => '#4CAF50'],
            ['name' => 'Freelance', 'icon' => 'business', 'color' => '#2196F3'],
            ['name' => 'Investimentos', 'icon' => 'trending_up', 'color' => '#FF9800'],
            ['name' => 'Vendas', 'icon' => 'shopping_bag', 'color' => '#9C27B0'],
            ['name' => 'Outros', 'icon' => 'attach_money', 'color' => '#607D8B'],
        ];

        foreach ($incomeCategories as $categoryData) {
            Category::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $categoryData['name'],
                    'type' => 'income'
                ],
                array_merge($categoryData, [
                    'type' => 'income',
                    'is_active' => true,
                ])
            );
        }

        // Categorias básicas de despesa
        $expenseCategories = [
            ['name' => 'Alimentação', 'icon' => 'restaurant', 'color' => '#F44336'],
            ['name' => 'Transporte', 'icon' => 'directions_car', 'color' => '#FF5722'],
            ['name' => 'Moradia', 'icon' => 'home', 'color' => '#795548'],
            ['name' => 'Saúde', 'icon' => 'local_hospital', 'color' => '#E91E63'],
            ['name' => 'Educação', 'icon' => 'school', 'color' => '#3F51B5'],
            ['name' => 'Lazer', 'icon' => 'sports_esports', 'color' => '#9E9E9E'],
            ['name' => 'Roupas', 'icon' => 'shopping_cart', 'color' => '#CDDC39'],
            ['name' => 'Tecnologia', 'icon' => 'computer', 'color' => '#00BCD4'],
            ['name' => 'Viagem', 'icon' => 'flight', 'color' => '#FFC107'],
        ];

        foreach ($expenseCategories as $categoryData) {
            Category::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $categoryData['name'],
                    'type' => 'expense'
                ],
                array_merge($categoryData, [
                    'type' => 'expense',
                    'is_active' => true,
                ])
            );
        }

        $this->command->info('Categorias básicas criadas com sucesso!');
    }
}