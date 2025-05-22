<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test regular user can access dashboard
     */
    public function test_user_can_access_user_dashboard(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@student.telkomuniversity.ac.id',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'User dashboard',
                'data' => [
                    'user_role' => 'user'
                ]
            ]);
    }

    /**
     * Test regular user cannot access admin dashboard
     */
    public function test_user_cannot_access_admin_dashboard(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@student.telkomuniversity.ac.id',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'You do not have permission to access this resource',
                'code' => 403
            ]);
    }

    /**
     * Test admin can access admin dashboard
     */
    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@telkomuniversity.ac.id',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $token = $admin->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Admin dashboard',
                'data' => [
                    'user_role' => 'admin'
                ]
            ]);
    }

    /**
     * Test disposisi can access staff dashboard
     */
    public function test_disposisi_can_access_staff_dashboard(): void
    {
        $disposisi = User::create([
            'name' => 'Disposisi User',
            'email' => 'disposisi@telkomuniversity.ac.id',
            'password' => bcrypt('password'),
            'role' => 'disposisi',
        ]);

        $token = $disposisi->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/staff/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Staff dashboard',
                'data' => [
                    'user_role' => 'disposisi'
                ]
            ]);
    }

    /**
     * Test user cannot access staff dashboard
     */
    public function test_user_cannot_access_staff_dashboard(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@student.telkomuniversity.ac.id',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/staff/dashboard');

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'You do not have permission to access this resource',
                'code' => 403
            ]);
    }
}
