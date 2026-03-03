<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::withCount('courses')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:categories,name',
            'icon' => 'nullable|string',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'icon' => $request->get('icon', '📚'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $category = Category::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'name' => 'sometimes|string|unique:categories,name,' . $category->id,
            'icon' => 'nullable|string',
        ]);

        $category->update($request->only(['name', 'icon']));

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category,
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $category = Category::where('uuid', $uuid)->firstOrFail();

        if ($category->courses()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with associated courses',
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }
}
