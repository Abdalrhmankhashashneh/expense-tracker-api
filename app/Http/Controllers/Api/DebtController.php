<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Models\DebtPayment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Debts",
 *     description="API Endpoints for managing debts owed by others"
 * )
 */
class DebtController extends Controller
{
    /**
     * Display a listing of debts.
     *
     * @OA\Get(
     *     path="/api/debts",
     *     summary="Get all debts",
     *     tags={"Debts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"pending", "in_progress", "completed", "overdue", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="priority",
     *         in="query",
     *         description="Filter by priority",
     *         @OA\Schema(type="string", enum={"1", "2", "3", "4", "5"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Debts retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->debts()->with('payments')->latest();

        // Filter by status
        if ($request->has('status') && in_array($request->status, Debt::STATUSES)) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->has('priority') && in_array($request->priority, Debt::PRIORITIES)) {
            $query->where('priority', $request->priority);
        }

        // Filter by payment type
        if ($request->has('payment_type') && in_array($request->payment_type, Debt::PAYMENT_TYPES)) {
            $query->where('payment_type', $request->payment_type);
        }

        $debts = $query->get()->map(function ($debt) {
            return $this->formatDebt($debt);
        });

        return response()->json([
            'success' => true,
            'data' => $debts,
        ]);
    }

    /**
     * Store a newly created debt.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'debtor_name' => ['required', 'string', 'max:255'],
            'debtor_phone' => ['nullable', 'string', 'max:20'],
            'debtor_email' => ['nullable', 'email', 'max:255'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'priority' => ['required', Rule::in(Debt::PRIORITIES)],
            'payment_type' => ['required', Rule::in(Debt::PAYMENT_TYPES)],
            'installment_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $debt = $request->user()->debts()->create([
            'debtor_name' => $validated['debtor_name'],
            'debtor_phone' => $validated['debtor_phone'] ?? null,
            'debtor_email' => $validated['debtor_email'] ?? null,
            'total_amount' => $validated['total_amount'],
            'paid_amount' => 0,
            'priority' => $validated['priority'],
            'payment_type' => $validated['payment_type'],
            'installment_amount' => $validated['installment_amount'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'start_date' => $validated['start_date'] ?? now(),
            'status' => Debt::STATUS_PENDING,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.debt.created'),
            'data' => $this->formatDebt($debt),
        ], 201);
    }

    /**
     * Display the specified debt.
     */
    public function show(Request $request, Debt $debt): JsonResponse
    {
        // Ensure user owns this debt
        if ($debt->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        $debt->load('payments');

        return response()->json([
            'success' => true,
            'data' => $this->formatDebt($debt),
        ]);
    }

    /**
     * Update the specified debt.
     */
    public function update(Request $request, Debt $debt): JsonResponse
    {
        // Ensure user owns this debt
        if ($debt->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        $validated = $request->validate([
            'debtor_name' => ['sometimes', 'string', 'max:255'],
            'debtor_phone' => ['nullable', 'string', 'max:20'],
            'debtor_email' => ['nullable', 'email', 'max:255'],
            'total_amount' => ['sometimes', 'numeric', 'min:0.01'],
            'priority' => ['sometimes', Rule::in(Debt::PRIORITIES)],
            'payment_type' => ['sometimes', Rule::in(Debt::PAYMENT_TYPES)],
            'installment_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::in(Debt::STATUSES)],
            'notes' => ['nullable', 'string'],
        ]);

        $debt->update($validated);

        return response()->json([
            'success' => true,
            'message' => __('messages.debt.updated'),
            'data' => $this->formatDebt($debt->fresh()),
        ]);
    }

    /**
     * Remove the specified debt.
     */
    public function destroy(Request $request, Debt $debt): JsonResponse
    {
        // Ensure user owns this debt
        if ($debt->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        // Delete related payments first
        $debt->payments()->delete();
        $debt->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.debt.deleted'),
        ]);
    }

    /**
     * Record a payment for a debt.
     */
    public function recordPayment(Request $request, Debt $debt): JsonResponse
    {
        // Ensure user owns this debt
        if ($debt->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', Rule::in(DebtPayment::getPaymentMethods())],
            'notes' => ['nullable', 'string'],
            'add_to_balance' => ['nullable', 'boolean'],
        ]);

        // Check if payment exceeds remaining amount
        $remainingAmount = $debt->remaining_amount;
        if ($validated['amount'] > $remainingAmount) {
            return response()->json([
                'success' => false,
                'message' => __('messages.debt.payment_exceeds_remaining'),
            ], 422);
        }

        $balanceTransactionId = null;

        // Optionally add to balance
        if (!empty($validated['add_to_balance'])) {
            $balance = $request->user()->getOrCreateBalance();
            $transaction = $balance->addMoney(
                $validated['amount'],
                'debt_payment',
                "Debt payment from {$debt->debtor_name}"
            );
            $balanceTransactionId = $transaction->id;
        }

        // Record the payment
        $payment = $debt->recordPayment(
            $validated['amount'],
            $validated['payment_date'],
            $validated['payment_method'] ?? DebtPayment::METHOD_CASH,
            $validated['notes'] ?? null,
            $balanceTransactionId
        );

        // Add user_id to the payment
        $payment->update(['user_id' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'message' => __('messages.debt.payment_recorded'),
            'data' => [
                'payment' => $payment,
                'debt' => $this->formatDebt($debt->fresh(['payments'])),
            ],
        ], 201);
    }

