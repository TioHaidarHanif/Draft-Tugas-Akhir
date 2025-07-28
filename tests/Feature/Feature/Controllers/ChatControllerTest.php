<?php

namespace Tests\Feature\Feature\Controllers;

use App\Models\ChatAttachment;
use App\Models\ChatMessage;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup fake storage
        Storage::fake('public');
    }

    /**
     * Test getting chat messages for a ticket.
     */
    public function test_can_get_chat_messages(): void
    {
        // Create users
        $student = User::factory()->create(['role' => 'student']);
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create(['user_id' => $student->id]);
        
        // Create chat messages
        $message1 = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $student->id,
            'message' => 'Student message',
            'is_system_message' => false,
            'read_by' => [$student->id],
        ]);
        
        $message2 = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'message' => 'Admin response',
            'is_system_message' => false,
            'read_by' => [$admin->id],
        ]);
        
        // Authenticate as student
        Sanctum::actingAs($student);
        
        // Get chat messages
        $response = $this->getJson("/api/tickets/{$ticket->id}/chat");
        
        // Assert response
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.message', 'Student message')
            ->assertJsonPath('data.1.message', 'Admin response');
    }
    
    /**
     * Test creating a new chat message.
     */
    public function test_can_create_chat_message(): void
    {
        // Create a student user
        $student = User::factory()->create(['role' => 'student']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create(['user_id' => $student->id, 'status' => 'open']);
        
        // Authenticate as student
        Sanctum::actingAs($student);
        
        // Create a chat message
        $response = $this->postJson("/api/tickets/{$ticket->id}/chat", [
            'message' => 'This is a new message',
        ]);
        
        // Assert response
        $response->assertStatus(201)
            ->assertJsonPath('message', 'This is a new message')
            ->assertJsonPath('user_id', $student->id)
            ->assertJsonPath('ticket_id', $ticket->id);
        
        // Assert the message was created in the database
        $this->assertDatabaseHas('chat_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $student->id,
            'message' => 'This is a new message',
            'is_system_message' => false,
        ]);
    }
    
    /**
     * Test deleting a chat message.
     */
    public function test_can_delete_own_chat_message(): void
    {
        // Create a student user
        $student = User::factory()->create(['role' => 'student']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create(['user_id' => $student->id, 'status' => 'open']);
        
        // Create a chat message
        $chatMessage = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $student->id,
            'message' => 'This is a test message',
            'is_system_message' => false,
            'read_by' => [$student->id],
        ]);
        
        // Authenticate as student
        Sanctum::actingAs($student);
        
        // Delete the chat message
        $response = $this->deleteJson("/api/tickets/{$ticket->id}/chat/{$chatMessage->id}");
       
        // Assert response
        $response->assertStatus(200)
            ->assertJsonPath('message', 'Chat message deleted successfully');
        
        // Assert the message was soft deleted
        $this->assertSoftDeleted('chat_messages', [
            'id' => $chatMessage->id,
        ]);
    }
    
    /**
     * Test cannot delete another user's chat message.
     */
    public function test_cannot_delete_another_users_message(): void
    {
        // Create users
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create(['user_id' => $student1->id, 'status' => 'open']);
        
        // Create a chat message by student1
        $chatMessage = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $student1->id,
            'message' => 'This is a test message',
            'is_system_message' => false,
            'read_by' => [$student1->id],
        ]);
        
        // Authenticate as student2
        Sanctum::actingAs($student2);
        
        // Try to delete student1's message
        $response = $this->deleteJson("/api/tickets/{$ticket->id}/chat/{$chatMessage->id}");
        
        // Assert response (should be forbidden)
        $response->assertStatus(403);
        
        // Assert the message was NOT deleted
        $this->assertDatabaseHas('chat_messages', [
            'id' => $chatMessage->id,
            'deleted_at' => null,
        ]);
    }
    
    /**
     * Test admin can delete any chat message.
     */
    public function test_admin_can_delete_any_message(): void
    {
        // Create users
        $student = User::factory()->create(['role' => 'student']);
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create(['user_id' => $student->id, 'status' => 'open']);
        
        // Create a chat message by student
        $chatMessage = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $student->id,
            'message' => 'This is a test message',
            'is_system_message' => false,
            'read_by' => [$student->id],
        ]);
        
        // Authenticate as admin
        Sanctum::actingAs($admin);
        
        // Delete student's message
        $response = $this->deleteJson("/api/tickets/{$ticket->id}/chat/{$chatMessage->id}");
        
        // Assert response
        $response->assertStatus(200)
            ->assertJsonPath('message', 'Chat message deleted successfully');
        
        // Assert the message was soft deleted
        $this->assertSoftDeleted('chat_messages', [
            'id' => $chatMessage->id,
        ]);
    }
    
    /**
     * Test uploading an attachment to chat.
     */
    public function test_can_upload_attachment(): void
    {
        // Create a student user
        $student = User::factory()->create(['role' => 'student']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create(['user_id' => $student->id, 'status' => 'open']);
        
        // Authenticate as student
        Sanctum::actingAs($student);
        
        // Create a fake file
        $file = UploadedFile::fake()->create('document.pdf', 1000);
        
        // Upload the attachment
        $response = $this->postJson("/api/tickets/{$ticket->id}/chat/attachment", [
            'message' => 'Here is an attachment',
            'file' => $file,
        ]);
        
        // Assert response
        $response->assertStatus(201)
            ->assertJsonPath('message.message', 'Here is an attachment')
            ->assertJsonPath('message.user_id', $student->id)
            ->assertJsonPath('message.ticket_id', $ticket->id)
            ->assertJsonPath('attachment.file_name', 'document.pdf')
            ->assertJsonPath('attachment.file_type', 'application/pdf');
        
        // Assert the message and attachment were created in the database
        $this->assertDatabaseHas('chat_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $student->id,
            'message' => 'Here is an attachment',
            'is_system_message' => false,
        ]);
        
        $chatMessageId = $response->json('message.id');
        
        $this->assertDatabaseHas('chat_attachments', [
            'chat_message_id' => $chatMessageId,
            'file_name' => 'document.pdf',
            'file_type' => 'application/pdf',
        ]);
    }
    
    /**
     * Test getting all attachments for a ticket's chat.
     */
    public function test_can_get_all_attachments(): void
    {
        // Create a student user
        $student = User::factory()->create(['role' => 'student']);
        
        // Create a ticket
        $ticket = Ticket::factory()->create(['user_id' => $student->id]);
        
        // Create chat messages with attachments
        $chatMessage1 = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $student->id,
            'message' => 'Message with attachment 1',
            'is_system_message' => false,
            'read_by' => [$student->id],
        ]);
        
        $chatMessage2 = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $student->id,
            'message' => 'Message with attachment 2',
            'is_system_message' => false,
            'read_by' => [$student->id],
        ]);
        
        // Create attachments
        $attachment1 = ChatAttachment::create([
            'chat_message_id' => $chatMessage1->id,
            'file_name' => 'document1.pdf',
            'file_type' => 'application/pdf',
            'file_size' => '1024',
            'file_url' => 'http://example.com/document1.pdf',
        ]);
        
        $attachment2 = ChatAttachment::create([
            'chat_message_id' => $chatMessage2->id,
            'file_name' => 'document2.pdf',
            'file_type' => 'application/pdf',
            'file_size' => '2048',
            'file_url' => 'http://example.com/document2.pdf',
        ]);
        
        // Authenticate as student
        Sanctum::actingAs($student);
        
        // Get all attachments
        $response = $this->getJson("/api/tickets/{$ticket->id}/chat/attachments");
        
        // Assert response
        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonPath('0.file_name', 'document1.pdf')
            ->assertJsonPath('1.file_name', 'document2.pdf');
    }
    
    /**
     * Test unauthorized access to chat.
     */
    public function test_unauthorized_access_to_chat(): void
    {
        // Create users
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        
        // Create a ticket for student1
        $ticket = Ticket::factory()->create(['user_id' => $student1->id]);
        
        // Authenticate as student2 (who doesn't own the ticket)
        Sanctum::actingAs($student2);
        
        // Try to get chat messages
        $response = $this->getJson("/api/tickets/{$ticket->id}/chat");
        
        // Assert response (should be forbidden)
        $response->assertStatus(403);
        
        // Try to create a chat message
        $response = $this->postJson("/api/tickets/{$ticket->id}/chat", [
            'message' => 'This should fail',
        ]);
        
        // Assert response (should be forbidden)
        $response->assertStatus(403);
    }
}
