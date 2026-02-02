<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommonExpenseRequest;
use App\Http\Requests\ApplyCommonExpenseRequest;
use App\Http\Resources\CommonExpenseResource;
use App\Http\Resources\ExpenseResource;
use App\Models\CommonExpense;
use Illuminate\Http\Request;

class CommonExpenseController extends Controller
{
    /**
     * Display a listing of common expense templates.
     *
     * @OA\Get(
     *     path="/api/common-expenses",
     *     summary="Get all common expense templates",
     *     description="Retrieve user's common expense templates with filtering and search",
     *     tags={"Common Expenses"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/CommonExpense"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $query = $request->user()->commonExpenses()->with('category');

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search by name or note
        if ($request->has('search')) {
            $query->search($request->search);
        }

        $commonExpenses = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => CommonExpenseResource::collection($commonExpenses),
        ]);
    }

    /**
     * Store a newly created common expense template.
     *
     * @OA\Post(
     *     path="/api/common-expenses",
     *     summary="Create common expense template",
     *     description="Create a new common expense template",
     *     tags={"Common Expenses"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "category_id", "amount"},
     *             @OA\Property(property="name", type="string", example="Monthly Rent"),
     *             @OA\Property(property="category_id", type="integer", example=3),
     *             @OA\Property(property="amount", type="string", example="750.00"),
     *             @OA\Property(property="note", type="string", example="Apartment rent")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Template created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CommonExpenseRequest $request)
    {
        $commonExpense = $request->user()->commonExpenses()->create($request->validated());
        $commonExpense->load('category');

        return response()->json([
            'success' => true,
            'message' => __('messages.common_expense.created'),
            'data' => new CommonExpenseResource($commonExpense),
        ], 201);
    }

    /**
     * Display the specified common expense template.
     *
     * @OA\Get(
     *     path="/api/common-expenses/{id}",
     *     summary="Get common expense template by ID",
     *     description="Retrieve a specific common expense template",
     *     tags={"Common Expenses"},
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
     *             @OA\Property(property="data", ref="#/components/schemas/CommonExpense")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(CommonExpense $commonExpense)
    {
        $this->authorize('view', $commonExpense);
        $commonExpense->load('category');

        return response()->json([
            'success' => true,
            'data' => new CommonExpenseResource($commonExpense),
        ]);
    }

    /**
     * Update the specified common expense template.
     *
     * @OA\Put(
     *     path="/api/common-expenses/{id}",
     *     summary="Update common expense template",
     *     description="Update an existing common expense template",
     *     tags={"Common Expenses"},
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
     *             required={"name", "category_id", "amount"},
     *             @OA\Property(property="name", type="string", example="Monthly Rent"),
     *             @OA\Property(property="category_id", type="integer", example=3),
     *             @OA\Property(property="amount", type="string", example="800.00"),
     *             @OA\Property(property="note", type="string", example="Updated rent amount")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Template updated successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(CommonExpenseRequest $request, CommonExpense $commonExpense)
    {
        $this->authorize('update', $commonExpense);

        $commonExpense->update($request->validated());
        $commonExpense->load('category');

        return response()->json([
            'success' => true,
            'message' => __('messages.common_expense.updated'),
            'data' => new CommonExpenseResource($commonExpense),
        ]);
    }

    /**
     * Remove the specified common expense template.
     *
     * @OA\Delete(
     *     path="/api/common-expenses/{id}",
     *     summary="Delete common expense template",
     *     description="Delete a common expense template (soft delete)",
     *     tags={"Common Expenses"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Template deleted successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(CommonExpense $commonExpense)
    {
        $this->authorize('delete', $commonExpense);

        $commonExpense->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.common_expense.deleted'),
        ]);
    }

    /**
     * Apply a common expense template to create a real expense.
     *
     * @OA\Post(
     *     path="/api/common-expenses/{id}/apply",
     *     summary="Apply common expense template",
     *     description="Create a real expense from a template. The date is required. Amount and note can be overridden optionally.",
     *     tags={"Common Expenses"},
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
     *             required={"date"},
     *             @OA\Property(property="date", type="string", format="date", example="2026-02-01"),
     *             @OA\Property(property="amount", type="string", example="800.00"),
     *             @OA\Property(property="note", type="string", example="February rent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Expense created from template",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/Expense")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function apply(ApplyCommonExpenseRequest $request, CommonExpense $commonExpense)
    {
        $this->authorize('apply', $commonExpense);

        // Resolve values: use overrides from request, fallback to template defaults
        $amount = $request->validated('amount') ?? $commonExpense->amount;
        $note = $request->has('note') ? $request->validated('note') : $commonExpense->note;

        // Check if user has sufficient balance
        $balance = $request->user()->getOrCreateBalance();
        if ($balance->current_balance < $amount) {
            return response()->json([
                'success' => false,
                'message' => __('messages.expense.insufficient_balance', [
                    'balance' => number_format($balance->current_balance, 2),
                    'amount' => number_format($amount, 2),
                ]),
            ], 422);
        }

        // Create the actual expense (same logic as ExpenseController::store)
        $expense = $request->user()->expenses()->create([
            'category_id' => $commonExpense->category_id,
            'amount' => $amount,
            'date' => $request->validated('date'),
            'note' => $note,
        ]);
        $expense->load('category');

        // Deduct from user's balance (exact same pattern as ExpenseController::store)
        $categoryName = $expense->category?->name ?? 'Expense';
        $balance->deductMoney(
            $expense->amount,
            $expense->id,
            $expense->note ?? $categoryName
        );

        return response()->json([
            'success' => true,
            'message' => __('messages.common_expense.applied'),
            'data' => new ExpenseResource($expense),
        ], 201);
    }
}
