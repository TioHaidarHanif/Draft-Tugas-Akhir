<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityLogControllerTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test that non-admin users cannot access activity logs.
     */
    public function test_non_admin_cannot_access_activity_logs(): void
    {
        // Create and authenticate a non-admin user
        $user = User::factory()->create(['role' => 'student']);
        Sanctum::actingAs($user);
        
        // Try to access activity logs
        $response = $this->getJson('/api/activity-logs');
        
        // Assert access is denied
        $response->assertStatus(403);
    }
    
    /**
     * Test that admin users can access activity logs.
     */
    public function test_admin_can_access_activity_logs(): void
    {
        // Create and authenticate an admin user
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        
        // Create some activity logs
        ActivityLogService::logAuth('login', 'User logged in', $admin->id);
        ActivityLogService::logUserManagement('view', 'Viewed user list', $admin->id);
        
        // Access activity logs
        $response = $this->getJson('/api/activity-logs');
        
        // Assert success and data structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'activity',
                            'description',
                            'ip_address',
                            'user_agent',
                            'created_at',
                            'updated_at',
                            'user' => [
                                'id',
                                'name',
                                'email',
                                'role'
                            ]
                        ]
                    ],
                    'current_page',
                    'total'
                ]
            ]);
    }
    
    /**
     * Test admin can view activity log statistics.
     */
    public function test_admin_can_view_activity_log_statistics(): void
    {
        // Create and authenticate an admin user
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        
        // Create some activity logs
        ActivityLogService::logAuth('login', 'User logged in', $admin->id);
        ActivityLogService::logAuth('logout', 'User logged out', $admin->id);
        ActivityLogService::logUserManagement('create', 'User created', $admin->id);
        
        // Access activity log statistics
        $response = $this->getJson('/api/activity-logs/statistics');
        
        // Assert success and data structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'total_logs',
                    'auth_logs',
                    'user_management_logs',
                    'profile_logs',
                    'logs_by_date'
                ]
            ]);
    }
    
    /**
     * Test admin can view a specific activity log.
     */
    public function test_admin_can_view_specific_activity_log(): void
    {
        // Create and authenticate an admin user
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        
        // Create an activity log
        $log = ActivityLogService::logAuth('login', 'User logged in', $admin->id);
        
        // Access the specific activity log
        $response = $this->getJson('/api/activity-logs/' . $log->id);
        
        // Assert success and data
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $log->id)
            ->assertJsonPath('data.activity', 'auth_login')
            ->assertJsonPath('data.user_id', $admin->id);
    }
    
    /**
     * Test activity logs can be filtered.
     */
    public function test_activity_logs_can_be_filtered(): void
    {
        // Create and authenticate an admin user
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        
        // Create various activity logs
        ActivityLogService::logAuth('login', 'User logged in', $admin->id);
        ActivityLogService::logAuth('logout', 'User logged out', $admin->id);
        ActivityLogService::logUserManagement('create', 'User created', $admin->id);
        
        // Filter by activity type
        $response = $this->getJson('/api/activity-logs?activity=auth_');
        
        // Assert only auth logs are returned
        $response->assertStatus(200);
        $data = $response->json('data.data');
        foreach ($data as $log) {
            $this->assertStringContainsString('auth_', $log['activity']);
        }
    }
}
