<?php
namespace Database\Factories;

use App\Models\ChatMessageRead;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatMessageReadFactory extends Factory
{
    protected $model = ChatMessageRead::class;

    public function definition(): array
    {
        return [
            'chat_message_id' => ChatMessage::factory(),
            'user_id' => User::factory(),
            'read_at' => now(),
        ];
    }
}
