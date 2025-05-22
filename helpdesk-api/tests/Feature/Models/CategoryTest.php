<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_category(): void
    {
        $category = Category::create([
            'name' => 'Test Category',
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
        ]);
    }

    public function test_category_can_have_subcategories(): void
    {
        $category = Category::create([
            'name' => 'Test Category',
        ]);

        $subCategory = SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Test SubCategory',
        ]);

        $this->assertCount(1, $category->subCategories);
        $this->assertEquals('Test SubCategory', $category->subCategories->first()->name);
    }

    public function test_category_can_have_multiple_subcategories(): void
    {
        $category = Category::create([
            'name' => 'Test Category',
        ]);

        SubCategory::create([
            'category_id' => $category->id,
            'name' => 'SubCategory 1',
        ]);

        SubCategory::create([
            'category_id' => $category->id,
            'name' => 'SubCategory 2',
        ]);

        $this->assertCount(2, $category->subCategories);
        $this->assertContains('SubCategory 1', $category->subCategories->pluck('name'));
        $this->assertContains('SubCategory 2', $category->subCategories->pluck('name'));
    }
}
