<?php

namespace Tests\Feature\Feature\Controllers;

use App\Models\Category;
use App\Models\FAQ;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FAQControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function it_can_list_public_faqs()
    {
        $category = Category::factory()->create();
        FAQ::factory()->count(5)->create([
            'category_id' => $category->id,
            'is_public' => true
        ]);
        FAQ::factory()->count(3)->create([
            'category_id' => $category->id,
            'is_public' => false
        ]);

        $response = $this->getJson('/api/faqs');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data.data')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'question',
                            'answer',
                            'category_id',
                            'is_public',
                            'view_count',
                            'created_at',
                            'updated_at',
                            'category' => [
                                'id',
                                'name'
                            ]
                        ]
                    ],
                    'current_page',
                    'per_page'
                ]
            ]);
    }

    /** @test */
    public function it_can_filter_faqs_by_category()
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        
        FAQ::factory()->count(3)->create([
            'category_id' => $category1->id,
            'is_public' => true
        ]);
        
        FAQ::factory()->count(2)->create([
            'category_id' => $category2->id,
            'is_public' => true
        ]);

        $response = $this->getJson("/api/faqs?category_id={$category1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data');
    }

    /** @test */
    public function it_can_search_faqs_by_keyword()
    {
        FAQ::factory()->create([
            'question' => 'How do I reset my password?',
            'answer' => 'Follow these steps...',
            'is_public' => true
        ]);
        
        FAQ::factory()->create([
            'question' => 'How do I change my email?',
            'answer' => 'You can update your email...',
            'is_public' => true
        ]);

        $response = $this->getJson('/api/faqs?search=password');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.question', 'How do I reset my password?');
    }

    /** @test */
    public function it_can_show_a_public_faq()
    {
        $faq = FAQ::factory()->create(['is_public' => true]);

        $response = $this->getJson("/api/faqs/{$faq->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'FAQ retrieved successfully',
                'data' => [
                    'id' => $faq->id,
                    'question' => $faq->question,
                    'answer' => $faq->answer
                ]
            ]);
        
        // Check that view count is incremented
        $this->assertEquals($faq->view_count + 1, $faq->fresh()->view_count);
    }

    /** @test */
    public function it_cannot_show_a_private_faq_to_guest()
    {
        $faq = FAQ::factory()->create(['is_public' => false]);

        $response = $this->getJson("/api/faqs/{$faq->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_show_a_private_faq_to_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        
        $faq = FAQ::factory()->create(['is_public' => false]);

        $response = $this->getJson("/api/faqs/{$faq->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'FAQ retrieved successfully',
                'data' => [
                    'id' => $faq->id,
                    'question' => $faq->question,
                    'answer' => $faq->answer
                ]
            ]);
    }

    /** @test */
    public function it_can_list_faq_categories()
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        
        FAQ::factory()->count(3)->create([
            'category_id' => $category1->id,
            'is_public' => true
        ]);
        
        FAQ::factory()->count(2)->create([
            'category_id' => $category2->id,
            'is_public' => true
        ]);

        $response = $this->getJson('/api/faqs/categories');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.faqs_count', 3)
            ->assertJsonPath('data.1.faqs_count', 2);
    }

    /** @test */
    public function it_can_create_a_faq_as_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        
        $category = Category::factory()->create();
        
        $faqData = [
            'question' => 'How do I reset my password?',
            'answer' => 'You can reset your password by clicking on the "Forgot Password" link.',
            'category_id' => $category->id,
            'is_public' => true
        ];

        $response = $this->postJson('/api/faqs', $faqData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'FAQ created successfully',
                'data' => [
                    'question' => 'How do I reset my password?',
                    'answer' => 'You can reset your password by clicking on the "Forgot Password" link.',
                    'category_id' => $category->id,
                    'is_public' => true,
                    'user_id' => $admin->id
                ]
            ]);
        
        $this->assertDatabaseHas('faqs', [
            'question' => 'How do I reset my password?',
            'user_id' => $admin->id
        ]);
    }

    /** @test */
    public function it_cannot_create_a_faq_as_non_admin()
    {
        $user = User::factory()->create(['role' => 'student']);
        Sanctum::actingAs($user);
        
        $category = Category::factory()->create();
        
        $faqData = [
            'question' => 'How do I reset my password?',
            'answer' => 'You can reset your password by clicking on the "Forgot Password" link.',
            'category_id' => $category->id,
            'is_public' => true
        ];

        $response = $this->postJson('/api/faqs', $faqData);

        $response->assertStatus(403);
        
        $this->assertDatabaseMissing('faqs', [
            'question' => 'How do I reset my password?'
        ]);
    }

    /** @test */
    public function it_can_update_a_faq_as_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        
        $faq = FAQ::factory()->create();
        
        $updateData = [
            'question' => 'Updated question?',
            'answer' => 'Updated answer.',
            'is_public' => false
        ];

        $response = $this->patchJson("/api/faqs/{$faq->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'FAQ updated successfully',
                'data' => [
                    'id' => $faq->id,
                    'question' => 'Updated question?',
                    'answer' => 'Updated answer.',
                    'is_public' => false
                ]
            ]);
        
        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'question' => 'Updated question?',
            'answer' => 'Updated answer.',
            'is_public' => false
        ]);
    }

    /** @test */
    public function it_can_delete_a_faq_as_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        
        $faq = FAQ::factory()->create();

        $response = $this->deleteJson("/api/faqs/{$faq->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'FAQ deleted successfully'
            ]);
        
        $this->assertSoftDeleted('faqs', [
            'id' => $faq->id
        ]);
    }

    /** @test */
    public function it_can_convert_ticket_to_faq_as_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        
        $ticket = Ticket::factory()->create();
        
        $faqData = [
            'question' => 'How do I do something?',
            'answer' => 'Here is how you do it...',
            'is_public' => true
        ];

        $response = $this->postJson("/api/tickets/{$ticket->id}/convert-to-faq", $faqData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Ticket converted to FAQ successfully',
                'data' => [
                    'question' => 'How do I do something?',
                    'answer' => 'Here is how you do it...',
                    'category_id' => $ticket->category_id,
                    'ticket_id' => $ticket->id,
                    'user_id' => $admin->id,
                    'is_public' => true
                ]
            ]);
        
        $this->assertDatabaseHas('faqs', [
            'question' => 'How do I do something?',
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id
        ]);
    }
}
