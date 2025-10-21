<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\FinancialGoalController;

// Rotas de autenticação com sessão (usando statefulApi do Laravel 11)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');
        
    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:web');
});

// Endpoint de diagnóstico opcional (remover em produção)
Route::get('/debug/csrf', function (Request $request) {
    return response()->json([
        'has_session' => $request->hasSession(),
        'session_id' => $request->session()->getId(),
        'csrf_token' => csrf_token(),
        'cookies' => request()->cookies->all(),
        'headers' => [
            'x-xsrf-token' => $request->header('X-XSRF-TOKEN'),
            'cookie' => $request->header('cookie'),
            'origin' => $request->header('origin'),
        ],
    ]);
});

// Rota para obter usuário atual (API protegida)
Route::middleware('auth:web')->get('/me', function (Request $request) {
    return $request->user();
});

// Rotas protegidas do sistema financeiro
Route::middleware('auth:web')->group(function () {
    
    // Categorias
    Route::apiResource('categories', CategoryController::class);
    Route::get('categories/{category}/stats', [CategoryController::class, 'stats']);
    Route::get('categories-top', [CategoryController::class, 'topCategories']);
    
    // Contas
    Route::apiResource('accounts', AccountController::class);
    Route::post('accounts/{account}/reactivate', [AccountController::class, 'reactivate']);
    Route::post('accounts/transfer', [AccountController::class, 'transfer']);
    Route::get('accounts/{account}/balance-history', [AccountController::class, 'balanceHistory']);
    Route::get('accounts-summary', [AccountController::class, 'summary']);
    Route::post('accounts/{account}/adjust-balance', [AccountController::class, 'adjustBalance']);
    
    // Transações
    Route::apiResource('transactions', TransactionController::class);
    Route::post('transactions/{transaction}/complete', [TransactionController::class, 'complete']);
    Route::post('transactions/{transaction}/cancel', [TransactionController::class, 'cancel']);
    Route::get('transactions-summary', [TransactionController::class, 'summary']);
    
    // Orçamentos
    Route::apiResource('budgets', BudgetController::class);
    Route::post('budgets/{budget}/categories', [BudgetController::class, 'addCategory']);
    Route::put('budgets/{budget}/categories/{budgetCategory}', [BudgetController::class, 'updateCategory']);
    Route::delete('budgets/{budget}/categories/{budgetCategory}', [BudgetController::class, 'removeCategory']);
    Route::get('budgets/{budget}/performance', [BudgetController::class, 'performance']);
    Route::post('budgets/{budget}/next-period', [BudgetController::class, 'createNextPeriod']);
    
    // Metas Financeiras
    Route::apiResource('financial-goals', FinancialGoalController::class);
    Route::post('financial-goals/{financialGoal}/contributions', [FinancialGoalController::class, 'addContribution']);
    Route::get('financial-goals/{financialGoal}/progress', [FinancialGoalController::class, 'progress']);
    Route::get('financial-goals-summary', [FinancialGoalController::class, 'summary']);
    Route::get('financial-goals-upcoming', [FinancialGoalController::class, 'upcoming']);
    Route::post('financial-goals/{financialGoal}/toggle-status', [FinancialGoalController::class, 'toggleStatus']);
    
});