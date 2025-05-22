<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_subcategory(): void
    {
        $category = Category::create([
            'name' => 'Test Category',
        ]);

        $subCategory = SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Test SubCategory',
        ]);

        $this->assertDatabaseHas('sub_categories', [
            'name' => 'Test SubCategory',
            'category_id' => $category->id,
        ]);
    }

    public function test_subcategory_belongs_to_category(): void
    {
        $category = Category::create([
            'name' => 'Test Category',
        ]);

        $subCategory = SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Test SubCategory',
        ]);

        $this->assertEquals($category->id, $subCategory->category->id);
        $this->assertEquals('Test Category', $subCategory->category->name);
    }
}
