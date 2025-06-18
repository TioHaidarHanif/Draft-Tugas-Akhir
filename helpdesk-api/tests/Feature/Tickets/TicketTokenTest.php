<?php

namespace Tests\Feature\Tickets;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TicketTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_ticket_auto_generates_token()
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $category = \App\Models\Category::factory()->create();
        $subCategory = \App\Models\SubCategory::factory()->create(['category_id' => $category->id]);
        $this->actingAs($user);
        $response = $this->postJson('/api/tickets', [
            'judul' => 'Test',
            'deskripsi' => 'Test',
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'prodi' => 'Teknik Informatika',
            'semester' => '6',
            'no_hp' => '08123456789',
            'anonymous' => true,
        ]);
        $response->assertStatus(201);
        $this->assertArrayHasKey('token', $response->json('data'));
        $this->assertNotNull($response->json('data.token'));
    }

    public function test_reveal_token_with_correct_password()
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'anonymous' => true,
        ]);
        $this->actingAs($user);
        $response = $this->postJson("/api/tickets/{$ticket->id}/reveal-token", [
            'password' => 'password',
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'data' => ['token']]);
    }

    public function test_reveal_token_with_wrong_password()
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'anonymous' => true,
        ]);
        $this->actingAs($user);
        $response = $this->postJson("/api/tickets/{$ticket->id}/reveal-token", [
            'password' => 'wrongpassword',
        ]);
        $response->assertStatus(401);
    }

    public function test_token_not_visible_to_other_users()
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $other = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'anonymous' => true,
        ]);
        $this->actingAs($other);
        $response = $this->getJson("/api/tickets/{$ticket->id}");
        $response->assertStatus(403);
    }

    public function test_token_visible_to_admin()
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'anonymous' => true,
        ]);
        $this->actingAs($admin);
        $response = $this->getJson("/api/tickets/{$ticket->id}");
        $response->assertStatus(200);
        $this->assertArrayHasKey('token', $response->json('data.ticket'));
    }
}
