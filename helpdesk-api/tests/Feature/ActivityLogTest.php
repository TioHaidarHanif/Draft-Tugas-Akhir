<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_logs_activity()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@student.telkomuniversity.ac.id',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('activity_logs', [
            'activity' => 'register',
        ]);
    }

    public function test_login_logs_activity()
    {
        $user = User::factory()->create([
            'email' => 'test2@student.telkomuniversity.ac.id',
            'password' => bcrypt('password'),
        ]);
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('activity_logs', [
            'activity' => 'login',
            'user_id' => $user->id,
        ]);
    }

    public function test_update_profile_logs_activity()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/auth/profile', [
                'name' => 'Updated Name',
            ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('activity_logs', [
            'activity' => 'update_profile',
            'user_id' => $user->id,
        ]);
    }

    public function test_logout_logs_activity()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');
        $response->assertStatus(200);
        $this->assertDatabaseHas('activity_logs', [
            'activity' => 'logout',
            'user_id' => $user->id,
        ]);
    }

    public function test_update_role_logs_activity()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'student']);
        $token = $admin->createToken('auth_token')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/users/' . $user->id . '/role', [
                'role' => 'disposisi',
            ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('activity_logs', [
            'activity' => 'update_role',
            'user_id' => $user->id,
        ]);
    }

    public function test_delete_user_logs_activity()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'student']);
        $token = $admin->createToken('auth_token')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/users/' . $user->id);
        $response->assertStatus(200);
        $this->assertDatabaseHas('activity_logs', [
            'activity' => 'delete_user',
            'user_id' => $user->id,
        ]);
    }

    public function test_admin_can_view_activity_logs()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('auth_token')->plainTextToken;
        ActivityLog::create([
            'id' => (string) Str::uuid(),
            'user_id' => $admin->id,
            'activity' => 'test',
            'description' => 'Test log',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test-agent',
            'created_at' => now(),
        ]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/activity-logs');
        $response->assertStatus(200)
            ->assertJsonFragment(['activity' => 'test']);
    }

    public function test_non_admin_cannot_view_activity_logs()
    {
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth_token')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/activity-logs');
        $response->assertStatus(403);
    }
}
