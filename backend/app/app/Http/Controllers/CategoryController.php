<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = Category::where('user_id', $user->id)
            ->with(['parent', 'children']);

        // Filtros
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        // Ordenação
        $orderBy = $request->get('order_by', 'name');
        $orderDirection = $request->get('order_direction', 'asc');
        $query->orderBy($orderBy, $orderDirection);

        $categories = $query->paginate($request->get('per_page', 15));

        return response()->json($categories);
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
            'type' => 'required|in:income,expense',
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean',
        ]);

        // Verificar se o parent é do mesmo tipo
        if (!empty($validated['parent_id'])) {
            $parent = Category::find($validated['parent_id']);
            if ($parent && $parent->type !== $validated['type']) {
                return response()->json([
                    'message' => 'A categoria pai deve ser do mesmo tipo.',
                    'errors' => ['parent_id' => ['A categoria pai deve ser do mesmo tipo.']]
                ], 422);
            }
        }

        $validated['user_id'] = $user->id;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $category = Category::create($validated);
        $category->load(['parent', 'children']);

        return response()->json([
            'message' => 'Categoria criada com sucesso.',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category): JsonResponse
    {
        $user = Auth::user();

        if ($category->user_id !== $user->id) {
            return response()->json(['message' => 'Categoria não encontrada.'], 404);
        }

        $category->load(['parent', 'children', 'transactions' => function ($query) {
            $query->latest()->limit(10);
        }]);

        // Adicionar estatísticas
        $stats = $category->getStats();

        return response()->json([
            'data' => $category,
            'stats' => $stats
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $user = Auth::user();

        if ($category->user_id !== $user->id) {
            return response()->json(['message' => 'Categoria não encontrada.'], 404);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'in:income,expense',
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }),
            ],
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean',
        ]);

        // Verificar se o parent é do mesmo tipo
        if (!empty($validated['parent_id'])) {
            $parent = Category::find($validated['parent_id']);
            $newType = $validated['type'] ?? $category->type;
            if ($parent && $parent->type !== $newType) {
                return response()->json([
                    'message' => 'A categoria pai deve ser do mesmo tipo.',
                    'errors' => ['parent_id' => ['A categoria pai deve ser do mesmo tipo.']]
                ], 422);
            }
        }

        // Não permitir tornar uma categoria pai de si mesma
        if (!empty($validated['parent_id']) && $validated['parent_id'] == $category->id) {
            return response()->json([
                'message' => 'Uma categoria não pode ser pai de si mesma.',
                'errors' => ['parent_id' => ['Uma categoria não pode ser pai de si mesma.']]
            ], 422);
        }

        $category->update($validated);
        $category->load(['parent', 'children']);

        return response()->json([
            'message' => 'Categoria atualizada com sucesso.',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        $user = Auth::user();

        if ($category->user_id !== $user->id) {
            return response()->json(['message' => 'Categoria não encontrada.'], 404);
        }

        // Verificar se tem transações
        if ($category->transactions()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir uma categoria com transações.',
            ], 422);
        }

        // Verificar se tem subcategorias
        if ($category->children()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir uma categoria que possui subcategorias.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Categoria excluída com sucesso.'
        ]);
    }

    /**
     * Get category statistics
     */
    public function stats(Category $category): JsonResponse
    {
        $user = Auth::user();

        if ($category->user_id !== $user->id) {
            return response()->json(['message' => 'Categoria não encontrada.'], 404);
        }

        $stats = $category->getStats();

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Get top categories by usage
     */
    public function topCategories(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'type' => 'nullable|in:income,expense',
            'limit' => 'integer|min:1|max:50',
            'period' => 'nullable|in:week,month,quarter,year',
        ]);

        $query = Category::where('user_id', $user->id);

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        // Adicionar contagem de transações no período
        $query->withCount(['transactions' => function ($query) use ($validated) {
            $query->where('status', 'completed');
            
            if (!empty($validated['period'])) {
                $date = match($validated['period']) {
                    'week' => now()->subWeek(),
                    'month' => now()->subMonth(),
                    'quarter' => now()->subQuarter(),
                    'year' => now()->subYear(),
                    default => null,
                };
                
                if ($date) {
                    $query->where('transaction_date', '>=', $date);
                }
            }
        }]);

        $categories = $query->orderByDesc('transactions_count')
            ->limit($validated['limit'] ?? 10)
            ->get();

        return response()->json([
            'data' => $categories
        ]);
    }
}