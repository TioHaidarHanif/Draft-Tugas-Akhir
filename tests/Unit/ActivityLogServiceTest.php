<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ActivityLogServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test logging activity with a specific user ID.
     */
    public function test_log_with_user_id(): void
    {
        // Create a user
        $user = User::factory()->create();
        
        // Log an activity with this user ID
        $log = ActivityLogService::log('test_activity', 'Test description', $user->id);
        
        // Assert the log was created with the correct data
        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals('test_activity', $log->activity);
        $this->assertEquals('Test description', $log->description);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertDatabaseHas('activity_logs', [
            'activity' => 'test_activity',
            'description' => 'Test description',
            'user_id' => $user->id
        ]);
    }

    /**
     * Test logging activity with the authenticated user.
     */
    public function test_log_with_authenticated_user(): void
    {
        // Create and authenticate a user
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Log an activity without specifying user ID (should use authenticated user)
        $log = ActivityLogService::log('test_auth_activity', 'Auth test description');
        
        // Assert the log was created with the authenticated user
        $this->assertEquals($user->id, $log->user_id);
        $this->assertDatabaseHas('activity_logs', [
            'activity' => 'test_auth_activity',
            'user_id' => $user->id
        ]);
    }

    /**
     * Test logging authentication activity.
     */
    public function test_log_auth_activity(): void
    {
        // Create a user
        $user = User::factory()->create();
        
        // Log an auth activity
        $log = ActivityLogService::logAuth('login', 'User logged in', $user->id);
        
        // Assert the log was created with the correct activity prefix
        $this->assertEquals('auth_login', $log->activity);
        $this->assertDatabaseHas('activity_logs', [
            'activity' => 'auth_login',
            'user_id' => $user->id
        ]);
    }

    /**
     * Test logging profile activity.
     */
    public function test_log_profile_activity(): void
    {
        // Create a user
        $user = User::factory()->create();
        
        // Log a profile activity
        $log = ActivityLogService::logProfile('update', 'Profile updated', $user->id);
        
        // Assert the log was created with the correct activity prefix
        $this->assertEquals('profile_update', $log->activity);
        $this->assertDatabaseHas('activity_logs', [
            'activity' => 'profile_update',
            'user_id' => $user->id
        ]);
    }

    /**
     * Test logging user management activity.
     */
    public function test_log_user_management_activity(): void
    {
        // Create a user
        $user = User::factory()->create(['role' => 'admin']);
        
        // Log a user management activity
        $log = ActivityLogService::logUserManagement('create', 'User created', $user->id);
        
        // Assert the log was created with the correct activity prefix
        $this->assertEquals('user_management_create', $log->activity);
        $this->assertDatabaseHas('activity_logs', [
            'activity' => 'user_management_create',
            'user_id' => $user->id
        ]);
    }
}
