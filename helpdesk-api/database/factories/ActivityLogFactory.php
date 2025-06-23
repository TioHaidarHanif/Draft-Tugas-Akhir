<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition()
    {
        return [
            'id' => (string) Str::uuid(),
            'user_id' => null,
            'activity' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'created_at' => now(),
        ];
    }
}
