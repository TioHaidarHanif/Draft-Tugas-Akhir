<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    // GET /categories
    public function index()
    {
        $categories = Category::with('subCategories')->get();
        return response()->json([
            'status' => 'success',
            'data' => [
                'categories' => $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'sub_categories' => $category->subCategories->map(function ($sub) {
                            return [
                                'id' => $sub->id,
                                'name' => $sub->name,
                            ];
                        }),
                    ];
                })
            ]
        ]);
    }

    // POST /categories (admin only)
    public function store(Request $request)
    {
        $this->authorizeRole('admin');
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422);
        }
        $category = Category::create(['name' => $request->name]);
        return response()->json([
            'status' => 'success',
            'message' => 'Category created successfully',
            'data' => [
                'id' => $category->id,
                'name' => $category->name
            ]
        ], 201);
    }

    // POST /categories/{category}/sub-categories (admin only)
    public function storeSubCategory(Request $request, $categoryId)
    {
        $this->authorizeRole('admin');
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:sub_categories,name,NULL,id,category_id,' . $categoryId,
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 422
            ], 422);
        }
        $category = Category::findOrFail($categoryId);
        $subCategory = $category->subCategories()->create(['name' => $request->name]);
        return response()->json([
            'status' => 'success',
            'message' => 'Sub-category created successfully',
            'data' => [
                'id' => $subCategory->id,
                'category_id' => $category->id,
                'name' => $subCategory->name
            ]
        ], 201);
    }

    // Helper for role check
    protected function authorizeRole($role)
    {
        if (!Auth::user() || Auth::user()->role !== $role) {
            abort(403, 'Forbidden');
        }
    }
}
