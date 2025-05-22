<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $regularUser;
    protected $disposisiUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with different roles
        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $this->disposisiUser = User::create([
            'name' => 'disposisi User',
            'email' => 'disposisi@example.com',
            'password' => bcrypt('password'),
            'role' => 'disposisi',
        ]);
    }

    public function test_admin_can_list_all_users(): void
    {
        $token = $this->adminUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'users')
            ->assertJsonFragment([
                'email' => 'admin@example.com',
                'role' => 'admin',
            ])
            ->assertJsonFragment([
                'email' => 'user@example.com',
                'role' => 'user',
            ])
            ->assertJsonFragment([
                'email' => 'disposisi@example.com',
                'role' => 'disposisi',
            ]);
    }

    public function test_regular_user_cannot_list_all_users(): void
    {
        $token = $this->regularUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_user(): void
    {
        $token = $this->adminUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/users', [
                'name' => 'New User',
                'email' => 'new@example.com',
                'password' => 'password',
                'role' => 'user',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'New User',
                'email' => 'new@example.com',
                'role' => 'user',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'role' => 'user',
        ]);
    }

    public function test_regular_user_cannot_create_user(): void
    {
        $token = $this->regularUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/users', [
                'name' => 'New User',
                'email' => 'new@example.com',
                'password' => 'password',
                'role' => 'user',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_view_own_profile(): void
    {
        $token = $this->regularUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/users/' . $this->regularUser->id);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'email' => 'user@example.com',
                'role' => 'user',
            ]);
    }

    public function test_user_cannot_view_other_user_profile(): void
    {
        $token = $this->regularUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/users/' . $this->disposisiUser->id);

        $response->assertStatus(403);
    }

    public function test_admin_can_view_any_user_profile(): void
    {
        $token = $this->adminUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/users/' . $this->regularUser->id);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'email' => 'user@example.com',
                'role' => 'user',
            ]);
    }

    public function test_user_can_update_own_profile(): void
    {
        $token = $this->regularUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/users/' . $this->regularUser->id, [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->regularUser->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_user_cannot_update_role(): void
    {
        $token = $this->regularUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/users/' . $this->regularUser->id, [
                'role' => 'admin',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('users', [
            'id' => $this->regularUser->id,
            'role' => 'user',
        ]);
    }

    public function test_admin_can_update_any_user_including_role(): void
    {
        $token = $this->adminUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/users/' . $this->regularUser->id, [
                'name' => 'Admin Updated',
                'role' => 'disposisi',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Admin Updated',
                'role' => 'disposisi',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->regularUser->id,
            'name' => 'Admin Updated',
            'role' => 'disposisi',
        ]);
    }

    public function test_admin_can_delete_user(): void
    {
        $token = $this->adminUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/users/' . $this->regularUser->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User deleted successfully',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $this->regularUser->id,
        ]);
    }

    public function test_regular_user_cannot_delete_user(): void
    {
        $token = $this->regularUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/users/' . $this->disposisiUser->id);

        $response->assertStatus(403);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $token = $this->adminUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/users/' . $this->adminUser->id);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Cannot delete your own account',
            ]);
    }
}
