<?php

namespace Tests\Feature\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_user_with_role(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'student',
        ]);
    }

    public function test_user_can_have_admin_role(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->assertEquals('admin', $user->role);
    }

    public function test_user_can_have_disposisi_role(): void
    {
        $user = User::create([
            'name' => 'disposisi User',
            'email' => 'disposisi@example.com',
            'password' => bcrypt('password'),
            'role' => 'disposisi',
        ]);

        $this->assertEquals('disposisi', $user->role);
    }

    public function test_user_has_tickets_relationship(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        $this->assertEmpty($user->tickets);
        $this->assertCount(0, $user->tickets);
    }

    public function test_user_has_assigned_tickets_relationship(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->assertEmpty($user->assignedTickets);
        $this->assertCount(0, $user->assignedTickets);
    }
}
