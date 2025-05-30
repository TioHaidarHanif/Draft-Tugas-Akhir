<?php

namespace Tests\Feature\Feature\Models;

use App\Models\ChatAttachment;
use App\Models\ChatMessage;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ChatAttachmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test creating a chat attachment.
     */
    public function test_can_create_chat_attachment(): void
    {
        // Create a user
        $user = User::factory()->create(['role' => 'student']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        
        // Create a chat message
        $chatMessage = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'This is a test message with attachment',
            'is_system_message' => false,
            'read_by' => [$user->id],
        ]);
        
        // Create a chat attachment
        $chatAttachment = ChatAttachment::create([
            'chat_message_id' => $chatMessage->id,
            'file_name' => 'test.txt',
            'file_type' => 'text/plain',
            'file_size' => '1024',
            'file_url' => 'http://example.com/test.txt',
        ]);
        
        // Assert the chat attachment was created
        $this->assertDatabaseHas('chat_attachments', [
            'id' => $chatAttachment->id,
            'chat_message_id' => $chatMessage->id,
            'file_name' => 'test.txt',
            'file_type' => 'text/plain',
            'file_size' => '1024',
            'file_url' => 'http://example.com/test.txt',
        ]);
    }
    
    /**
     * Test chat attachment relationship with chat message.
     */
    public function test_chat_attachment_belongs_to_chat_message(): void
    {
        // Create a user
        $user = User::factory()->create(['role' => 'student']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        
        // Create a chat message
        $chatMessage = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'This is a test message with attachment',
            'is_system_message' => false,
            'read_by' => [$user->id],
        ]);
        
        // Create a chat attachment
        $chatAttachment = ChatAttachment::create([
            'chat_message_id' => $chatMessage->id,
            'file_name' => 'test.txt',
            'file_type' => 'text/plain',
            'file_size' => '1024',
            'file_url' => 'http://example.com/test.txt',
        ]);
        
        // Assert relationship
        $this->assertInstanceOf(ChatMessage::class, $chatAttachment->chatMessage);
        $this->assertEquals($chatMessage->id, $chatAttachment->chatMessage->id);
    }
    
    /**
     * Test deleting a chat message cascades to its attachments.
     */
    public function test_deleting_chat_message_deletes_attachments(): void
    {
        // Create a user
        $user = User::factory()->create(['role' => 'student']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        
        // Create a chat message
        $chatMessage = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'This is a test message with attachment',
            'is_system_message' => false,
            'read_by' => [$user->id],
        ]);
        
        // Create chat attachments
        $chatAttachment = ChatAttachment::create([
            'chat_message_id' => $chatMessage->id,
            'file_name' => 'test.txt',
            'file_type' => 'text/plain',
            'file_size' => '1024',
            'file_url' => 'http://example.com/test.txt',
        ]);
        
        // Delete the chat message
        $chatMessage->delete();
        
        // Assert the chat message is soft deleted
        $this->assertSoftDeleted('chat_messages', [
            'id' => $chatMessage->id,
        ]);
        
        // When a chat message is deleted, its attachments should still be in the database
        // because we're using soft deletes on the chat message
        $this->assertDatabaseHas('chat_attachments', [
            'id' => $chatAttachment->id,
        ]);
        
        // But when we force delete, the attachments should be deleted due to cascade
        $chatMessage->forceDelete();
        
        // Assert the chat attachment was deleted
        $this->assertDatabaseMissing('chat_attachments', [
            'id' => $chatAttachment->id,
        ]);
    }
}
