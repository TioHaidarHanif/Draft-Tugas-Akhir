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
        return response()->json($categories);
    }

    // POST /categories (admin only)
    public function store(Request $request)
    {
        $this->authorizeRole('admin');
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $category = Category::create(['name' => $request->name]);
        return response()->json($category, 201);
    }

    // POST /categories/{category}/sub-categories (admin only)
    public function storeSubCategory(Request $request, $categoryId)
    {
        $this->authorizeRole('admin');
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:sub_categories,name,NULL,id,category_id,' . $categoryId,
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $category = Category::findOrFail($categoryId);
        $subCategory = $category->subCategories()->create(['name' => $request->name]);
        return response()->json($subCategory, 201);
    }

    // Helper for role check
    protected function authorizeRole($role)
    {
        if (!Auth::user() || Auth::user()->role !== $role) {
            abort(403, 'Forbidden');
        }
    }
}
