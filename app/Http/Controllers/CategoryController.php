<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories with subcategories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $categories = Category::with('subCategories')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Categories retrieved successfully',
            'data' => $categories
        ]);
    }

    /**
     * Store a newly created category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = Category::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified category with subcategories.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $category = Category::with('subCategories')->find($id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Category retrieved successfully',
            'data' => $category
        ]);
    }

    /**
     * Update the specified category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found',
            ], 404);
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Category deleted successfully',
        ]);
    }

    /**
     * Store a newly created subcategory.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $category_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeSubCategory(Request $request, $category_id)
    {
        $category = Category::find($category_id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:sub_categories,name,NULL,id,category_id,' . $category_id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $subCategory = SubCategory::create([
            'category_id' => $category_id,
            'name' => $request->name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'SubCategory created successfully',
            'data' => $subCategory
        ], 201);
    }

    /**
     * Update the specified subcategory.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $category_id
     * @param  int  $subcategory_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSubCategory(Request $request, $category_id, $subcategory_id)
    {
        $category = Category::find($category_id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found',
            ], 404);
        }

        $subCategory = SubCategory::where('category_id', $category_id)
            ->where('id', $subcategory_id)
            ->first();

        if (!$subCategory) {
            return response()->json([
                'status' => 'error',
                'message' => 'SubCategory not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:sub_categories,name,' . $subcategory_id . ',id,category_id,' . $category_id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $subCategory->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'SubCategory updated successfully',
            'data' => $subCategory
        ]);
    }

    /**
     * Remove the specified subcategory.
     *
     * @param  int  $category_id
     * @param  int  $subcategory_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroySubCategory($category_id, $subcategory_id)
    {
        $category = Category::find($category_id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found',
            ], 404);
        }

        $subCategory = SubCategory::where('category_id', $category_id)
            ->where('id', $subcategory_id)
            ->first();

        if (!$subCategory) {
            return response()->json([
                'status' => 'error',
                'message' => 'SubCategory not found',
            ], 404);
        }

        $subCategory->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'SubCategory deleted successfully',
        ]);
    }
}
