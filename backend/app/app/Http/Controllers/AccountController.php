<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = Account::where('user_id', $user->id);

        // Filtros
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->has('currency')) {
            $query->where('currency', $request->currency);
        }

        // Ordenação
        $orderBy = $request->get('order_by', 'name');
        $orderDirection = $request->get('order_direction', 'asc');
        $query->orderBy($orderBy, $orderDirection);

        $accounts = $query->paginate($request->get('per_page', 15));

        return response()->json($accounts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:checking,savings,credit_card,investment,cash',
            'initial_balance' => 'required|numeric',
            'account_number' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:255',
            'currency' => 'string|max:3',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['user_id'] = $user->id;
        $validated['current_balance'] = $validated['initial_balance'];
        $validated['currency'] = $validated['currency'] ?? 'BRL';
        $validated['is_active'] = $validated['is_active'] ?? true;

        $account = Account::create($validated);

        return response()->json([
            'message' => 'Conta criada com sucesso.',
            'data' => $account
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Account $account): JsonResponse
    {
        $user = Auth::user();

        if ($account->user_id !== $user->id) {
            return response()->json(['message' => 'Conta não encontrada.'], 404);
        }

        $account->load(['transactions' => function ($query) {
            $query->latest()->limit(10);
        }]);

        // Adicionar estatísticas
        $stats = $account->getStats();

        return response()->json([
            'data' => $account,
            'stats' => $stats
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Account $account): JsonResponse
    {
        $user = Auth::user();

        if ($account->user_id !== $user->id) {
            return response()->json(['message' => 'Conta não encontrada.'], 404);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'type' => 'in:checking,savings,credit_card,investment,cash',
            'account_number' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:255',
            'currency' => 'string|max:3',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Não permitir alterar initial_balance aqui
        // Isso deve ser feito através de uma operação específica

        $account->update($validated);

        return response()->json([
            'message' => 'Conta atualizada com sucesso.',
            'data' => $account
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account): JsonResponse
    {
        $user = Auth::user();

        if ($account->user_id !== $user->id) {
            return response()->json(['message' => 'Conta não encontrada.'], 404);
        }

        // Verificar se tem transações
        if ($account->transactions()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir uma conta com transações.',
            ], 422);
        }

        $account->delete();

        return response()->json([
            'message' => 'Conta excluída com sucesso.'
        ]);
    }

    /**
     * Transfer money between accounts
     */
    public function transfer(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'from_account_id' => 'required|integer|exists:accounts,id',
            'to_account_id' => 'required|integer|exists:accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $fromAccount = Account::find($validated['from_account_id']);
        $toAccount = Account::find($validated['to_account_id']);

        // Verificar se as contas pertencem ao usuário
        if ($fromAccount->user_id !== $user->id || $toAccount->user_id !== $user->id) {
            return response()->json(['message' => 'Conta não encontrada.'], 404);
        }

        // Verificar se a conta de origem tem saldo suficiente
        if (!$fromAccount->hasSufficientBalance($validated['amount'])) {
            return response()->json([
                'message' => 'Saldo insuficiente na conta de origem.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $transfer = $fromAccount->transferTo(
                $toAccount,
                $validated['amount'],
                $validated['description'] ?? 'Transferência entre contas',
                $validated['notes'] ?? null
            );

            DB::commit();

            return response()->json([
                'message' => 'Transferência realizada com sucesso.',
                'data' => $transfer
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erro ao realizar transferência.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get account balance history
     */
    public function balanceHistory(Request $request, Account $account): JsonResponse
    {
        $user = Auth::user();

        if ($account->user_id !== $user->id) {
            return response()->json(['message' => 'Conta não encontrada.'], 404);
        }

        $validated = $request->validate([
            'period' => 'nullable|in:week,month,quarter,year',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $history = $account->getBalanceHistory($validated);

        return response()->json([
            'data' => $history
        ]);
    }

    /**
     * Get account summary
     */
    public function summary(): JsonResponse
    {
        $user = Auth::user();

        $summary = [
            'total_accounts' => Account::where('user_id', $user->id)->where('is_active', true)->count(),
            'total_balance' => Account::where('user_id', $user->id)->sum('current_balance'),
            'by_type' => Account::where('user_id', $user->id)
                ->selectRaw('type, COUNT(*) as count, SUM(current_balance) as total_balance')
                ->groupBy('type')
                ->get(),
            'by_currency' => Account::where('user_id', $user->id)
                ->selectRaw('currency, COUNT(*) as count, SUM(current_balance) as total_balance')
                ->groupBy('currency')
                ->get(),
        ];

        return response()->json([
            'data' => $summary
        ]);
    }

    /**
     * Update account balance (manual adjustment)
     */
    public function adjustBalance(Request $request, Account $account): JsonResponse
    {
        $user = Auth::user();

        if ($account->user_id !== $user->id) {
            return response()->json(['message' => 'Conta não encontrada.'], 404);
        }

        $validated = $request->validate([
            'adjustment_amount' => 'required|numeric',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $oldBalance = $account->current_balance;
            $newBalance = $oldBalance + $validated['adjustment_amount'];

            $account->update(['current_balance' => $newBalance]);

            // Registrar o ajuste como uma transação especial
            $account->transactions()->create([
                'user_id' => $user->id,
                'amount' => abs($validated['adjustment_amount']),
                'type' => $validated['adjustment_amount'] > 0 ? 'income' : 'expense',
                'status' => 'completed',
                'description' => 'Ajuste de saldo: ' . $validated['reason'],
                'notes' => $validated['notes'],
                'transaction_date' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Saldo ajustado com sucesso.',
                'data' => [
                    'old_balance' => $oldBalance,
                    'adjustment' => $validated['adjustment_amount'],
                    'new_balance' => $newBalance,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erro ao ajustar saldo.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}