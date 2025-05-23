<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

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
}
