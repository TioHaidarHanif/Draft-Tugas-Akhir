<?php

namespace Database\Factories;

// Laravel 8+ factory auto-discovery fix
use Illuminate\Support\Str;

use App\Models\Faq;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            'question' => $this->faker->sentence(),
            'answer' => $this->faker->paragraph(),
            'category_id' => Category::factory(),
            'created_by' => User::factory(),
            'ticket_id' => null,
        ];
    }
}
