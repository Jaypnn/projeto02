<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\FinancialGoal;
use Illuminate\Database\Seeder;

class FinancialSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar ou encontrar usuário de teste principal
        $user = User::firstOrCreate(
            ['email' => 'joao@example.com'],
            [
                'name' => 'João Silva',
                'password' => bcrypt('password'),
            ]
        );

        // Verificar se já tem dados financeiros
        if ($user->categories()->count() > 0) {
            $this->command->info("Usuário {$user->name} já tem dados financeiros, pulando...");
            return;
        }

        $this->command->info("Criando sistema financeiro básico para: {$user->name}");
        
        // Apenas criar algumas contas básicas - sem transações por enquanto
        $this->createBasicAccountsForUser($user);

        $this->command->info('Sistema financeiro básico populado com sucesso!');
    }

    /**
     * Cria contas básicas para um usuário
     */
    private function createBasicAccountsForUser(User $user): void
    {
        // Verificar se já tem contas
        if ($user->accounts()->count() > 0) {
            $this->command->info("Usuário já tem contas, pulando criação...");
            return;
        }
        
        // Criar apenas 2 contas simples
        $accounts = [
            [
                'name' => 'Conta Corrente',
                'type' => 'checking',
                'initial_balance' => 2500.00,
                'current_balance' => 2500.00,
                'currency' => 'BRL',
                'is_active' => true,
            ],
            [
                'name' => 'Poupança',
                'type' => 'savings', 
                'initial_balance' => 5000.00,
                'current_balance' => 5000.00,
                'currency' => 'BRL',
                'is_active' => true,
            ],
        ];
        
        $createdCount = 0;
        foreach ($accounts as $accountData) {
            Account::create(array_merge($accountData, [
                'user_id' => $user->id,
            ]));
            $createdCount++;
        }
        
        $this->command->info("- Criadas {$createdCount} contas básicas");
    }

    /**
     * Cria um sistema financeiro completo para um usuário (MÉTODO ANTIGO - NÃO USADO)
     */
    private function createFinancialSystemForUser(User $user): void
    {
        // 1. Usar categorias existentes ou criar se necessário
        $incomeCategories = Category::where('user_id', $user->id)->where('type', 'income')->get();
        if ($incomeCategories->count() < 5) {
            $needed = 5 - $incomeCategories->count();
            $newIncomeCategories = Category::factory()->count($needed)->income()->make()->map(function($category, $index) use ($user) {
                $category->user_id = $user->id;
                $category->name = $category->name . ' ' . ($index + 1); // Tornar único
                $category->save();
                return $category;
            });
            $incomeCategories = $incomeCategories->merge($newIncomeCategories);
        }

        $expenseCategories = Category::where('user_id', $user->id)->where('type', 'expense')->get();
        if ($expenseCategories->count() < 9) {
            $needed = 9 - $expenseCategories->count();
            $newExpenseCategories = Category::factory()->count($needed)->expense()->make()->map(function($category, $index) use ($user) {
                $category->user_id = $user->id;
                $category->name = $category->name . ' ' . ($index + 1); // Tornar único
                $category->save();
                return $category;
            });
            $expenseCategories = $expenseCategories->merge($newExpenseCategories);
        }

        // 3. Criar algumas subcategorias apenas se não existirem
        $subcategoriesCount = Category::where('user_id', $user->id)->whereNotNull('parent_id')->count();
        if ($subcategoriesCount < 3 && $expenseCategories->count() > 0) {
            for ($i = 0; $i < 3; $i++) {
                Category::factory()->make([
                    'user_id' => $user->id,
                    'parent_id' => $expenseCategories->random()->id,
                    'name' => 'Sub ' . $expenseCategories->random()->name . ' ' . ($i + 1),
                ])->save();
            }
        }

        // 4. Criar contas diferentes
        $accounts = collect([
            Account::factory()->checking()->create(['user_id' => $user->id]),
            Account::factory()->savings()->create(['user_id' => $user->id]),
            Account::factory()->creditCard()->create(['user_id' => $user->id]),
            Account::factory()->investment()->create(['user_id' => $user->id]),
            Account::factory()->cash()->create(['user_id' => $user->id]),
        ]);

        // 5. Criar transações variadas
        $this->createTransactionsForUser($user, $accounts, $incomeCategories, $expenseCategories);

        // 6. Criar orçamentos
        $budgets = $this->createBudgetsForUser($user, $expenseCategories);

        // 7. Criar metas financeiras
        $this->createFinancialGoalsForUser($user, $incomeCategories);

        // 8. Atualizar saldos das contas baseado nas transações
        $this->updateAccountBalances($accounts);
    }

    /**
     * Cria transações para o usuário
     */
    private function createTransactionsForUser(User $user, $accounts, $incomeCategories, $expenseCategories): void
    {
        // Receitas mensais (salário, freelances)
        Transaction::factory()->count(12)->income()->completed()->create([
            'user_id' => $user->id,
            'account_id' => $accounts->where('type', 'checking')->first()->id,
            'category_id' => $incomeCategories->random()->id,
        ]);

        // Despesas variadas
        Transaction::factory()->count(80)->expense()->completed()->create([
            'user_id' => $user->id,
            'account_id' => $accounts->random()->id,
            'category_id' => $expenseCategories->random()->id,
        ]);

        // Algumas transferências entre contas
        Transaction::factory()->count(10)->transfer()->completed()->create([
            'user_id' => $user->id,
            'account_id' => $accounts->random()->id,
            'destination_account_id' => $accounts->random()->id,
        ]);

        // Transações recorrentes
        Transaction::factory()->count(5)->monthlyRecurring()->completed()->create([
            'user_id' => $user->id,
            'account_id' => $accounts->random()->id,
            'category_id' => $expenseCategories->random()->id,
        ]);

        // Algumas transações pendentes
        Transaction::factory()->count(8)->pending()->create([
            'user_id' => $user->id,
            'account_id' => $accounts->random()->id,
            'category_id' => $expenseCategories->random()->id,
        ]);
    }

    /**
     * Cria orçamentos para o usuário
     */
    private function createBudgetsForUser(User $user, $expenseCategories)
    {
        $budgets = collect([
            // Orçamento mensal ativo
            Budget::factory()->monthly()->active()->create(['user_id' => $user->id]),
            
            // Orçamento mensal do mês passado (completo)
            Budget::factory()->monthly()->completed()->create(['user_id' => $user->id]),
            
            // Orçamento anual em rascunho
            Budget::factory()->yearly()->draft()->create(['user_id' => $user->id]),
        ]);

        // Para cada orçamento ativo/completo, criar categorias
        $budgets->whereIn('status', ['active', 'completed'])->each(function ($budget) use ($expenseCategories) {
            $selectedCategories = $expenseCategories->random(6);
            
            $selectedCategories->each(function ($category) use ($budget) {
                $state = fake()->randomElement(['underBudget', 'nearLimit', 'overBudget']);
                
                BudgetCategory::factory()->{$state}()->create([
                    'budget_id' => $budget->id,
                    'category_id' => $category->id,
                ]);
            });
            
            // Atualizar os valores calculados do orçamento
            $budget->updateCalculatedAmounts();
        });

        return $budgets;
    }

    /**
     * Cria metas financeiras para o usuário
     */
    private function createFinancialGoalsForUser(User $user, $incomeCategories): void
    {
        // Meta ativa de reserva de emergência
        FinancialGoal::factory()->savings()->active()->create([
            'user_id' => $user->id,
            'category_id' => $incomeCategories->random()->id,
            'name' => 'Reserva de Emergência',
        ]);

        // Meta de compra quase completada
        FinancialGoal::factory()->purchase()->nearCompletion()->create([
            'user_id' => $user->id,
            'category_id' => $incomeCategories->random()->id,
        ]);

        // Meta de investimento no início
        FinancialGoal::factory()->investment()->justStarted()->create([
            'user_id' => $user->id,
            'category_id' => $incomeCategories->random()->id,
        ]);

        // Meta completada
        FinancialGoal::factory()->purchase()->completed()->create([
            'user_id' => $user->id,
            'category_id' => $incomeCategories->random()->id,
        ]);

        // Meta atrasada
        FinancialGoal::factory()->savings()->overdue()->create([
            'user_id' => $user->id,
            'category_id' => $incomeCategories->random()->id,
        ]);
    }

    /**
     * Atualiza saldos das contas baseado nas transações
     */
    private function updateAccountBalances($accounts): void
    {
        $accounts->each(function ($account) {
            $account->updateCurrentBalance();
        });
    }
}