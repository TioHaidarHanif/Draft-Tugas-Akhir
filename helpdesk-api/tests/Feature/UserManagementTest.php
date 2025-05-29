<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Create an admin user for authentication
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'student']);
    }

    public function test_admin_can_get_all_users()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/users');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data']);
    }

    public function test_non_admin_cannot_access_user_management()
    {
        $response = $this->actingAs($this->user)->getJson('/api/users');
        $response->assertStatus(403);
    }

    public function test_admin_can_get_user_detail()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/users/' . $this->user->id);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data']);
    }

    public function test_admin_can_update_user()
    {
        $response = $this->actingAs($this->admin)->patchJson('/api/users/' . $this->user->id, [
            'name' => 'Updated Name',
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_admin_can_update_user_role()
    {
        $response = $this->actingAs($this->admin)->patchJson('/api/users/' . $this->user->id . '/role', [
            'role' => 'disposisi',
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.role', 'disposisi');
    }

    public function test_admin_can_delete_user()
    {
        $response = $this->actingAs($this->admin)->deleteJson('/api/users/' . $this->user->id);
        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    public function test_admin_can_get_user_statistics()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/users/statistics');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['total', 'by_role']]);
    }

    public function test_user_list_includes_ticket_statistics_and_list()
    {
        $user = $this->user;
        $ticket1 = \App\Models\Ticket::factory()->create([
            'user_id' => $user->id,
            'judul' => 'Ticket Satu',
            'status' => 'open',
        ]);
        $ticket2 = \App\Models\Ticket::factory()->create([
            'user_id' => $user->id,
            'judul' => 'Ticket Dua',
            'status' => 'closed',
        ]);
        $response = $this->actingAs($this->admin)->getJson('/api/users');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    ['id', 'name', 'email', 'role', 'created_at', 'updated_at', 'ticket_count', 'tickets'],
                ]
            ]);
        $userData = collect($response->json('data'))->firstWhere('id', $user->id);
        $this->assertEquals(2, $userData['ticket_count']);
        $this->assertCount(2, $userData['tickets']);
        $this->assertEquals('Ticket Satu', $userData['tickets'][0]['judul']);
    }

    public function test_user_detail_includes_ticket_statistics_and_list()
    {
        $user = $this->user;
        $ticket = \App\Models\Ticket::factory()->create([
            'user_id' => $user->id,
            'judul' => 'Ticket Detail',
            'status' => 'open',
        ]);
        $response = $this->actingAs($this->admin)->getJson('/api/users/' . $user->id);
        print_r($response->json());
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id', 'name', 'email', 'role', 'created_at', 'updated_at', 'ticket_count', 'tickets'
                ]
            ]);
        $data = $response->json('data');
        $this->assertEquals(1, $data['ticket_count']);
        $this->assertEquals('Ticket Detail', $data['tickets'][0]['judul']);
    }
}
