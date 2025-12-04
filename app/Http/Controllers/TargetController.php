<?php

namespace App\Http\Controllers;

use App\Models\Target;
use App\Models\Balance;
use App\Models\BalanceTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TargetController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/targets",
     *     summary="Get all targets (wishlist)",
     *     tags={"Targets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of targets")
     * )
     */
    public function index(): JsonResponse
    {
        $targets = Target::where('user_id', Auth::id())
                         ->where('status', 'active')
                         ->orderBy('target_amount', 'asc')
                         ->get();

        // Get user balance
        $balance = Balance::where('user_id', Auth::id())->first();
        $currentBalance = $balance ? $balance->current_balance : 0;

        // Separate affordable and not affordable
        $affordable = $targets->filter(fn($t) => $t->can_afford)->values();
        $notAffordable = $targets->filter(fn($t) => !$t->can_afford)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'targets' => $targets,
                'affordable' => $affordable,
                'not_affordable' => $notAffordable,
                'current_balance' => $currentBalance,
                'total_count' => $targets->count(),
                'affordable_count' => $affordable->count(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/targets",
     *     summary="Create a new target",
     *     tags={"Targets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "price"},
     *             @OA\Property(property="name", type="string", example="New iPhone"),
     *             @OA\Property(property="price", type="number", example=1200.00),
     *             @OA\Property(property="description", type="string", example="Latest model"),
     *             @OA\Property(property="image_url", type="string", example="https://example.com/iphone.jpg"),
     *             @OA\Property(property="priority", type="string", enum={"low", "medium", "high"}, example="high")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Target created successfully")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:1000',
            'image_url' => 'nullable|url|max:500',
            'priority' => 'nullable|in:low,medium,high',
        ]);

        $target = Target::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'target_amount' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            'priority' => $validated['priority'] ?? 'medium',
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => __('targets.created'),
            'data' => $target,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/targets/{id}",
     *     summary="Get a specific target",
     *     tags={"Targets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Target details")
     * )
     */
    public function show(Target $target): JsonResponse
    {
        if ($target->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('common.unauthorized'),
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $target,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/targets/{id}",
     *     summary="Update a target",
     *     tags={"Targets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent()),
     *     @OA\Response(response=200, description="Target updated successfully")
     * )
     */
    public function update(Request $request, Target $target): JsonResponse
    {
        if ($target->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('common.unauthorized'),
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0.01',
            'description' => 'nullable|string|max:1000',
            'image_url' => 'nullable|url|max:500',
            'priority' => 'nullable|in:low,medium,high',
        ]);

        $updateData = [];
        if (isset($validated['name'])) $updateData['name'] = $validated['name'];
        if (isset($validated['price'])) $updateData['target_amount'] = $validated['price'];
        if (array_key_exists('description', $validated)) $updateData['description'] = $validated['description'];
        if (array_key_exists('image_url', $validated)) $updateData['image_url'] = $validated['image_url'];
        if (isset($validated['priority'])) $updateData['priority'] = $validated['priority'];

        $target->update($updateData);

        return response()->json([
            'success' => true,
            'message' => __('targets.updated'),
            'data' => $target->fresh(),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/targets/{id}",
     *     summary="Delete a target",
     *     tags={"Targets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Target deleted successfully")
     * )
     */
    public function destroy(Target $target): JsonResponse
    {
        if ($target->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('common.unauthorized'),
            ], 403);
        }

        $target->delete();

        return response()->json([
            'success' => true,
            'message' => __('targets.deleted'),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/targets/{id}/purchase",
     *     summary="Purchase a target (deduct from balance)",
     *     tags={"Targets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Target purchased successfully")
     * )
     */
    public function purchase(Target $target): JsonResponse
    {
        if ($target->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('common.unauthorized'),
            ], 403);
        }

        if (!$target->can_afford) {
            return response()->json([
                'success' => false,
                'message' => __('targets.insufficient_balance'),
            ], 400);
        }

        DB::beginTransaction();
        try {
            $balance = Balance::where('user_id', Auth::id())->first();
            $price = $target->target_amount;

            // Deduct from balance
            $balance->current_balance -= $price;
            $balance->save();

            // Record transaction
            BalanceTransaction::create([
                'user_id' => Auth::id(),
                'balance_id' => $balance->id,
                'type' => 'subtract',
                'amount' => $price,
                'source' => 'target',
                'target_id' => $target->id,
                'description' => __('targets.purchase_description', ['name' => $target->name]),
            ]);

            // Mark target as completed
            $target->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('targets.purchased'),
                'data' => [
                    'target' => $target->fresh(),
                    'new_balance' => $balance->current_balance,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('common.error'),
            ], 500);
        }
    }
}
