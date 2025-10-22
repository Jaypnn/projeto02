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
        
        $query = Transaction::query()
            ->where('transactions.user_id', $user->id)
            ->with(['account', 'category', 'destinationAccount']);

        // Normalização de filtros vindos do frontend
        $rawType = strtolower(trim((string) $request->query('type', '')));
        $typeMap = [
            'receita' => 'income', 'receitas' => 'income',
            'despesa' => 'expense', 'despesas' => 'expense',
            'transferencia' => 'transfer', 'transferências' => 'transfer', 'transferencias' => 'transfer',
        ];
        $type = $typeMap[$rawType] ?? ($rawType ?: null);
        $accountId = (int) $request->query('account_id', 0);
        $categoryId = (int) $request->query('category_id', 0);
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $minAmount = $request->query('min_amount');
        $maxAmount = $request->query('max_amount');
        $search = $request->query('search');

        // Filtros com when + colunas qualificadas
        $query->when($type === 'income', fn($q) => $q->where('transactions.type', 'income'))
              ->when($type === 'expense', fn($q) => $q->where('transactions.type', 'expense'))
              ->when($type === 'transfer', fn($q) => $q->where('transactions.type', 'transfer'))
              ->when($accountId > 0, fn($q) => $q->where('transactions.account_id', $accountId))
              ->when($categoryId > 0, fn($q) => $q->where('transactions.category_id', $categoryId))
              ->when($startDate, fn($q) => $q->where('transactions.transaction_date', '>=', $startDate))
              ->when($endDate, fn($q) => $q->where('transactions.transaction_date', '<=', $endDate))
              ->when($minAmount !== null && $minAmount !== '', fn($q) => $q->where('transactions.amount', '>=', $minAmount))
              ->when($maxAmount !== null && $maxAmount !== '', fn($q) => $q->where('transactions.amount', '<=', $maxAmount));

        // status removido: todas as transações são efetivas

        if ($request->has('is_recurring')) {
            $query->where('transactions.is_recurring', $request->boolean('is_recurring'));
        }

        // Busca por descrição
        if ($search) {
            $query->where('transactions.description', 'like', '%' . $search . '%');
        }

        // Ordenação com whitelist
    $allowedOrder = ['transaction_date', 'amount', 'description', 'created_at'];
        $orderBy = in_array($request->get('order_by'), $allowedOrder, true) ? $request->get('order_by') : 'transaction_date';
        $orderDirection = strtolower($request->get('order_direction')) === 'asc' ? 'asc' : 'desc';
    $query->orderBy('transactions.' . $orderBy, $orderDirection);

    $transactions = $query->paginate($request->get('per_page', 15))->withQueryString();

        return response()->json($transactions);
    }

    /**
     * Alias explícito para listar apenas transações do usuário autenticado.
     * Mantém a mesma resposta de index(), servindo em rota dedicada.
     */
    public function myIndex(Request $request): JsonResponse
    {
        return $this->index($request);
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
            // status removido
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
            // As categorias não possuem mais tipo; a compatibilidade é determinada apenas pelo tipo da transação.
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
        $validated['is_recurring'] = $validated['is_recurring'] ?? false;

        // Verificar saldo para despesas (todas as transações são efetivas)
        if ($validated['type'] === 'expense') {
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

            // Atualizar saldos sempre (status removido)
            $transaction->account->updateCurrentBalance();
            if ($transaction->destination_account_id) {
                $transaction->destinationAccount->updateCurrentBalance();
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

        // status removido: permitir edição normalmente

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
            // status removido
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

            // Guardar contas antigas para recálculo de saldo
            $oldAccountId = $transaction->account_id;
            $oldDestinationId = $transaction->destination_account_id;

            $transaction->update($validated);

            // Recalcular saldos de contas afetadas
            if ($oldAccountId) {
                $oldAcc = Account::find($oldAccountId);
                if ($oldAcc) { $oldAcc->updateCurrentBalance(); }
            }
            if ($oldDestinationId) {
                $oldDest = Account::find($oldDestinationId);
                if ($oldDest) { $oldDest->updateCurrentBalance(); }
            }
            // Recalcular novas contas
            $transaction->account->updateCurrentBalance();
            if ($transaction->destination_account_id) {
                $transaction->destinationAccount->updateCurrentBalance();
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

            // Atualizar saldos (status removido)
            $account = $transaction->account;
            $dest = $transaction->destinationAccount;

            $transaction->delete();

            if ($account) { $account->updateCurrentBalance(); }
            if ($dest) { $dest->updateCurrentBalance(); }

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
    // Métodos complete/cancel removidos: status não é mais usado

    /**
     * Cancel a transaction
     */
    

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

    $query = Transaction::where('user_id', $user->id);

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