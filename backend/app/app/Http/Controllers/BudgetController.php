<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = Budget::where('user_id', $user->id)
            ->with(['budgetCategories.category']);

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('period_type')) {
            $query->where('period_type', $request->period_type);
        }

        if ($request->has('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('end_date', '<=', $request->end_date);
        }

        // Ordenação
        $orderBy = $request->get('order_by', 'start_date');
        $orderDirection = $request->get('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        $budgets = $query->paginate($request->get('per_page', 15));

        return response()->json($budgets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'total_amount' => 'required|numeric|min:0',
            'period_type' => 'required|in:weekly,monthly,quarterly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'in:draft,active,completed,paused',
            'alert_percentage' => 'integer|min:1|max:100',
            'notes' => 'nullable|string|max:1000',
            'categories' => 'nullable|array',
            'categories.*.category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
            'categories.*.allocated_amount' => 'required|numeric|min:0',
            'categories.*.notes' => 'nullable|string|max:500',
        ]);

        // Verificar se há sobreposição de datas para orçamentos ativos
        $overlapping = Budget::where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('start_date', '<=', $validated['start_date'])
                          ->where('end_date', '>=', $validated['end_date']);
                    });
            })
            ->exists();

        if ($overlapping && ($validated['status'] ?? 'draft') === 'active') {
            return response()->json([
                'message' => 'Já existe um orçamento ativo no período especificado.',
            ], 422);
        }

        // Verificar se a soma das categorias não excede o total
        if (!empty($validated['categories'])) {
            $totalCategories = collect($validated['categories'])->sum('allocated_amount');
            if ($totalCategories > $validated['total_amount']) {
                return response()->json([
                    'message' => 'A soma das categorias não pode exceder o valor total do orçamento.',
                ], 422);
            }
        }

        $validated['user_id'] = $user->id;
        $validated['status'] = $validated['status'] ?? 'draft';
        $validated['spent_amount'] = 0;
        $validated['remaining_amount'] = $validated['total_amount'];
        $validated['alert_percentage'] = $validated['alert_percentage'] ?? 80;

        try {
            DB::beginTransaction();

            $budget = Budget::create($validated);

            // Criar categorias do orçamento
            if (!empty($validated['categories'])) {
                foreach ($validated['categories'] as $categoryData) {
                    $budget->budgetCategories()->create([
                        'category_id' => $categoryData['category_id'],
                        'allocated_amount' => $categoryData['allocated_amount'],
                        'spent_amount' => 0,
                        'remaining_amount' => $categoryData['allocated_amount'],
                        'notes' => $categoryData['notes'] ?? null,
                    ]);
                }
            }

            $budget->load(['budgetCategories.category']);

            DB::commit();

            return response()->json([
                'message' => 'Orçamento criado com sucesso.',
                'data' => $budget
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erro ao criar orçamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Budget $budget): JsonResponse
    {
        $user = Auth::user();

        if ($budget->user_id !== $user->id) {
            return response()->json(['message' => 'Orçamento não encontrado.'], 404);
        }

        $budget->load(['budgetCategories.category']);
        
        // Atualizar valores calculados
        $budget->updateCalculatedAmounts();

        // Adicionar estatísticas
        $stats = $budget->getStats();

        return response()->json([
            'data' => $budget,
            'stats' => $stats
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Budget $budget): JsonResponse
    {
        $user = Auth::user();

        if ($budget->user_id !== $user->id) {
            return response()->json(['message' => 'Orçamento não encontrado.'], 404);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string|max:1000',
            'total_amount' => 'numeric|min:0',
            'period_type' => 'in:weekly,monthly,quarterly,yearly',
            'start_date' => 'date',
            'end_date' => 'date|after:start_date',
            'status' => 'in:draft,active,completed,paused',
            'alert_percentage' => 'integer|min:1|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Não permitir alterar datas se o orçamento estiver ativo e tiver transações
        if ($budget->status === 'active' && isset($validated['start_date'], $validated['end_date'])) {
            $hasTransactions = $budget->budgetCategories()
                ->whereHas('category.transactions', function ($query) use ($budget) {
                    $query->whereBetween('transaction_date', [$budget->start_date, $budget->end_date]);
                })
                ->exists();

            if ($hasTransactions) {
                return response()->json([
                    'message' => 'Não é possível alterar as datas de um orçamento ativo com transações.',
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $budget->update($validated);

            // Recalcular valores se necessário
            if (isset($validated['total_amount'])) {
                $budget->updateCalculatedAmounts();
            }

            $budget->load(['budgetCategories.category']);

            DB::commit();

            return response()->json([
                'message' => 'Orçamento atualizado com sucesso.',
                'data' => $budget
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erro ao atualizar orçamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Budget $budget): JsonResponse
    {
        $user = Auth::user();

        if ($budget->user_id !== $user->id) {
            return response()->json(['message' => 'Orçamento não encontrado.'], 404);
        }

        // Só permitir excluir se estiver em rascunho
        if ($budget->status !== 'draft') {
            return response()->json([
                'message' => 'Apenas orçamentos em rascunho podem ser excluídos.',
            ], 422);
        }

        $budget->delete();

        return response()->json([
            'message' => 'Orçamento excluído com sucesso.'
        ]);
    }

    /**
     * Add category to budget
     */
    public function addCategory(Request $request, Budget $budget): JsonResponse
    {
        $user = Auth::user();

        if ($budget->user_id !== $user->id) {
            return response()->json(['message' => 'Orçamento não encontrado.'], 404);
        }

        $validated = $request->validate([
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
            'allocated_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        // Verificar se a categoria já existe no orçamento
        if ($budget->budgetCategories()->where('category_id', $validated['category_id'])->exists()) {
            return response()->json([
                'message' => 'Esta categoria já está no orçamento.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $budgetCategory = $budget->budgetCategories()->create([
                'category_id' => $validated['category_id'],
                'allocated_amount' => $validated['allocated_amount'],
                'spent_amount' => 0,
                'remaining_amount' => $validated['allocated_amount'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $budgetCategory->updateCalculatedAmounts();
            $budget->updateCalculatedAmounts();

            $budgetCategory->load('category');

            DB::commit();

            return response()->json([
                'message' => 'Categoria adicionada ao orçamento com sucesso.',
                'data' => $budgetCategory
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erro ao adicionar categoria ao orçamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update budget category
     */
    public function updateCategory(Request $request, Budget $budget, BudgetCategory $budgetCategory): JsonResponse
    {
        $user = Auth::user();

        if ($budget->user_id !== $user->id || $budgetCategory->budget_id !== $budget->id) {
            return response()->json(['message' => 'Categoria do orçamento não encontrada.'], 404);
        }

        $validated = $request->validate([
            'allocated_amount' => 'numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $budgetCategory->update($validated);
            $budgetCategory->updateCalculatedAmounts();
            $budget->updateCalculatedAmounts();

            DB::commit();

            return response()->json([
                'message' => 'Categoria do orçamento atualizada com sucesso.',
                'data' => $budgetCategory
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erro ao atualizar categoria do orçamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove category from budget
     */
    public function removeCategory(Budget $budget, BudgetCategory $budgetCategory): JsonResponse
    {
        $user = Auth::user();

        if ($budget->user_id !== $user->id || $budgetCategory->budget_id !== $budget->id) {
            return response()->json(['message' => 'Categoria do orçamento não encontrada.'], 404);
        }

        try {
            DB::beginTransaction();

            $budgetCategory->delete();
            $budget->updateCalculatedAmounts();

            DB::commit();

            return response()->json([
                'message' => 'Categoria removida do orçamento com sucesso.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erro ao remover categoria do orçamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get budget performance/stats
     */
    public function performance(Budget $budget): JsonResponse
    {
        $user = Auth::user();

        if ($budget->user_id !== $user->id) {
            return response()->json(['message' => 'Orçamento não encontrado.'], 404);
        }

        $budget->updateCalculatedAmounts();
        $stats = $budget->getStats();

        // Adicionar detalhes por categoria
        $categoryStats = $budget->budgetCategories->map(function ($budgetCategory) {
            $budgetCategory->updateCalculatedAmounts();
            return $budgetCategory->getStats();
        });

        return response()->json([
            'data' => [
                'budget_stats' => $stats,
                'category_stats' => $categoryStats,
                'alerts' => $budget->getAlerts(),
            ]
        ]);
    }

    /**
     * Create next period budget based on current
     */
    public function createNextPeriod(Budget $budget): JsonResponse
    {
        $user = Auth::user();

        if ($budget->user_id !== $user->id) {
            return response()->json(['message' => 'Orçamento não encontrado.'], 404);
        }

        try {
            DB::beginTransaction();

            $nextBudget = $budget->generateNextPeriod();

            DB::commit();

            return response()->json([
                'message' => 'Próximo período do orçamento criado com sucesso.',
                'data' => $nextBudget
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erro ao criar próximo período do orçamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}