<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    /**
     * Test admin can get list of all users with ticket statistics.
     *
     * @return void
     */
    public function test_admin_can_get_all_users_with_ticket_statistics()
    {
        $admin = $this->createAndAuthenticateUser('admin');
        
        // Create some additional users
        $users = User::factory()->count(3)->create();
        
        // Create tickets for a user
        $user = $users[0];
        \App\Models\Ticket::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'open'
        ]);
        
        \App\Models\Ticket::factory()->count(1)->create([
            'user_id' => $user->id,
            'status' => 'closed'
        ]);
        
        $response = $this->getJson('/api/users');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'email',
                             'role',
                             'tickets_count',
                             'tickets_statistics' => [
                                 'total',
                                 'open',
                                 'closed',
                                 'in_progress'
                             ]
                         ]
                     ]
                 ]);
        
        // Assert the user with tickets has the correct statistics
        $responseData = $response->json('data');
        $responseUser = collect($responseData)->firstWhere('id', $user->id);
        
        $this->assertEquals(3, $responseUser['tickets_statistics']['total']);
        $this->assertEquals(2, $responseUser['tickets_statistics']['open']);
        $this->assertEquals(1, $responseUser['tickets_statistics']['closed']);
    }
    
    /**
     * Test student cannot get list of all users.
     *
     * @return void
     */
    public function test_student_cannot_get_all_users()
    {
        $student = $this->createAndAuthenticateUser('student');
        
        $response = $this->getJson('/api/users');
        
        $response->assertStatus(403);
    }
    
    /**
     * Test disposisi user cannot get list of all users.
     *
     * @return void
     */
    public function test_disposisi_cannot_get_all_users()
    {
        $disposisi = $this->createAndAuthenticateUser('disposisi');
        
        $response = $this->getJson('/api/users');
        
        $response->assertStatus(403);
    }
    
    /**
     * Test admin can get a specific user with ticket details.
     *
     * @return void
     */
    public function test_admin_can_get_specific_user_with_ticket_details()
    {
        $admin = $this->createAndAuthenticateUser('admin');
        
        $user = User::factory()->create();
        
        // Create some tickets for the user
        \App\Models\Ticket::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'open',
            'judul' => 'Test Ticket Open'
        ]);
        
        \App\Models\Ticket::factory()->count(1)->create([
            'user_id' => $user->id,
            'status' => 'closed',
            'judul' => 'Test Ticket Closed'
        ]);
        
        $response = $this->getJson("/api/users/{$user->id}");
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'id',
                         'name',
                         'email',
                         'role',
                         'tickets' => [
                             '*' => [
                                 'id',
                                 'judul',
                                 'status',
                                 'created_at',
                                 'url'
                             ]
                         ],
                         'tickets_statistics' => [
                             'total',
                             'open',
                             'closed',
                             'in_progress'
                         ]
                     ]
                 ]);
        
        // Assert ticket statistics are correct
        $responseData = $response->json('data');
        $this->assertEquals(3, $responseData['tickets_statistics']['total']);
        $this->assertEquals(2, $responseData['tickets_statistics']['open']);
        $this->assertEquals(1, $responseData['tickets_statistics']['closed']);
        
        // Assert tickets are included in the response
        $this->assertCount(3, $responseData['tickets']);
        
        // Assert each ticket has a URL
        foreach ($responseData['tickets'] as $ticket) {
            $this->assertStringContainsString("/api/tickets/{$ticket['id']}", $ticket['url']);
        }
    }
    
    /**
     * Test admin cannot get a non-existent user.
     *
     * @return void
     */
    public function test_admin_cannot_get_nonexistent_user()
    {
        $admin = $this->createAndAuthenticateUser('admin');
        
        $response = $this->getJson("/api/users/9999");
        
        $response->assertStatus(404);
    }
    
    /**
     * Test student cannot get a specific user.
     *
     * @return void
     */
    public function test_student_cannot_get_specific_user()
    {
        $student = $this->createAndAuthenticateUser('student');
        
        $user = User::factory()->create();
        
        $response = $this->getJson("/api/users/{$user->id}");
        
        $response->assertStatus(403);
    }
    
    /**
     * Test admin can update a user.
     *
     * @return void
     */
    public function test_admin_can_update_user()
    {
        $admin = $this->createAndAuthenticateUser('admin');
        
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com'
        ]);
        
        $response = $this->patchJson("/api/users/{$user->id}", [
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ]);
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ]);
    }
    
    /**
     * Test admin can update user password.
     *
     * @return void
     */
    public function test_admin_can_update_user_password()
    {
        $admin = $this->createAndAuthenticateUser('admin');
        
        $user = User::factory()->create();
        $oldPassword = $user->password;
        
        $response = $this->patchJson("/api/users/{$user->id}", [
            'password' => 'newpassword123'
        ]);
        
        $response->assertStatus(200);
        
        // Refresh user model to get updated data
        $user->refresh();
        
        // Check that the password has been changed
        $this->assertNotEquals($oldPassword, $user->password);
    }
    
    /**
     * Test admin cannot update a non-existent user.
     *
     * @return void
     */
    public function test_admin_cannot_update_nonexistent_user()
    {
        $admin = $this->createAndAuthenticateUser('admin');
        
        $response = $this->patchJson("/api/users/9999", [
            'name' => 'Updated Name'
        ]);
        
        $response->assertStatus(404);
    }
    
    /**
     * Test validation for user update.
     *
     * @return void
     */
    public function test_user_update_validation()
    {
        $admin = $this->createAndAuthenticateUser('admin');
        
        $user = User::factory()->create();
        
        // Create another user with this email to test unique validation
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);
        
        $response = $this->patchJson("/api/users/{$user->id}", [
            'email' => 'existing@example.com'
        ]);
        
        $response->assertStatus(422);
    }
    
    /**
     * Test student cannot update user.
     *
     * @return void
     */
    public function test_student_cannot_update_user()
    {
        $student = $this->createAndAuthenticateUser('student');
        
        $user = User::factory()->create();
        
        $response = $this->patchJson("/api/users/{$user->id}", [
            'name' => 'Updated Name'
        ]);
        
        $response->assertStatus(403);
    }
    
    /**
     * Test admin can update a user's role.
     *
     * @return void
     */
    public function test_admin_can_update_user_role()
    {
        $admin = $this->createAndAuthenticateUser('admin');
        
        $user = User::factory()->create(['role' => 'student']);
        
        $response = $this->patchJson("/api/users/{$user->id}/role", [
            'role' => 'disposisi'
        ]);
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'disposisi'
        ]);
    }
    
    /**
     * Test validation for role update.
     *
     * @return void
     */
    public function test_role_update_validation()
    {
        $admin = $this->createAndAuthenticateUser('admin');
        
        $user = User::factory()->create(['role' => 'student']);
        
        $response = $this->patchJson("/api/users/{$user->id}/role", [
            'role' => 'invalid_role'
        ]);
        
        $response->assertStatus(422);
    }
    
    /**
     * Test admin cannot update role for non-existent user.
     *
     * @return void
     */
    public function test_admin_cannot_update_role_for_nonexistent_user()
    {
        $admin = $this->createAndAuthenticateUser('admin');
        
        $response = $this->patchJson("/api/users/9999/role", [
            'role' => 'disposisi'
        ]);
        
        $response->assertStatus(404);
    }
    
    /**
     * Test student cannot update user role.
     *
     * @return void
     */
    public function test_student_cannot_update_user_role()
    {
        $student = $this->createAndAuthenticateUser('student');
        
        $user = User::factory()->create(['role' => 'student']);
        
        $response = $this->patchJson("/api/users/{$user->id}/role", [
            'role' => 'disposisi'
        ]);
        
        $response->assertStatus(403);
    }
    
    /**
     * Test admin can delete a user.
     *
     * @return void
     */
    public function test_admin_can_delete_user()
    {
        $admin = $this->createAndAuthenticateUser('admin');
        
        $user = User::factory()->create();
        
        $response = $this->deleteJson("/api/users/{$user->id}");
        
        $response->assertStatus(200);
        
        $this->assertDatabaseMissing('users', [
            'id' => $user->id
        ]);
    }
    
    /**
     * Test admin cannot delete a non-existent user.
     *
     * @return void
     */
    public function test_admin_cannot_delete_nonexistent_user()
    {
        $admin = $this->createAndAuthenticateUser('admin');
        
        $response = $this->deleteJson("/api/users/9999");
        
        $response->assertStatus(404);
    }
    
    /**
     * Test admin cannot delete the only admin user.
     *
     * @return void
     */
    public function test_admin_cannot_delete_only_admin()
    {
        $admin = $this->createAndAuthenticateUser('admin');
        
        $response = $this->deleteJson("/api/users/{$admin->id}");
        
        $response->assertStatus(422);
    }
    
    /**
     * Test student cannot delete user.
     *
     * @return void
     */
    public function test_student_cannot_delete_user()
    {
        $student = $this->createAndAuthenticateUser('student');
        
        $user = User::factory()->create();
        
        $response = $this->deleteJson("/api/users/{$user->id}");
        
        $response->assertStatus(403);
    }
    
    /**
     * Test admin can get user statistics.
     *
     * @return void
     */
    public function test_admin_can_get_user_statistics()
    {
        $admin = $this->createAndAuthenticateUser('admin');
        
        // Create users with different roles
        User::factory()->count(2)->create(['role' => 'student']);
        User::factory()->count(1)->create(['role' => 'disposisi']);
        
        $response = $this->getJson('/api/users/statistics');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'total_users',
                         'admin_users',
                         'student_users',
                         'disposisi_users',
                         'tickets_created',
                         'tickets_open',
                         'tickets_closed',
                         'registration_trend'
                     ]
                 ]);
    }
    
    /**
     * Test student cannot get user statistics.
     *
     * @return void
     */
    public function test_student_cannot_get_user_statistics()
    {
        $student = $this->createAndAuthenticateUser('student');
        
        $response = $this->getJson('/api/users/statistics');
        
        $response->assertStatus(403);
    }
}
