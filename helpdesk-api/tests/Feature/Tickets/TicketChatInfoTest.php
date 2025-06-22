<?php

declare(strict_types=1);

namespace Tests\Feature\Tickets;

use App\Models\Ticket;
use App\Models\User;
use App\Models\ChatMessage;
use App\Models\ChatMessageRead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketChatInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_list_contains_chat_count_and_has_unread_chat()
    {
        $user = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'admin']);
        $token = $user->createToken('auth_token')->plainTextToken;
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        // 2 chat messages, 1 read, 1 unread
        $msg1 = ChatMessage::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $other->id]);
        $msg2 = ChatMessage::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $other->id]);
        ChatMessageRead::create(['chat_message_id' => $msg1->id, 'user_id' => $user->id, 'read_at' => now()]);
        // msg2 belum dibaca
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/tickets');
        $response->assertStatus(200)
            ->assertJsonFragment([
                'chat_count' => 2,
                'has_unread_chat' => true,
            ]);
    }

    public function test_ticket_detail_contains_chat_count_and_has_unread_chat()
    {
        $user = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'admin']);
        $token = $user->createToken('auth_token')->plainTextToken;
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $msg1 = ChatMessage::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $other->id]);
        ChatMessageRead::create(['chat_message_id' => $msg1->id, 'user_id' => $user->id, 'read_at' => now()]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/tickets/' . $ticket->id);
        $response->assertStatus(200)
            ->assertJsonFragment([
                'chat_count' => 1,
                'has_unread_chat' => false,
            ]);
    }
}
