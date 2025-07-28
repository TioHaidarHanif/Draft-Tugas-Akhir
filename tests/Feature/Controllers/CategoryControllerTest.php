<?php

namespace Tests\Feature\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $regularUser;
    protected $category;
    protected $subCategory;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with different roles
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->regularUser = User::factory()->create(['role' => 'student']);

        // Create a test category and subcategory
        $this->category = Category::create(['name' => 'Hardware']);
        $this->subCategory = SubCategory::create([
            'category_id' => $this->category->id,
            'name' => 'Laptop'
        ]);
    }

    /**
     * Test retrieving all categories (public access).
     */
    public function test_get_all_categories_public_access(): void
    {
        // Create some categories in the database for the test
        Category::create(['name' => 'Test Category 1']);
        Category::create(['name' => 'Test Category 2']);
        
        // Then try the categories endpoint
        $response = $this->get('/api/categories');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'created_at',
                        'updated_at',
                        'sub_categories'
                    ]
                ]
            ]);
            
        // Check that we have at least 3 categories (2 we just created + Hardware from setUp)
        $responseData = $response->json('data');
        $this->assertGreaterThanOrEqual(3, count($responseData));
    }

    /**
     * Test creating a new category as admin.
     */
    public function test_create_category_as_admin(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/categories', [
                'name' => 'Network'
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Network'
        ]);
    }

    /**
     * Test creating a new category as a regular user (should be forbidden).
     */
    public function test_create_category_as_regular_user(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->postJson('/api/categories', [
                'name' => 'Software'
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('categories', [
            'name' => 'Software'
        ]);
    }

    /**
     * Test validation when creating a category with an existing name.
     */
    public function test_create_category_with_duplicate_name(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/categories', [
                'name' => 'Hardware' // Already exists
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test updating a category as admin.
     */
    public function test_update_category_as_admin(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/categories/{$this->category->id}", [
                'name' => 'Hardware Updated'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $this->category->id,
            'name' => 'Hardware Updated'
        ]);
    }

    /**
     * Test deleting a category as admin.
     */
    public function test_delete_category_as_admin(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/api/categories/{$this->category->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('categories', [
            'id' => $this->category->id
        ]);

        // SubCategories should also be deleted due to cascade
        $this->assertDatabaseMissing('sub_categories', [
            'id' => $this->subCategory->id
        ]);
    }

    /**
     * Test creating a subcategory as admin.
     */
    public function test_create_subcategory_as_admin(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/categories/{$this->category->id}/sub-categories", [
                'name' => 'Desktop'
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'category_id',
                    'name',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('sub_categories', [
            'category_id' => $this->category->id,
            'name' => 'Desktop'
        ]);
    }

    /**
     * Test creating a subcategory with duplicate name in the same category.
     */
    public function test_create_subcategory_with_duplicate_name(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/categories/{$this->category->id}/sub-categories", [
                'name' => 'Laptop' // Already exists in this category
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test updating a subcategory as admin.
     */
    public function test_update_subcategory_as_admin(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/categories/{$this->category->id}/sub-categories/{$this->subCategory->id}", [
                'name' => 'Laptop Updated'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'category_id',
                    'name',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('sub_categories', [
            'id' => $this->subCategory->id,
            'name' => 'Laptop Updated'
        ]);
    }

    /**
     * Test deleting a subcategory as admin.
     */
    public function test_delete_subcategory_as_admin(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/api/categories/{$this->category->id}/sub-categories/{$this->subCategory->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sub_categories', [
            'id' => $this->subCategory->id
        ]);
    }
}
