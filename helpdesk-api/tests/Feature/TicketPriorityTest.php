<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketPriorityTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_ticket_with_priority()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $subCategory = SubCategory::factory()->create(['category_id' => $category->id]);
        $payload = [
            'judul' => 'Test Ticket',
            'deskripsi' => 'Test deskripsi',
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'prodi' => 'Informatika',
            'semester' => '6',
            'no_hp' => '08123456789',
            'prioritas' => 'high',
        ];
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson('/api/tickets', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('data.prioritas', 'high');
    }

    public function test_create_ticket_without_priority_default_medium()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $subCategory = SubCategory::factory()->create(['category_id' => $category->id]);
        $payload = [
            'judul' => 'Test Ticket',
            'deskripsi' => 'Test deskripsi',
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'prodi' => 'Informatika',
            'semester' => '6',
            'no_hp' => '08123456789',
        ];
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson('/api/tickets', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('data.prioritas', 'medium');
    }

    public function test_update_ticket_priority()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'prioritas' => 'medium']);
        $this->actingAs($user, 'sanctum');
        $response = $this->patchJson('/api/tickets/' . $ticket->id, ['prioritas' => 'urgent']);
        $response->assertStatus(200)
            ->assertJsonPath('data.prioritas', 'urgent');
    }

    public function test_invalid_priority_rejected()
    {
        $user = User::factory()->create();
        $payload = [
            'judul' => 'Test Ticket',
            'deskripsi' => 'Test deskripsi',
            'category_id' => 1,
            'sub_category_id' => 1,
            'prodi' => 'Informatika',
            'semester' => '6',
            'no_hp' => '08123456789',
            'prioritas' => 'invalid',
        ];
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson('/api/tickets', $payload);
        $response->assertStatus(422);
    }
}
