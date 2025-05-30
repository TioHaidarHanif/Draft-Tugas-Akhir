<?php

namespace Tests\Feature\FAQ;

use App\Models\Faq;
use App\Models\User;
use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FAQControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_view_faqs()
    {
        $category = Category::factory()->create();
        $faq = Faq::factory()->create(['category_id' => $category->id]);
        $response = $this->getJson('/api/faqs');
        $response->assertStatus(200)->assertJsonFragment(['question' => $faq->question]);
    }

    public function test_admin_can_create_faq()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $payload = [
            'question' => 'Apa itu FAQ?',
            'answer' => 'Frequently Asked Questions',
            'category_id' => $category->id,
        ];
        $response = $this->actingAs($admin)->postJson('/api/faqs', $payload);
        $response->assertStatus(201)->assertJsonFragment(['question' => 'Apa itu FAQ?']);
    }

    public function test_non_admin_cannot_create_faq()
    {
        $user = User::factory()->asUser()->create();
        $category = Category::factory()->create();
        $payload = [
            'question' => 'Apa itu FAQ?',
            'category_id' => $category->id,
        ];
        $response = $this->actingAs($user)->postJson('/api/faqs', $payload);
        $response->assertStatus(403);
    }

    public function test_admin_can_convert_ticket_to_faq()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $ticket = Ticket::factory()->create();
        $payload = [
            'question' => 'Bagaimana cara reset password?',
            'answer' => 'Klik lupa password di halaman login.',
            'category_id' => $category->id,
        ];
        $response = $this->actingAs($admin)->postJson("/api/tickets/{$ticket->id}/convert-to-faq", $payload);
        $response->assertStatus(201)->assertJsonFragment(['question' => 'Bagaimana cara reset password?']);
    }

    public function test_public_can_view_faq_detail()
    {
        $category = Category::factory()->create();
        $faq = Faq::factory()->create(['category_id' => $category->id]);
        $response = $this->getJson('/api/faqs/' . $faq->id);
        $response->assertStatus(200)
            ->assertJsonFragment(['question' => $faq->question]);
    }

    public function test_admin_can_update_faq()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $faq = Faq::factory()->create(['category_id' => $category->id]);
        $payload = [
            'question' => 'Updated Question',
            'answer' => 'Updated Answer',
        ];
        $response = $this->actingAs($admin)->patchJson('/api/faqs/' . $faq->id, $payload);
        $response->assertStatus(200)
            ->assertJsonFragment(['question' => 'Updated Question']);
    }

    public function test_admin_can_delete_faq()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $faq = Faq::factory()->create(['category_id' => $category->id]);
        $response = $this->actingAs($admin)->deleteJson('/api/faqs/' . $faq->id);
        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'FAQ deleted']);
        $this->assertSoftDeleted('faqs', ['id' => $faq->id]);
    }

    public function test_public_can_view_faq_categories()
    {
        $category = Category::factory()->create();
        Faq::factory()->create(['category_id' => $category->id]);
        $response = $this->getJson('/api/faqs/categories');
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $category->id]);
    }

    public function test_admin_create_faq_validation_error()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $payload = [
            'answer' => 'Harus gagal',
            // question dan category_id tidak diisi
        ];
        $response = $this->actingAs($admin)->postJson('/api/faqs', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question', 'category_id']);
    }

    public function test_admin_convert_to_faq_validation_error()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = Ticket::factory()->create();
        $payload = [
            // question dan category_id tidak diisi
        ];
        $response = $this->actingAs($admin)->postJson("/api/tickets/{$ticket->id}/convert-to-faq", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question', 'category_id']);
    }

    public function test_public_can_view_faq_categories_empty()
    {
        $response = $this->getJson('/api/faqs/categories');
        $response->assertStatus(200)
            ->assertExactJson([]);
    }
}
