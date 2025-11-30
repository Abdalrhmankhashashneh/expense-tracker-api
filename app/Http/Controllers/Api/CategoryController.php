<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource (default + custom categories).
     *
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Get all categories",
     *     description="Retrieve all categories (default + user's custom categories)",
     *     tags={"Categories"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Category"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $categories = Category::forUser($request->user()->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Store a newly created resource in storage (custom category).
     *
     * @OA\Post(
     *     path="/api/categories",
     *     summary="Create new category",
     *     description="Create a new custom category",
     *     tags={"Categories"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "icon", "color"},
     *             @OA\Property(property="name", type="string", example="Entertainment"),
     *             @OA\Property(property="icon", type="string", example="🎬"),
     *             @OA\Property(property="color", type="string", example="#907B60")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Category created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Category")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CategoryRequest $request)
    {
        $category = $request->user()->categories()->create([
            'name' => [
                'en' => $request->name,
                'ar' => $request->name,
            ],
            'icon' => $request->icon,
            'color' => $request->color,
            'is_default' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.category.created'),
            'data' => new CategoryResource($category),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/categories/{id}",
     *     summary="Get category by ID",
     *     description="Retrieve a specific category",
     *     tags={"Categories"},
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
     *             @OA\Property(property="data", ref="#/components/schemas/Category")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(Category $category)
    {
        $this->authorize('view', $category);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/categories/{id}",
     *     summary="Update category",
     *     description="Update a custom category (cannot update default categories)",
     *     tags={"Categories"},
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
     *             required={"name", "icon", "color"},
     *             @OA\Property(property="name", type="string", example="Movies"),
     *             @OA\Property(property="icon", type="string", example="🎥"),
     *             @OA\Property(property="color", type="string", example="#C1B6AE")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Category updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Category")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Cannot update default category"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(CategoryRequest $request, Category $category)
    {
        $this->authorize('update', $category);

        // Cannot update default categories
        if ($category->is_default) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANNOT_UPDATE_DEFAULT',
                    'message' => __('messages.category.cannot_update_default'),
                ],
            ], 403);
        }

        $category->update([
            'name' => [
                'en' => $request->name,
                'ar' => $request->name,
            ],
            'icon' => $request->icon,
            'color' => $request->color,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.category.updated'),
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/categories/{id}",
     *     summary="Delete category",
     *     description="Delete a custom category (cannot delete default categories or categories with expenses)",
     *     tags={"Categories"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Category deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Cannot delete default category"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=409, description="Category has expenses")
     * )
     */
    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        // Cannot delete default categories
        if ($category->is_default) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANNOT_DELETE_DEFAULT',
                    'message' => __('messages.category.cannot_delete_default'),
                ],
            ], 403);
        }

        // Cannot delete categories with existing expenses
        if ($category->expenses()->count() > 0) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CATEGORY_HAS_EXPENSES',
                    'message' => __('messages.category.has_expenses'),
                ],
            ], 409);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.category.deleted'),
        ]);
    }
}
