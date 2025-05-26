<?php
namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition()
    {
        return [
            'recipient_id' => User::factory(),
            'recipient_role' => 'student',
            'sender_id' => User::factory(),
            'ticket_id' => null,
            'title' => $this->faker->sentence,
            'message' => $this->faker->paragraph,
            'type' => 'custom',
            'read_at' => null,
        ];
    }
}
