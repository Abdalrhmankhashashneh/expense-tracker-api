<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\BalanceTransaction;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource with filtering and pagination.
     *
     * @OA\Get(
     *     path="/api/expenses",
     *     summary="Get all expenses",
     *     description="Retrieve expenses with filtering, search, and pagination",
     *     tags={"Expenses"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort_by", in="query", @OA\Schema(type="string", enum={"date", "amount"})),
     *     @OA\Parameter(name="sort_order", in="query", @OA\Schema(type="string", enum={"asc", "desc"})),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", default=20)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="expenses", type="array", @OA\Items(ref="#/components/schemas/Expense")),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="total_pages", type="integer"),
     *                     @OA\Property(property="total_expenses", type="integer"),
     *                     @OA\Property(property="per_page", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $query = $request->user()->expenses()->with('category');

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->inDateRange($request->date_from, $request->date_to);
        }

        // Search in notes
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $limit = $request->get('limit', 20);
        $expenses = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => [
                'expenses' => ExpenseResource::collection($expenses),
                'pagination' => [
                    'current_page' => $expenses->currentPage(),
                    'total_pages' => $expenses->lastPage(),
                    'total_expenses' => $expenses->total(),
                    'per_page' => $expenses->perPage(),
                ],
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/expenses",
     *     summary="Create new expense",
     *     description="Create a new expense record",
     *     tags={"Expenses"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category_id", "amount", "date"},
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="amount", type="string", example="150.00"),
     *             @OA\Property(property="date", type="string", format="date", example="2025-12-15"),
     *             @OA\Property(property="note", type="string", example="Grocery shopping")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Expense created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expense created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Expense")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(ExpenseRequest $request)
    {
        $expense = $request->user()->expenses()->create($request->validated());
        $expense->load('category');

        // Deduct from user's balance
        $balance = $request->user()->getOrCreateBalance();
        $categoryName = $expense->category?->name ?? 'Expense';
        $balance->deductMoney(
            $expense->amount,
            $expense->id,
            $expense->note ?? $categoryName
        );

        return response()->json([
            'success' => true,
            'message' => __('messages.expense.created'),
            'data' => new ExpenseResource($expense),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/expenses/{id}",
     *     summary="Get expense by ID",
     *     description="Retrieve a specific expense record",
     *     tags={"Expenses"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Expense")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(Expense $expense)
    {
        $this->authorize('view', $expense);
        $expense->load('category');

        return response()->json([
            'success' => true,
            'data' => new ExpenseResource($expense),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/expenses/{id}",
     *     summary="Update expense",
     *     description="Update an existing expense record",
     *     tags={"Expenses"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category_id", "amount", "date"},
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="amount", type="string", example="175.00"),
     *             @OA\Property(property="date", type="string", format="date", example="2025-12-15"),
     *             @OA\Property(property="note", type="string", example="Updated note")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Expense updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expense updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Expense")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(ExpenseRequest $request, Expense $expense)
    {
        $this->authorize('update', $expense);

        $oldAmount = $expense->amount;
        $expense->update($request->validated());
        $expense->load('category');

        // Adjust balance if amount changed
        $amountDiff = $expense->amount - $oldAmount;
        if ($amountDiff != 0) {
            $balance = $request->user()->getOrCreateBalance();
            $categoryName = $expense->category?->name ?? 'Expense';

            if ($amountDiff > 0) {
                // Expense increased - deduct more
                $balance->deductMoney($amountDiff, $expense->id, "Updated: {$categoryName}");
            } else {
                // Expense decreased - refund the difference
                $balance->refundMoney(abs($amountDiff), $expense->id, "Updated: {$categoryName}");
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.expense.updated'),
            'data' => new ExpenseResource($expense),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/expenses/{id}",
     *     summary="Delete expense",
     *     description="Delete an expense record (soft delete)",
     *     tags={"Expenses"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Expense deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expense deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(Expense $expense)
    {
        $this->authorize('delete', $expense);

        // Refund the amount to user's balance
        $user = $expense->user;
        $balance = $user->getOrCreateBalance();
        $categoryName = $expense->category?->name ?? 'Expense';
        $balance->refundMoney(
            $expense->amount,
            $expense->id,
            "Deleted: {$categoryName}"
        );

        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.expense.deleted'),
        ]);
    }

    /**
     * Get expense summary for a specific month.
     *
     * @OA\Get(
     *     path="/api/expenses/summary",
     *     summary="Get expense summary",
     *     description="Get total expenses and category breakdown for a specific month",
     *     tags={"Expenses"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         @OA\Schema(type="string", format="Y-m", example="2025-12")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="month", type="string", example="2025-12"),
     *                 @OA\Property(property="total_expenses", type="string", example="1500.00"),
     *                 @OA\Property(property="categories", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function summary(Request $request)
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = $request->get('month', now()->format('Y-m'));

        $expenses = $request->user()
            ->expenses()
            ->whereYear('date', '>=', substr($month, 0, 4))
            ->whereMonth('date', '>=', substr($month, 5, 2))
            ->whereYear('date', '<=', substr($month, 0, 4))
            ->whereMonth('date', '<=', substr($month, 5, 2))
            ->get();

        $totalExpenses = $expenses->sum('amount');
        $expenseCount = $expenses->count();
        $daysInMonth = now()->parse($month)->daysInMonth;
        $averagePerDay = $expenseCount > 0 ? $totalExpenses / $daysInMonth : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'total_expenses' => number_format($totalExpenses, 2, '.', ''),
                'expense_count' => $expenseCount,
                'average_per_day' => number_format($averagePerDay, 2, '.', ''),
            ],
        ]);
    }
}
