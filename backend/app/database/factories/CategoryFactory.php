<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            // Categorias de receita
            ['name' => 'Salário', 'type' => 'income', 'icon' => 'salary', 'color' => '#4CAF50'],
            ['name' => 'Freelance', 'type' => 'income', 'icon' => 'work', 'color' => '#2196F3'],
            ['name' => 'Investimentos', 'type' => 'income', 'icon' => 'trending_up', 'color' => '#FF9800'],
            ['name' => 'Vendas', 'type' => 'income', 'icon' => 'shopping_bag', 'color' => '#9C27B0'],
            ['name' => 'Outros', 'type' => 'income', 'icon' => 'attach_money', 'color' => '#607D8B'],
            
            // Categorias de despesa
            ['name' => 'Alimentação', 'type' => 'expense', 'icon' => 'restaurant', 'color' => '#F44336'],
            ['name' => 'Transporte', 'type' => 'expense', 'icon' => 'directions_car', 'color' => '#FF5722'],
            ['name' => 'Moradia', 'type' => 'expense', 'icon' => 'home', 'color' => '#795548'],
            ['name' => 'Saúde', 'type' => 'expense', 'icon' => 'local_hospital', 'color' => '#E91E63'],
            ['name' => 'Educação', 'type' => 'expense', 'icon' => 'school', 'color' => '#3F51B5'],
            ['name' => 'Lazer', 'type' => 'expense', 'icon' => 'sports_esports', 'color' => '#9E9E9E'],
            ['name' => 'Roupas', 'type' => 'expense', 'icon' => 'shopping_cart', 'color' => '#CDDC39'],
            ['name' => 'Tecnologia', 'type' => 'expense', 'icon' => 'computer', 'color' => '#00BCD4'],
            ['name' => 'Viagem', 'type' => 'expense', 'icon' => 'flight', 'color' => '#FFC107'],
        ];

        $category = fake()->randomElement($categories);

        return [
            'user_id' => User::factory(),
            'parent_id' => null, // Será definido em estados específicos
            'name' => $category['name'],
            'description' => fake()->optional(0.7)->sentence(),
            'type' => $category['type'],
            'icon' => $category['icon'],
            'color' => $category['color'],
            'is_active' => fake()->boolean(95), // 95% ativas
        ];
    }

    /**
     * Indica que a categoria é uma subcategoria
     */
    public function subcategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => Category::factory(),
        ]);
    }

    /**
     * Categoria de receita
     */
    public function income(): static
    {
        $incomeCategories = [
            ['name' => 'Salário', 'icon' => 'salary', 'color' => '#4CAF50'],
            ['name' => 'Freelance', 'icon' => 'work', 'color' => '#2196F3'],
            ['name' => 'Investimentos', 'icon' => 'trending_up', 'color' => '#FF9800'],
            ['name' => 'Vendas', 'icon' => 'shopping_bag', 'color' => '#9C27B0'],
            ['name' => 'Outros', 'icon' => 'attach_money', 'color' => '#607D8B'],
        ];

        return $this->state(function (array $attributes) use ($incomeCategories) {
            $category = fake()->randomElement($incomeCategories);
            return [
                'name' => $category['name'],
                'type' => 'income',
                'icon' => $category['icon'],
                'color' => $category['color'],
            ];
        });
    }

    /**
     * Categoria de despesa
     */
    public function expense(): static
    {
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

        return $this->state(function (array $attributes) use ($expenseCategories) {
            $category = fake()->randomElement($expenseCategories);
            return [
                'name' => $category['name'],
                'type' => 'expense',
                'icon' => $category['icon'],
                'color' => $category['color'],
            ];
        });
    }

    /**
     * Categoria inativa
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}