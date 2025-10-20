<?php

namespace App\Http\Controllers;

use App\Models\FinancialGoal;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FinancialGoalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = FinancialGoal::where('user_id', $user->id)
            ->with(['category']);

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('goal_type')) {
            $query->where('goal_type', $request->goal_type);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Busca por nome
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Ordenação
        $orderBy = $request->get('order_by', 'created_at');
        $orderDirection = $request->get('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        $goals = $query->paginate($request->get('per_page', 15));

        return response()->json($goals);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'target_amount' => 'required|numeric|min:0.01',
            'current_amount' => 'numeric|min:0',
            'target_date' => 'required|date|after:today',
            'priority' => 'in:low,medium,high',
            'goal_type' => 'required|in:savings,investment,purchase,debt_payment,emergency_fund',
            'monthly_contribution' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['user_id'] = $user->id;
        $validated['current_amount'] = $validated['current_amount'] ?? 0;
        $validated['priority'] = $validated['priority'] ?? 'medium';
        $validated['status'] = 'active';

        // Verificar se o valor atual não excede o valor alvo
        if ($validated['current_amount'] >= $validated['target_amount']) {
            return response()->json([
                'message' => 'O valor atual não pode ser maior ou igual ao valor alvo.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $goal = FinancialGoal::create($validated);
            $goal->load('category');

            DB::commit();

            return response()->json([
                'message' => 'Meta financeira criada com sucesso.',
                'data' => $goal
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erro ao criar meta financeira.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(FinancialGoal $financialGoal): JsonResponse
    {
        $user = Auth::user();

        if ($financialGoal->user_id !== $user->id) {
            return response()->json(['message' => 'Meta financeira não encontrada.'], 404);
        }

        $financialGoal->load(['category']);
        
        // Atualizar valores calculados
        $financialGoal->updateCurrentAmount();

        // Adicionar estatísticas e marcos
        $stats = $financialGoal->getStats();
        $milestones = $financialGoal->getMilestones();

        return response()->json([
            'data' => $financialGoal,
            'stats' => $stats,
            'milestones' => $milestones
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FinancialGoal $financialGoal): JsonResponse
    {
        $user = Auth::user();

        if ($financialGoal->user_id !== $user->id) {
            return response()->json(['message' => 'Meta financeira não encontrada.'], 404);
        }

        $validated = $request->validate([
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
            'name' => 'string|max:255',
            'description' => 'nullable|string|max:1000',
            'target_amount' => 'numeric|min:0.01',
            'target_date' => 'date',
            'priority' => 'in:low,medium,high',
            'status' => 'in:active,paused,completed,canceled',
            'goal_type' => 'in:savings,investment,purchase,debt_payment,emergency_fund',
            'monthly_contribution' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Não permitir reduzir target_amount abaixo do current_amount
        if (isset($validated['target_amount']) && $validated['target_amount'] < $financialGoal->current_amount) {
            return response()->json([
                'message' => 'O valor alvo não pode ser menor que o valor atual.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $financialGoal->update($validated);

            // Verificar se completou a meta com a atualização
            if ($financialGoal->isCompleted() && $financialGoal->status === 'active') {
                $financialGoal->update(['status' => 'completed']);
            }

            $financialGoal->load('category');

            DB::commit();

            return response()->json([
                'message' => 'Meta financeira atualizada com sucesso.',
                'data' => $financialGoal
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erro ao atualizar meta financeira.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FinancialGoal $financialGoal): JsonResponse
    {
        $user = Auth::user();

        if ($financialGoal->user_id !== $user->id) {
            return response()->json(['message' => 'Meta financeira não encontrada.'], 404);
        }

        $financialGoal->delete();

        return response()->json([
            'message' => 'Meta financeira excluída com sucesso.'
        ]);
    }

    /**
     * Add contribution to goal
     */
    public function addContribution(Request $request, FinancialGoal $financialGoal): JsonResponse
    {
        $user = Auth::user();

        if ($financialGoal->user_id !== $user->id) {
            return response()->json(['message' => 'Meta financeira não encontrada.'], 404);
        }

        if ($financialGoal->status !== 'active') {
            return response()->json([
                'message' => 'Apenas metas ativas podem receber contribuições.',
            ], 422);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        // Verificar se a contribuição não excede o valor restante
        $remainingAmount = $financialGoal->getRemainingAmount();
        if ($validated['amount'] > $remainingAmount) {
            return response()->json([
                'message' => "A contribuição não pode exceder o valor restante (R$ {$remainingAmount}).",
            ], 422);
        }

        try {
            DB::beginTransaction();

            $success = $financialGoal->addContribution(
                $validated['amount'],
                $validated['description'] ?? null
            );

            if (!$success) {
                throw new \Exception('Erro ao processar contribuição');
            }

            $financialGoal->refresh();

            DB::commit();

            return response()->json([
                'message' => 'Contribuição adicionada com sucesso.',
                'data' => $financialGoal,
                'completed' => $financialGoal->isCompleted()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erro ao adicionar contribuição.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get goal progress and statistics
     */
    public function progress(FinancialGoal $financialGoal): JsonResponse
    {
        $user = Auth::user();

        if ($financialGoal->user_id !== $user->id) {
            return response()->json(['message' => 'Meta financeira não encontrada.'], 404);
        }

        $financialGoal->updateCurrentAmount();
        
        $stats = $financialGoal->getStats();
        $milestones = $financialGoal->getMilestones();
        $projectedCompletion = $financialGoal->getProjectedCompletionDate();

        return response()->json([
            'data' => [
                'goal' => $financialGoal,
                'stats' => $stats,
                'milestones' => $milestones,
                'projected_completion' => $projectedCompletion,
                'needs_adjustment' => $financialGoal->needsAdjustment(),
                'suggested_monthly' => $financialGoal->getSuggestedMonthlyContribution(),
            ]
        ]);
    }

    /**
     * Get goals summary/dashboard
     */
    public function summary(): JsonResponse
    {
        $user = Auth::user();

        $summary = [
            'total_goals' => FinancialGoal::where('user_id', $user->id)->count(),
            'active_goals' => FinancialGoal::where('user_id', $user->id)->active()->count(),
            'completed_goals' => FinancialGoal::where('user_id', $user->id)->completed()->count(),
            'overdue_goals' => FinancialGoal::where('user_id', $user->id)->overdue()->count(),
            'total_target' => FinancialGoal::where('user_id', $user->id)->active()->sum('target_amount'),
            'total_saved' => FinancialGoal::where('user_id', $user->id)->active()->sum('current_amount'),
            'by_type' => FinancialGoal::where('user_id', $user->id)
                ->selectRaw('goal_type, COUNT(*) as count, SUM(target_amount) as total_target, SUM(current_amount) as total_saved')
                ->groupBy('goal_type')
                ->get(),
            'by_priority' => FinancialGoal::where('user_id', $user->id)
                ->active()
                ->selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->get(),
        ];

        $summary['total_remaining'] = $summary['total_target'] - $summary['total_saved'];
        $summary['overall_progress'] = $summary['total_target'] > 0 
            ? ($summary['total_saved'] / $summary['total_target']) * 100 
            : 0;

        return response()->json([
            'data' => $summary
        ]);
    }

    /**
     * Get upcoming goals (ending soon)
     */
    public function upcoming(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'days' => 'integer|min:1|max:365',
        ]);

        $days = $validated['days'] ?? 30;

        $goals = FinancialGoal::where('user_id', $user->id)
            ->upcoming($days)
            ->with('category')
            ->get();

        return response()->json([
            'data' => $goals
        ]);
    }

    /**
     * Pause/Resume goal
     */
    public function toggleStatus(FinancialGoal $financialGoal): JsonResponse
    {
        $user = Auth::user();

        if ($financialGoal->user_id !== $user->id) {
            return response()->json(['message' => 'Meta financeira não encontrada.'], 404);
        }

        if (!in_array($financialGoal->status, ['active', 'paused'])) {
            return response()->json([
                'message' => 'Apenas metas ativas ou pausadas podem ter o status alterado.',
            ], 422);
        }

        $newStatus = $financialGoal->status === 'active' ? 'paused' : 'active';
        $financialGoal->update(['status' => $newStatus]);

        $message = $newStatus === 'paused' ? 'Meta pausada com sucesso.' : 'Meta reativada com sucesso.';

        return response()->json([
            'message' => $message,
            'data' => $financialGoal
        ]);
    }
}