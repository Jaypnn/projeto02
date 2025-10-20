<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = Transaction::where('user_id', $user->id)
            ->with(['account', 'category', 'destinationAccount']);

        // Filtros
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('start_date')) {
            $query->where('transaction_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('transaction_date', '<=', $request->end_date);
        }

        if ($request->has('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }

        if ($request->has('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        if ($request->has('is_recurring')) {
            $query->where('is_recurring', $request->boolean('is_recurring'));
        }

        // Busca por descrição
        if ($request->has('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        // Ordenação
        $orderBy = $request->get('order_by', 'transaction_date');
        $orderDirection = $request->get('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        $transactions = $query->paginate($request->get('per_page', 15));

        return response()->json($transactions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
            'destination_account_id' => [
                'nullable',
                'integer',
                'different:account_id',
                Rule::exists('accounts', 'id')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:income,expense,transfer',
            'status' => 'in:pending,completed,canceled',
            'description' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'reference' => 'nullable|string|max:100',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|in:daily,weekly,monthly,quarterly,yearly',
            'recurring_end_date' => 'nullable|date|after:transaction_date',
        ]);

        // Validações específicas por tipo
        if ($validated['type'] === 'transfer') {
            if (empty($validated['destination_account_id'])) {
                return response()->json([
                    'message' => 'Conta de destino é obrigatória para transferências.',
                    'errors' => ['destination_account_id' => ['Conta de destino é obrigatória para transferências.']]
                ], 422);
            }
            $validated['category_id'] = null; // Transferências não têm categoria
        } else {
            if (empty($validated['category_id'])) {
                return response()->json([
                    'message' => 'Categoria é obrigatória para receitas e despesas.',
                    'errors' => ['category_id' => ['Categoria é obrigatória para receitas e despesas.']]
                ], 422);
            }
            
            // Verificar se o tipo da categoria é compatível
            $category = Category::find($validated['category_id']);
            if ($category && $category->type !== $validated['type']) {
                return response()->json([
                    'message' => 'Tipo da categoria incompatível com o tipo da transação.',
                    'errors' => ['category_id' => ['Tipo da categoria incompatível com o tipo da transação.']]
                ], 422);
            }
        }

        // Validações de recorrência
        if ($validated['is_recurring'] ?? false) {
            if (empty($validated['recurring_frequency'])) {
                return response()->json([
                    'message' => 'Frequência de recorrência é obrigatória.',
                    'errors' => ['recurring_frequency' => ['Frequência de recorrência é obrigatória.']]
                ], 422);
            }
        }

        $validated['user_id'] = $user->id;
        $validated['status'] = $validated['status'] ?? 'completed';
        $validated['is_recurring'] = $validated['is_recurring'] ?? false;

        // Verificar saldo para despesas se for completada imediatamente
        if ($validated['type'] === 'expense' && $validated['status'] === 'completed') {
            $account = Account::find($validated['account_id']);
            if (!$account->hasSufficientBalance($validated['amount'])) {
                return response()->json([
                    'message' => 'Saldo insuficiente na conta.',
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $transaction = Transaction::create($validated);

            // Atualizar saldo da conta se a transação for completada
            if ($transaction->status === 'completed') {
                $transaction->account->updateBalance();
                
                if ($transaction->destination_account_id) {
                    $transaction->destinationAccount->updateBalance();
                }
            }

            $transaction->load(['account', 'category', 'destinationAccount']);

            DB::commit();

            return response()->json([
                'message' => 'Transação criada com sucesso.',
                'data' => $transaction
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erro ao criar transação.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction): JsonResponse
    {
        $user = Auth::user();

        if ($transaction->user_id !== $user->id) {
            return response()->json(['message' => 'Transação não encontrada.'], 404);
        }

        $transaction->load(['account', 'category', 'destinationAccount']);

        return response()->json([
            'data' => $transaction
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $user = Auth::user();

        if ($transaction->user_id !== $user->id) {
            return response()->json(['message' => 'Transação não encontrada.'], 404);
        }

        // Não permitir editar transação completada (apenas cancelar)
        if ($transaction->status === 'completed') {
            return response()->json([
                'message' => 'Não é possível editar uma transação completada.',
            ], 422);
        }

        $validated = $request->validate([
            'account_id' => [
                'integer',
                Rule::exists('accounts', 'id')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
            'destination_account_id' => [
                'nullable',
                'integer',
                'different:account_id',
                Rule::exists('accounts', 'id')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
            'amount' => 'numeric|min:0.01',
            'type' => 'in:income,expense,transfer',
            'status' => 'in:pending,completed,canceled',
            'description' => 'string|max:255',
            'transaction_date' => 'date',
            'notes' => 'nullable|string|max:1000',
            'reference' => 'nullable|string|max:100',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|in:daily,weekly,monthly,quarterly,yearly',
            'recurring_end_date' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $transaction->status;
            $transaction->update($validated);

            // Se mudou para completed, atualizar saldos
            if ($transaction->status === 'completed' && $oldStatus !== 'completed') {
                $transaction->account->updateBalance();
                
                if ($transaction->destination_account_id) {
                    $transaction->destinationAccount->updateBalance();
                }
            }

            $transaction->load(['account', 'category', 'destinationAccount']);

            DB::commit();

            return response()->json([
                'message' => 'Transação atualizada com sucesso.',
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erro ao atualizar transação.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        $user = Auth::user();

        if ($transaction->user_id !== $user->id) {
            return response()->json(['message' => 'Transação não encontrada.'], 404);
        }

        try {
            DB::beginTransaction();

            // Se a transação estava completed, reverter os saldos
            if ($transaction->status === 'completed') {
                $transaction->status = 'canceled'; // Temporário para o cálculo
                $transaction->account->updateBalance();
                
                if ($transaction->destination_account_id) {
                    $transaction->destinationAccount->updateBalance();
                }
            }

            $transaction->delete();

            DB::commit();

            return response()->json([
                'message' => 'Transação excluída com sucesso.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erro ao excluir transação.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete a pending transaction
     */
    public function complete(Transaction $transaction): JsonResponse
    {
        $user = Auth::user();

        if ($transaction->user_id !== $user->id) {
            return response()->json(['message' => 'Transação não encontrada.'], 404);
        }

        if ($transaction->status !== 'pending') {
            return response()->json([
                'message' => 'Apenas transações pendentes podem ser completadas.',
            ], 422);
        }

        // Verificar saldo para despesas
        if ($transaction->type === 'expense') {
            if (!$transaction->account->hasSufficientBalance($transaction->amount)) {
                return response()->json([
                    'message' => 'Saldo insuficiente na conta.',
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $transaction->update(['status' => 'completed']);
            
            // Atualizar saldos
            $transaction->account->updateBalance();
            
            if ($transaction->destination_account_id) {
                $transaction->destinationAccount->updateBalance();
            }

            DB::commit();

            return response()->json([
                'message' => 'Transação completada com sucesso.',
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erro ao completar transação.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a transaction
     */
    public function cancel(Transaction $transaction): JsonResponse
    {
        $user = Auth::user();

        if ($transaction->user_id !== $user->id) {
            return response()->json(['message' => 'Transação não encontrada.'], 404);
        }

        if ($transaction->status === 'canceled') {
            return response()->json([
                'message' => 'Transação já está cancelada.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $wasCompleted = $transaction->status === 'completed';
            
            $transaction->update(['status' => 'canceled']);
            
            // Se estava completada, reverter os saldos
            if ($wasCompleted) {
                $transaction->account->updateBalance();
                
                if ($transaction->destination_account_id) {
                    $transaction->destinationAccount->updateBalance();
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Transação cancelada com sucesso.',
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erro ao cancelar transação.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction summary/stats
     */
    public function summary(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'period' => 'nullable|in:week,month,quarter,year',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = Transaction::where('user_id', $user->id)->where('status', 'completed');

        // Filtro por período
        if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
            $query->whereBetween('transaction_date', [$validated['start_date'], $validated['end_date']]);
        } elseif (!empty($validated['period'])) {
            $date = match($validated['period']) {
                'week' => now()->subWeek(),
                'month' => now()->subMonth(),
                'quarter' => now()->subQuarter(),
                'year' => now()->subYear(),
                default => now()->subMonth(),
            };
            $query->where('transaction_date', '>=', $date);
        }

        $summary = [
            'total_income' => (clone $query)->where('type', 'income')->sum('amount'),
            'total_expense' => (clone $query)->where('type', 'expense')->sum('amount'),
            'total_transfers' => (clone $query)->where('type', 'transfer')->sum('amount'),
            'net_income' => 0, // Será calculado abaixo
            'transaction_count' => $query->count(),
            'by_type' => $query->selectRaw('type, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('type')
                ->get(),
        ];

        $summary['net_income'] = $summary['total_income'] - $summary['total_expense'];

        return response()->json([
            'data' => $summary
        ]);
    }
}