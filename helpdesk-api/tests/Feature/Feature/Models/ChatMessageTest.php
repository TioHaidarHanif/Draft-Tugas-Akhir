<?php

namespace Tests\Feature\Feature\Models;

use App\Models\ChatAttachment;
use App\Models\ChatMessage;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ChatMessageTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test creating a chat message.
     */
    public function test_can_create_chat_message(): void
    {
        // Create a user
        $user = User::factory()->create(['role' => 'student']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        
        // Create a chat message
        $chatMessage = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'This is a test message',
            'is_system_message' => false,
            'read_by' => [$user->id],
        ]);
        
        // Assert the chat message was created
        $this->assertDatabaseHas('chat_messages', [
            'id' => $chatMessage->id,
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'This is a test message',
            'is_system_message' => false,
        ]);
    }
    
    /**
     * Test chat message relationships.
     */
    public function test_chat_message_relationships(): void
    {
        // Create a user
        $user = User::factory()->create(['role' => 'student']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        
        // Create a chat message
        $chatMessage = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'This is a test message',
            'is_system_message' => false,
            'read_by' => [$user->id],
        ]);
        
        // Create chat attachments
        $attachment1 = ChatAttachment::create([
            'chat_message_id' => $chatMessage->id,
            'file_name' => 'test1.txt',
            'file_type' => 'text/plain',
            'file_size' => '1024',
            'file_url' => 'http://example.com/test1.txt',
        ]);
        
        $attachment2 = ChatAttachment::create([
            'chat_message_id' => $chatMessage->id,
            'file_name' => 'test2.txt',
            'file_type' => 'text/plain',
            'file_size' => '2048',
            'file_url' => 'http://example.com/test2.txt',
        ]);
        
        // Assert relationships
        $this->assertInstanceOf(User::class, $chatMessage->user);
        $this->assertEquals($user->id, $chatMessage->user->id);
        
        $this->assertInstanceOf(Ticket::class, $chatMessage->ticket);
        $this->assertEquals($ticket->id, $chatMessage->ticket->id);
        
        $this->assertCount(2, $chatMessage->attachments);
        $this->assertInstanceOf(ChatAttachment::class, $chatMessage->attachments->first());
    }
    
    /**
     * Test soft deleting a chat message.
     */
    public function test_can_soft_delete_chat_message(): void
    {
        // Create a user
        $user = User::factory()->create(['role' => 'student']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        
        // Create a chat message
        $chatMessage = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'This is a test message',
            'is_system_message' => false,
            'read_by' => [$user->id],
        ]);
        
        // Delete the chat message
        $chatMessage->delete();
        
        // Assert the chat message is soft deleted
        $this->assertSoftDeleted('chat_messages', [
            'id' => $chatMessage->id,
        ]);
    }
    
    /**
     * Test reading chat messages and marking as read.
     */
    public function test_can_mark_chat_message_as_read(): void
    {
        // Create users
        $user1 = User::factory()->create(['role' => 'student']);
        $user2 = User::factory()->create(['role' => 'admin']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create(['user_id' => $user1->id]);
        
        // Create a chat message
        $chatMessage = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user1->id,
            'message' => 'This is a test message',
            'is_system_message' => false,
            'read_by' => [$user1->id], // Initially read by sender only
        ]);
        
        // Mark as read by user2
        $readBy = $chatMessage->read_by;
        $readBy[] = $user2->id;
        $chatMessage->read_by = $readBy;
        $chatMessage->save();
        
        // Assert the chat message is marked as read by both users
        $this->assertDatabaseHas('chat_messages', [
            'id' => $chatMessage->id,
        ]);
        
        $refreshedMessage = ChatMessage::find($chatMessage->id);
        $this->assertContains($user1->id, $refreshedMessage->read_by);
        $this->assertContains($user2->id, $refreshedMessage->read_by);
    }
}