    /**
     * Get payment history for a debt.
     */
    public function payments(Request $request, Debt $debt): JsonResponse
    {
        // Ensure user owns this debt
        if ($debt->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        $payments = $debt->payments()->latest('payment_date')->get();

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    /**
     * Get debt statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $debts = $request->user()->debts;

        $totalOwed = $debts->sum('total_amount');
        $totalPaid = $debts->sum('paid_amount');
        $totalRemaining = $totalOwed - $totalPaid;

        $byStatus = $debts->groupBy('status')->map->count();
        $byPriority = $debts->groupBy('priority')->map->count();

        $overdueDebts = $debts->filter(function ($debt) {
            return $debt->due_date && $debt->due_date < now() && $debt->status !== Debt::STATUS_COMPLETED;
        })->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_owed' => $totalOwed,
                'total_paid' => $totalPaid,
                'total_remaining' => $totalRemaining,
                'overall_progress' => $totalOwed > 0 ? round(($totalPaid / $totalOwed) * 100, 2) : 0,
                'total_debts' => $debts->count(),
                'overdue_debts' => $overdueDebts,
                'by_status' => $byStatus,
                'by_priority' => $byPriority,
            ],
        ]);
    }

    /**
     * Format debt for response.
     */
    private function formatDebt(Debt $debt): array
    {
        return [
            'id' => $debt->id,
            'debtor_name' => $debt->debtor_name,
            'debtor_phone' => $debt->debtor_phone,
            'debtor_email' => $debt->debtor_email,
            'total_amount' => $debt->total_amount,
            'paid_amount' => $debt->paid_amount,
            'remaining_amount' => $debt->remaining_amount,
            'progress_percentage' => $debt->progress_percentage,
            'priority' => $debt->priority,
            'payment_type' => $debt->payment_type,
            'installment_amount' => $debt->installment_amount,
            'due_date' => $debt->due_date?->format('Y-m-d'),
            'start_date' => $debt->start_date?->format('Y-m-d'),
            'status' => $debt->status,
            'notes' => $debt->notes,
            'is_completed' => $debt->isCompleted(),
            'is_overdue' => $debt->isOverdue(),
            'payments' => $debt->relationLoaded('payments') ? $debt->payments : [],
            'created_at' => $debt->created_at,
            'updated_at' => $debt->updated_at,
        ];
    }
}
