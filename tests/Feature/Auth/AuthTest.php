<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@student.telkomuniversity.ac.id',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@student.telkomuniversity.ac.id',
            'role' => 'student',
        ]);
    }

    public function test_user_cannot_register_with_existing_email(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@student.telkomuniversity.ac.id',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Another User',
            'email' => 'test@student.telkomuniversity.ac.id', // Using existing email
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Registration failed',
            ])
            ->assertJsonValidationErrors('email');
    }

    public function test_user_cannot_register_with_invalid_email_domain(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com', // Not a valid Telkom University domain
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Registration failed',
            ])
            ->assertJsonValidationErrors('email');
    }

    public function test_user_can_login(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@student.telkomuniversity.ac.id',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@student.telkomuniversity.ac.id',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Login successful',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                    ],
                    'token',
                ],
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@student.telkomuniversity.ac.id',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@student.telkomuniversity.ac.id',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'The provided credentials are incorrect.',
                'code' => 401
            ]);
    }

    public function test_user_can_logout(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@student.telkomuniversity.ac.id',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Logged out successfully',
            ]);

        // Check token was deleted
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_authenticated_user_can_get_own_info(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@student.telkomuniversity.ac.id',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/profile');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => 'Test User',
                        'email' => 'test@student.telkomuniversity.ac.id',
                        'role' => 'student',
                    ]
                ]
            ]);
    }
}
