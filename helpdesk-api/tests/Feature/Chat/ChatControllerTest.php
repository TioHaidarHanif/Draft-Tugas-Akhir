<?php
namespace Tests\Feature\Chat;

use App\Models\User;
use App\Models\Ticket;
use App\Models\ChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_post_and_get_chat_message()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson("/api/tickets/{$ticket->id}/chat", [
            'message' => 'Hello, this is a chat message.'
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('chat_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'Hello, this is a chat message.'
        ]);
        $get = $this->getJson("/api/tickets/{$ticket->id}/chat");
        $get->assertStatus(200)->assertJsonStructure(['messages']);
    }

    public function test_user_can_upload_chat_attachment()
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user, 'sanctum');
        $msg = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'Attachment test',
        ]);
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        $response = $this->postJson("/api/tickets/{$ticket->id}/chat/attachment", [
            'chat_message_id' => $msg->id,
            'file' => $file,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('chat_attachments', [
            'chat_message_id' => $msg->id,
            'file_name' => 'test.pdf',
        ]);
    }

    public function test_user_can_delete_own_chat_message()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user, 'sanctum');
        $msg = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'To be deleted',
        ]);
        $response = $this->deleteJson("/api/tickets/{$ticket->id}/chat/{$msg->id}");
        $response->assertStatus(200);
        $this->assertSoftDeleted('chat_messages', ['id' => $msg->id]);
    }

    public function test_user_cannot_delete_others_chat_message()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $msg = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $other->id,
            'message' => 'Not yours',
        ]);
        $this->actingAs($user, 'sanctum');
        $response = $this->deleteJson("/api/tickets/{$ticket->id}/chat/{$msg->id}");
        $response->assertStatus(403);
    }

    public function test_user_can_get_chat_attachments()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user, 'sanctum');
        $msg = ChatMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'Attachment',
        ]);
        $msg->attachments()->create([
            'file_path' => 'chat_attachments/test.pdf',
            'file_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
        ]);
        $response = $this->getJson("/api/tickets/{$ticket->id}/chat/attachments");
        $response->assertStatus(200)->assertJsonStructure(['attachments']);
    }
}
