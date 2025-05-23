<?php

namespace Tests\Feature\Models;

use App\Models\Notification;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $adminUser;
    protected $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with different roles
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Create a category
        $category = Category::create([
            'name' => 'Test Category',
        ]);

        // Create a subcategory
        $subCategory = SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Test SubCategory',
        ]);

        // Create a ticket
        $this->ticket = Ticket::create([
            'user_id' => $this->user->id,
            'judul' => 'Test Ticket',
            'deskripsi' => 'This is a test ticket',
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'status' => 'open',
        ]);
    }

    public function test_can_create_notification(): void
    {
        $notification = Notification::create([
            'recipient_id' => $this->user->id,
            'recipient_role' => 'user',
            'sender_id' => $this->adminUser->id,
            'ticket_id' => $this->ticket->id,
            'title' => 'Ticket Update',
            'message' => 'Your ticket has been updated.',
            'type' => 'ticket_update',
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $this->user->id,
            'title' => 'Ticket Update',
            'message' => 'Your ticket has been updated.',
        ]);
    }

    public function test_notification_belongs_to_recipient(): void
    {
        $notification = Notification::create([
            'recipient_id' => $this->user->id,
            'recipient_role' => 'user',
            'sender_id' => $this->adminUser->id,
            'ticket_id' => $this->ticket->id,
            'title' => 'Ticket Update',
            'message' => 'Your ticket has been updated.',
            'type' => 'ticket_update',
        ]);

        $this->assertEquals($this->user->id, $notification->recipient->id);
        $this->assertEquals('Test User', $notification->recipient->name);
    }

    public function test_notification_belongs_to_sender(): void
    {
        $notification = Notification::create([
            'recipient_id' => $this->user->id,
            'recipient_role' => 'user',
            'sender_id' => $this->adminUser->id,
            'ticket_id' => $this->ticket->id,
            'title' => 'Ticket Update',
            'message' => 'Your ticket has been updated.',
            'type' => 'ticket_update',
        ]);

        $this->assertEquals($this->adminUser->id, $notification->sender->id);
        $this->assertEquals('Admin User', $notification->sender->name);
    }

    public function test_notification_belongs_to_ticket(): void
    {
        $notification = Notification::create([
            'recipient_id' => $this->user->id,
            'recipient_role' => 'user',
            'sender_id' => $this->adminUser->id,
            'ticket_id' => $this->ticket->id,
            'title' => 'Ticket Update',
            'message' => 'Your ticket has been updated.',
            'type' => 'ticket_update',
        ]);

        $this->assertEquals($this->ticket->id, $notification->ticket->id);
        $this->assertEquals('Test Ticket', $notification->ticket->judul);
    }

    public function test_notification_has_uuid(): void
    {
        $notification = Notification::create([
            'recipient_id' => $this->user->id,
            'recipient_role' => 'user',
            'sender_id' => $this->adminUser->id,
            'ticket_id' => $this->ticket->id,
            'title' => 'Ticket Update',
            'message' => 'Your ticket has been updated.',
            'type' => 'ticket_update',
        ]);

        $this->assertIsString($notification->id);
        $this->assertNotEquals(1, $notification->id);
        // Check if it's a valid UUID format
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $notification->id);
    }

    public function test_notification_can_be_marked_as_read(): void
    {
        $notification = Notification::create([
            'recipient_id' => $this->user->id,
            'recipient_role' => 'user',
            'sender_id' => $this->adminUser->id,
            'ticket_id' => $this->ticket->id,
            'title' => 'Ticket Update',
            'message' => 'Your ticket has been updated.',
            'type' => 'ticket_update',
        ]);

        $this->assertNull($notification->read_at);

        $notification->read_at = now();
        $notification->save();

        $this->assertNotNull($notification->refresh()->read_at);
    }
}
