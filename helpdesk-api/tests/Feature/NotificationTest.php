<?php
namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_notifications()
    {
        $user = User::factory()->create();
        Notification::factory()->count(3)->create(['recipient_id' => $user->id]);
        $this->actingAs($user, 'sanctum');
        $response = $this->getJson('/api/notifications');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'notifications',
                    'pagination',
                ]
            ]);
    }

    public function test_user_can_mark_notification_as_read()
    {
        $user = User::factory()->create();
        $notif = Notification::factory()->create(['recipient_id' => $user->id, 'read_at' => null]);
        $this->actingAs($user, 'sanctum');
        $response = $this->patchJson('/api/notifications/' . $notif->id . '/read');
        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Notification marked as read']);
        $this->assertNotNull($notif->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read()
    {
        $user = User::factory()->create();
        Notification::factory()->count(2)->create(['recipient_id' => $user->id, 'read_at' => null]);
        $this->actingAs($user, 'sanctum');
        $response = $this->patchJson('/api/notifications/read-all');
        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'All notifications marked as read']);
        $this->assertEquals(0, Notification::where('recipient_id', $user->id)->whereNull('read_at')->count());
    }

    public function test_user_can_create_notification_manually()
    {
        $user = User::factory()->create();
        $recipient = User::factory()->create();
        $this->actingAs($user, 'sanctum');
        $payload = [
            'recipient_id' => $recipient->id,
            'recipientRole' => $recipient->role,
            'title' => 'Manual',
            'message' => 'Manual notification',
            'type' => 'custom',
        ];
        $response = $this->postJson('/api/notifications', $payload);
        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Notification created successfully']);
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $recipient->id,
            'title' => 'Manual',
        ]);
    }
}
