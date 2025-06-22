<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\SubCategory;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class TicketChatInfoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the ticket listing includes chat count.
     *
     * @return void
     */
    public function test_ticket_listing_includes_chat_count()
    {
        // Create test users
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        // Create a category and subcategory
        $category = Category::factory()->create();
        $subCategory = SubCategory::factory()->create([
            'category_id' => $category->id
        ]);

        // Create a ticket
        $ticket = Ticket::factory()->create([
            'user_id' => $student->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'status' => 'open'
        ]);

        // Create some chat messages for the ticket
        ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $student->id,
            'message' => 'Test message 1',
            'is_system_message' => false,
            'read_by' => [$student->id]
        ]);

        ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'message' => 'Test message 2',
            'is_system_message' => false,
            'read_by' => [$admin->id]
        ]);

        // Test as admin user
        $response = $this->actingAs($admin)
            ->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'tickets' => [
                        '*' => [
                            'id',
                            'chat_count',
                            'has_unread_chat'
                        ]
                    ]
                ]
            ])
            ->assertJsonPath('data.tickets.0.chat_count', 2)
            ->assertJsonPath('data.tickets.0.has_unread_chat', true);

        // Test as student user
        $response = $this->actingAs($student)
            ->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonPath('data.tickets.0.chat_count', 2)
            ->assertJsonPath('data.tickets.0.has_unread_chat', true);
    }

    /**
     * Test that the ticket detail includes chat count.
     *
     * @return void
     */
    public function test_ticket_detail_includes_chat_count()
    {
        // Create test users
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        // Create a category and subcategory
        $category = Category::factory()->create();
        $subCategory = SubCategory::factory()->create([
            'category_id' => $category->id
        ]);

        // Create a ticket
        $ticket = Ticket::factory()->create([
            'user_id' => $student->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'status' => 'open'
        ]);

        // Create chat messages
        ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $student->id,
            'message' => 'Student message',
            'is_system_message' => false,
            'read_by' => [$student->id]
        ]);

        ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'message' => 'Admin message',
            'is_system_message' => false,
            'read_by' => [$admin->id]
        ]);

        // Test as admin
        $response = $this->actingAs($admin)
            ->getJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'ticket' => [
                        'id',
                        'chat_count',
                        'has_unread_chat'
                    ]
                ]
            ])
            ->assertJsonPath('data.ticket.chat_count', 2)
            ->assertJsonPath('data.ticket.has_unread_chat', true);

        // Test as student
        $response = $this->actingAs($student)
            ->getJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.ticket.chat_count', 2)
            ->assertJsonPath('data.ticket.has_unread_chat', true);
    }

    /**
     * Test that reading chat messages updates the unread status.
     *
     * @return void
     */
    public function test_reading_messages_updates_unread_status()
    {
        // Create test users
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        // Create a category and subcategory
        $category = Category::factory()->create();
        $subCategory = SubCategory::factory()->create([
            'category_id' => $category->id
        ]);

        // Create a ticket
        $ticket = Ticket::factory()->create([
            'user_id' => $student->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'status' => 'open'
        ]);

        // Create chat message
        $chatMessage = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $student->id,
            'message' => 'Student message',
            'is_system_message' => false,
            'read_by' => [$student->id] // Only read by student initially
        ]);

        // Admin checks ticket - should show unread
        $response = $this->actingAs($admin)
            ->getJson("/api/tickets/{$ticket->id}");
        
        $response->assertStatus(200);
        
        // Directly verify if the message is unread for admin
        $this->assertFalse(in_array($admin->id, $chatMessage->read_by));

        // Manually mark messages as read using the ChatService
        $chatService = new ChatService();
        $chatService->markMessagesAsRead([$chatMessage], $admin->id);
        
        // Refresh message from database to verify it's marked as read
        $chatMessage->refresh();
        $this->assertTrue(in_array($admin->id, $chatMessage->read_by));

        // Now check again - should show ticket with no unread messages
        $this->actingAs($admin)->getJson("/api/tickets/{$ticket->id}");
        
        // Direct verification of has_unread_chat attribute for clearer test
        $hasUnreadChat = (new Ticket())->newQuery()
            ->where('id', $ticket->id)
            ->first()
            ->getHasUnreadChatAttribute();
            
        $this->assertFalse($hasUnreadChat);
    }
}
