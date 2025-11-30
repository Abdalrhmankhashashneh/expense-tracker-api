<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\IncomeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    /**
     * Get user settings and preferences.
     *
     * @OA\Get(
     *     path="/api/settings",
     *     summary="Get settings",
     *     description="Get user settings, profile information, and preferences",
     *     tags={"Settings"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="current_income", ref="#/components/schemas/Income"),
     *                 @OA\Property(property="preferences", type="object",
     *                     @OA\Property(property="currency", type="string", example="USD"),
     *                     @OA\Property(property="date_format", type="string", example="Y-m-d"),
     *                     @OA\Property(property="first_day_of_week", type="string", example="monday"),
     *                     @OA\Property(property="language", type="string", example="en")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $currentIncome = $user->currentIncome;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'current_income' => $currentIncome ? new IncomeResource($currentIncome) : null,
                'preferences' => [
                    'currency' => 'USD',
                    'date_format' => 'Y-m-d',
                    'first_day_of_week' => 'monday',
                    'language' => app()->getLocale(),
                ],
            ],
        ]);
    }

    /**
     * Update user profile.
     *
     * @OA\Put(
     *     path="/api/settings/profile",
     *     summary="Update profile",
     *     description="Update user profile information",
     *     tags={"Settings"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
        ]);

        $request->user()->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.settings.profile_updated'),
            'data' => new UserResource($request->user()),
        ]);
    }

    /**
     * Change user password.
     *
     * @OA\Post(
     *     path="/api/settings/change-password",
     *     summary="Change password",
     *     description="Change user password (all tokens will be revoked)",
     *     tags={"Settings"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password", "new_password", "new_password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password", example="oldpassword123"),
     *             @OA\Property(property="new_password", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="new_password_confirmation", type="string", format="password", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password changed successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('messages.auth.invalid_current_password')],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Revoke all tokens
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.settings.password_changed'),
        ]);
    }
}
