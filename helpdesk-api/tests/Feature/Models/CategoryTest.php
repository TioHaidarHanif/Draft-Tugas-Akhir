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

    public function test_admin_can_create_category()
    {
        $admin = \App\Models\User::factory()->asAdmin()->create();
        $payload = ['name' => 'Test Category'];
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/categories', $payload);
        $response->assertStatus(201)->assertJsonFragment(['name' => 'Test Category']);
        $this->assertDatabaseHas('categories', ['name' => 'Test Category']);
    }

    public function test_non_admin_cannot_create_category()
    {
        $user = \App\Models\User::factory()->asUser()->create();
        $payload = ['name' => 'Test Category'];
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/categories', $payload);
        $response->assertStatus(403);
    }

    public function test_admin_can_create_subcategory()
    {
        $admin = \App\Models\User::factory()->asAdmin()->create();
        $category = Category::factory()->create();
        $payload = ['name' => 'Test SubCategory'];
        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/categories/{$category->id}/sub-categories", $payload);
        $response->assertStatus(201)->assertJsonFragment(['name' => 'Test SubCategory']);
        $this->assertDatabaseHas('sub_categories', ['name' => 'Test SubCategory', 'category_id' => $category->id]);
    }

    public function test_non_admin_cannot_create_subcategory()
    {
        $user = \App\Models\User::factory()->asUser()->create();
        $category = Category::factory()->create();
        $payload = ['name' => 'Test SubCategory'];
        $response = $this->actingAs($user, 'sanctum')->postJson("/api/categories/{$category->id}/sub-categories", $payload);
        $response->assertStatus(403);
    }

    public function test_get_categories_with_subcategories()
    {
        $category = Category::factory()->create(['name' => 'Parent Category']);
        $sub = \App\Models\SubCategory::factory()->create(['category_id' => $category->id, 'name' => 'Child SubCategory']);
        $user = \App\Models\User::factory()->asUser()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/categories');
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Parent Category'])
            ->assertJsonFragment(['name' => 'Child SubCategory']);
    }

    public function test_category_name_is_required()
    {
        $admin = \App\Models\User::factory()->asAdmin()->create();
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/categories', []);
        $response->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_subcategory_name_is_required()
    {
        $admin = \App\Models\User::factory()->asAdmin()->create();
        $category = Category::factory()->create();
        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/categories/{$category->id}/sub-categories", []);
        $response->assertStatus(422)->assertJsonValidationErrors('name');
    }
}
