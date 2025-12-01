<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * Display a listing of active currencies.
     *
     * @OA\Get(
     *     path="/api/currencies",
     *     summary="Get all active currencies",
     *     description="Retrieve all active currencies available for selection",
     *     tags={"Currencies"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Currency"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index()
    {
        $currencies = Currency::active()->orderBy('code')->get();

        // Add translated name to each currency
        $currencies = $currencies->map(function ($currency) {
            $currency->translated_name = $currency->translatedName;
            return $currency;
        });

        return response()->json([
            'success' => true,
            'data' => $currencies,
        ]);
    }

    /**
     * Get the default currency.
     *
     * @OA\Get(
     *     path="/api/currencies/default",
     *     summary="Get default currency",
     *     description="Retrieve the system default currency (JOD)",
     *     tags={"Currencies"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Currency")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function default()
    {
        $currency = Currency::getDefault();

        if ($currency) {
            $currency->translated_name = $currency->translatedName;
        }

        return response()->json([
            'success' => true,
            'data' => $currency,
        ]);
    }

    /**
     * Get the user's active currency.
     *
     * @OA\Get(
     *     path="/api/currencies/active",
     *     summary="Get user's active currency",
     *     description="Retrieve the authenticated user's preferred currency",
     *     tags={"Currencies"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Currency")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function active(Request $request)
    {
        $user = $request->user();
        $currency = $user->currency ?? Currency::getDefault();

        if ($currency) {
            $currency->translated_name = $currency->translatedName;
        }

        return response()->json([
            'success' => true,
            'data' => $currency,
        ]);
    }

    /**
     * Update the user's preferred currency.
     *
     * @OA\Put(
     *     path="/api/currencies/set",
     *     summary="Set user's currency",
     *     description="Update the authenticated user's preferred currency",
     *     tags={"Currencies"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"currency_id"},
     *             @OA\Property(property="currency_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Currency updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Currency updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Currency")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function set(Request $request)
    {
        $request->validate([
            'currency_id' => ['required', 'exists:currencies,id'],
        ]);

        $currency = Currency::findOrFail($request->currency_id);

        if (!$currency->is_active) {
            return response()->json([
                'success' => false,
                'message' => __('messages.currency.inactive'),
            ], 422);
        }

        $request->user()->update([
            'currency_id' => $currency->id,
        ]);

        $currency->translated_name = $currency->translatedName;

        return response()->json([
            'success' => true,
            'message' => __('messages.currency.updated'),
            'data' => $currency,
        ]);
    }

    /**
     * Display the specified currency.
     *
     * @OA\Get(
     *     path="/api/currencies/{id}",
     *     summary="Get currency by ID",
     *     description="Retrieve a specific currency",
     *     tags={"Currencies"},
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
     *             @OA\Property(property="data", ref="#/components/schemas/Currency")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(Currency $currency)
    {
        $currency->translated_name = $currency->translatedName;

        return response()->json([
            'success' => true,
            'data' => $currency,
        ]);
    }
}
