<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IncomeRequest;
use App\Http\Resources\IncomeResource;
use App\Models\Income;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    /**
     * Display a listing of the resource (income history).
     *
     * @OA\Get(
     *     path="/api/income",
     *     summary="Get income history",
     *     description="Retrieve all income records for the authenticated user",
     *     tags={"Income"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Income"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $incomes = $request->user()
            ->incomes()
            ->latest('effective_from')
            ->get();

        return response()->json([
            'success' => true,
            'data' => IncomeResource::collection($incomes),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/income",
     *     summary="Create new income",
     *     description="Create a new income record",
     *     tags={"Income"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"monthly_amount", "effective_from"},
     *             @OA\Property(property="monthly_amount", type="string", example="5000.00"),
     *             @OA\Property(property="effective_from", type="string", format="date", example="2025-12-01")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Income created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Income created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Income")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(IncomeRequest $request)
    {
        $income = $request->user()->incomes()->create([
            'monthly_amount' => $request->monthly_amount,
            'effective_from' => $request->effective_from,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.income.created'),
            'data' => new IncomeResource($income),
        ], 201);
    }

    /**
     * Display the current active income.
     *
     * @OA\Get(
     *     path="/api/income/current",
     *     summary="Get current income",
     *     description="Retrieve the currently active income record",
     *     tags={"Income"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Income"),
     *             @OA\Property(property="message", type="string", example="No income record found")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function current(Request $request)
    {
        $income = $request->user()->currentIncome;

        if (!$income) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => __('messages.income.no_income'),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new IncomeResource($income),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/income/{id}",
     *     summary="Get income by ID",
     *     description="Retrieve a specific income record",
     *     tags={"Income"},
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
     *             @OA\Property(property="data", ref="#/components/schemas/Income")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(Income $income)
    {
        $this->authorize('view', $income);

        return response()->json([
            'success' => true,
            'data' => new IncomeResource($income),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/income/{id}",
     *     summary="Update income",
     *     description="Update an existing income record",
     *     tags={"Income"},
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
     *             required={"monthly_amount", "effective_from"},
     *             @OA\Property(property="monthly_amount", type="string", example="5500.00"),
     *             @OA\Property(property="effective_from", type="string", format="date", example="2025-12-01")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Income updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Income updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Income")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(IncomeRequest $request, Income $income)
    {
        $this->authorize('update', $income);

        $income->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => __('messages.income.updated'),
            'data' => new IncomeResource($income),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/income/{id}",
     *     summary="Delete income",
     *     description="Delete an income record",
     *     tags={"Income"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Income deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Income deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(Income $income)
    {
        $this->authorize('delete', $income);

        $income->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.income.deleted'),
        ]);
    }
}
