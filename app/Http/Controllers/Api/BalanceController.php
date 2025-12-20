<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BalanceResource;
use App\Http\Resources\BalanceTransactionResource;
use App\Models\Balance;
use App\Models\BalanceTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Balance",
 *     description="API Endpoints for managing user balance and transactions"
 * )
 */
class BalanceController extends Controller
{
    /**
     * Get current user's balance.
     *
     * @OA\Get(
     *     path="/api/balance",
     *     summary="Get current balance",
     *     tags={"Balance"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Current balance retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Balance")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $balance = $request->user()->getOrCreateBalance();

        return response()->json([
            'success' => true,
            'data' => new BalanceResource($balance),
        ]);
    }

    /**
     * Add money to balance.
     *
     * @OA\Post(
     *     path="/api/balance/add",
     *     summary="Add money to balance",
     *     tags={"Balance"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount", "source"},
     *             @OA\Property(property="amount", type="number", format="float", example=500.00),
     *             @OA\Property(property="source", type="string", enum={"salary", "freelance", "gift", "investment", "refund", "transfer", "other"}, example="salary"),
     *             @OA\Property(property="description", type="string", nullable=true, example="Monthly salary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Money added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Money added successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction", ref="#/components/schemas/BalanceTransaction"),
     *                 @OA\Property(property="balance", ref="#/components/schemas/Balance")
     *             )
     *         )
     *     )
     * )
     */
    public function addMoney(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'source' => ['required', Rule::in(BalanceTransaction::SOURCES)],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $balance = $request->user()->getOrCreateBalance();
        $transaction = $balance->addMoney(
            $validated['amount'],
            $validated['source'],
            $validated['description'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => __('Money added successfully'),
            'data' => [
                'transaction' => new BalanceTransactionResource($transaction),
                'balance' => new BalanceResource($balance->fresh()),
            ],
        ], 201);
    }

    /**
     * Get balance transaction history.
     *
     * @OA\Get(
     *     path="/api/balance/transactions",
     *     summary="Get transaction history",
     *     tags={"Balance"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by transaction type",
     *         @OA\Schema(type="string", enum={"credit", "debit"})
     *     ),
     *     @OA\Parameter(
     *         name="source",
     *         in="query",
     *         description="Filter by source",
     *         @OA\Schema(type="string", enum={"salary", "freelance", "gift", "investment", "refund", "transfer", "other"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction history retrieved successfully"
     *     )
     * )
     */
    public function transactions(Request $request): JsonResponse
    {
        $query = $request->user()->balanceTransactions()->latest();

        // Filter by type
        if ($request->has('type') && in_array($request->type, ['credit', 'debit'])) {
            $query->where('type', $request->type);
        }

        // Filter by source
        if ($request->has('source') && in_array($request->source, BalanceTransaction::SOURCES)) {
            $query->where('source', $request->source);
        }

        $perPage = $request->get('per_page', 15);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => BalanceTransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Get available money sources.
     *
     * @OA\Get(
     *     path="/api/balance/sources",
     *     summary="Get available money sources",
     *     tags={"Balance"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Sources retrieved successfully"
     *     )
     * )
     */
    public function sources(): JsonResponse
    {
        $sources = collect(BalanceTransaction::SOURCES)->map(function ($source) {
            return [
                'value' => $source,
                'label' => [
                    'en' => match($source) {
                        'salary' => 'Salary',
                        'freelance' => 'Freelance',
                        'gift' => 'Gift',
                        'investment' => 'Investment',
                        'refund' => 'Refund',
                        'transfer' => 'Transfer',
                        'other' => 'Other',
                    },
                    'ar' => match($source) {
                        'salary' => 'راتب',
                        'freelance' => 'عمل حر',
                        'gift' => 'هدية',
                        'investment' => 'استثمار',
                        'refund' => 'استرداد',
                        'transfer' => 'تحويل',
                        'other' => 'أخرى',
                    },
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $sources,
        ]);
    }
}
