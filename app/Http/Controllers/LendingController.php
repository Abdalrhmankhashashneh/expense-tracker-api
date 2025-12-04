<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Lending;
use App\Models\LendingPayment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LendingController extends Controller
{
    /**
     * Get all lendings for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $query = Lending::where('user_id', auth()->id())
            ->with('payments')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter overdue
        if ($request->has('overdue') && $request->overdue === 'true') {
            $query->overdue();
        }

        $lendings = $query->get();

        // Calculate summary
        $summary = [
            'total_lent' => Lending::where('user_id', auth()->id())->sum('amount'),
            'total_pending' => Lending::where('user_id', auth()->id())
                ->whereIn('status', ['pending', 'partial'])
                ->sum('remaining_amount'),
            'total_received' => Lending::where('user_id', auth()->id())->sum('amount') -
                Lending::where('user_id', auth()->id())->sum('remaining_amount'),
            'overdue_count' => Lending::where('user_id', auth()->id())->overdue()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'lendings' => $lendings,
                'summary' => $summary,
            ],
        ]);
    }

    /**
     * Store a new lending
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'borrower_name' => 'required|string|max:255',
            'borrower_phone' => 'nullable|string|max:50',
            'borrower_email' => 'nullable|email|max:255',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'description' => 'nullable|string|max:1000',
            'lending_date' => 'required|date',
            'expected_return_date' => 'nullable|date|after_or_equal:lending_date',
            'notes' => 'nullable|string|max:2000',
            'deduct_from_balance' => 'nullable|boolean',
        ]);

        $lending = DB::transaction(function () use ($validated) {
            $lending = Lending::create([
                'user_id' => auth()->id(),
                'borrower_name' => $validated['borrower_name'],
                'borrower_phone' => $validated['borrower_phone'] ?? null,
                'borrower_email' => $validated['borrower_email'] ?? null,
                'amount' => $validated['amount'],
                'remaining_amount' => $validated['amount'],
                'currency' => $validated['currency'] ?? 'USD',
                'description' => $validated['description'] ?? null,
                'lending_date' => $validated['lending_date'],
                'expected_return_date' => $validated['expected_return_date'] ?? null,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Deduct from balance if requested (default: true)
            $deductFromBalance = $validated['deduct_from_balance'] ?? true;
            if ($deductFromBalance) {
                $balance = Balance::firstOrCreate(
                    ['user_id' => auth()->id()],
                    ['current_balance' => 0]
                );
                $balance->deductForLending(
                    (float) $validated['amount'],
                    $lending->id,
                    $validated['borrower_name']
                );
            }

            return $lending;
        });

        $lending->load('payments');

        return response()->json([
            'success' => true,
            'message' => 'Lending recorded successfully',
            'data' => $lending,
        ], 201);
    }

    /**
     * Get a specific lending
     */
    public function show(Lending $lending): JsonResponse
    {
        // Check ownership
        if ($lending->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $lending->load('payments');

        return response()->json([
            'success' => true,
            'data' => $lending,
        ]);
    }

    /**
     * Update a lending
     */
    public function update(Request $request, Lending $lending): JsonResponse
    {
        // Check ownership
        if ($lending->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'borrower_name' => 'sometimes|required|string|max:255',
            'borrower_phone' => 'nullable|string|max:50',
            'borrower_email' => 'nullable|email|max:255',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'description' => 'nullable|string|max:1000',
            'lending_date' => 'sometimes|required|date',
            'expected_return_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'status' => 'sometimes|in:pending,partial,paid,forgiven',
        ]);

        // If amount is changed, recalculate remaining
        if (isset($validated['amount']) && $validated['amount'] != $lending->amount) {
            $totalReceived = (float) $lending->amount - (float) $lending->remaining_amount;
            $validated['remaining_amount'] = max(0, $validated['amount'] - $totalReceived);
        }

        $lending->update($validated);

        // Update status based on remaining amount
        if (!isset($validated['status'])) {
            $lending->updateStatus();
        }

        $lending->load('payments');

        return response()->json([
            'success' => true,
            'message' => 'Lending updated successfully',
            'data' => $lending,
        ]);
    }

    /**
     * Delete a lending
     */
    public function destroy(Lending $lending): JsonResponse
    {
        // Check ownership
        if ($lending->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        DB::transaction(function () use ($lending) {
            // Refund the original lending amount to balance (money comes back since lending is cancelled)
            $balance = Balance::where('user_id', auth()->id())->first();
            if ($balance) {
                // Only refund if there was a balance deduction
                $balance->refundLending(
                    (float) $lending->amount,
                    $lending->id,
                    $lending->borrower_name
                );
            }

            $lending->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Lending deleted successfully',
        ]);
    }

    /**
     * Record a payment received for a lending
     */
    public function recordPayment(Request $request, Lending $lending): JsonResponse
    {
        // Check ownership
        if ($lending->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $lending->remaining_amount,
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|in:cash,bank_transfer,mobile_payment,check,other',
            'notes' => 'nullable|string|max:1000',
            'add_to_balance' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($lending, $validated) {
            // Create payment record
            LendingPayment::create([
                'lending_id' => $lending->id,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update remaining amount
            $lending->remaining_amount = (float) $lending->remaining_amount - $validated['amount'];
            $lending->save();

            // Update status
            $lending->updateStatus();

            // Add to balance if requested (default: true)
            $addToBalance = $validated['add_to_balance'] ?? true;
            if ($addToBalance) {
                $balance = Balance::firstOrCreate(
                    ['user_id' => auth()->id()],
                    ['current_balance' => 0]
                );
                $balance->addLendingReturn(
                    (float) $validated['amount'],
                    $lending->id,
                    $lending->borrower_name
                );
            }
        });

        $lending->load('payments');

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'data' => $lending,
        ]);
    }

    /**
     * Forgive a lending (mark as forgiven)
     */
    public function forgive(Lending $lending): JsonResponse
    {
        // Check ownership
        if ($lending->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $lending->status = 'forgiven';
        $lending->remaining_amount = 0;
        $lending->save();

        $lending->load('payments');

        return response()->json([
            'success' => true,
            'message' => 'Lending forgiven successfully',
            'data' => $lending,
        ]);
    }

    /**
     * Get payment history for a lending
     */
    public function getPayments(Lending $lending): JsonResponse
    {
        // Check ownership
        if ($lending->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $payments = $lending->payments()->orderBy('payment_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    /**
     * Delete a payment
     */
    public function deletePayment(Lending $lending, LendingPayment $payment): JsonResponse
    {
        // Check ownership
        if ($lending->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Ensure payment belongs to lending
        if ($payment->lending_id !== $lending->id) {
            return response()->json([
                'success' => false,
                'message' => 'Payment does not belong to this lending',
            ], 400);
        }

        DB::transaction(function () use ($lending, $payment) {
            // Add amount back to remaining
            $lending->remaining_amount = (float) $lending->remaining_amount + (float) $payment->amount;
            $lending->save();

            // Delete payment
            $payment->delete();

            // Update status
            $lending->updateStatus();
        });

        $lending->load('payments');

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully',
            'data' => $lending,
        ]);
    }
}
