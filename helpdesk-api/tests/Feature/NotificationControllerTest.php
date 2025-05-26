<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a notification.
     *
     * @return void
     */
    public function test_can_create_notification()
    {
        // Create a sender
        $sender = User::factory()->create(['role' => 'admin']);
        
        // Create a recipient
        $recipient = User::factory()->create(['role' => 'student']);
        
        // Authenticate as the sender
        $this->actingAs($sender);
        
        // Create notification request data
        $notificationData = [
            'recipient_id' => $recipient->id,
            'title' => 'Test Notification',
            'message' => 'This is a test notification message',
            'type' => 'custom',
        ];
        
        // Send POST request to create notification
        $response = $this->postJson('/api/notifications', $notificationData);
        
        // Assert successful response
        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Notification created successfully',
            ]);
        
        // Verify notification was created in the database
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $recipient->id,
            'sender_id' => $sender->id,
            'recipient_role' => $recipient->role,
            'title' => 'Test Notification',
            'message' => 'This is a test notification message',
            'type' => 'custom',
        ]);
    }
    
    /**
     * Test creating a notification for a specific role.
     *
     * @return void
     */
    public function test_can_create_notification_for_role()
    {
        // Create a sender
        $sender = User::factory()->create(['role' => 'admin']);
        
        // Create multiple recipients with the same role
        $recipients = User::factory()->count(3)->create(['role' => 'student']);
        
        // Authenticate as the sender
        $this->actingAs($sender);
        
        // Create notification request data for all students
        $notificationData = [
            'recipient_role' => 'student',
            'title' => 'Test Role Notification',
            'message' => 'This is a test notification for all students',
            'type' => 'custom',
        ];
        
        // Send POST request to create notification
        $response = $this->postJson('/api/notifications', $notificationData);
        
        // Assert successful response
        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Notification created successfully',
            ]);
        
        // Verify notifications were created for all students
        foreach ($recipients as $recipient) {
            $this->assertDatabaseHas('notifications', [
                'recipient_id' => $recipient->id,
                'sender_id' => $sender->id,
                'recipient_role' => 'student',
                'title' => 'Test Role Notification',
                'message' => 'This is a test notification for all students',
                'type' => 'custom',
            ]);
        }
    }
    
    /**
     * Test validation fails when creating a notification without required fields.
     *
     * @return void
     */
    public function test_notification_creation_validation_fails()
    {
        // Create and authenticate user
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        
        // Missing required fields
        $response = $this->postJson('/api/notifications', []);
        
        // Assert validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'message', 'type']);
        
        // Missing recipient information
        $response = $this->postJson('/api/notifications', [
            'title' => 'Test Notification',
            'message' => 'This is a test message',
            'type' => 'custom'
        ]);
        
        // Assert error response
        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Either recipient_id or recipient_role must be provided',
            ]);
    }
}
