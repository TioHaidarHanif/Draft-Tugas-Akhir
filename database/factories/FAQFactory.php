<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FAQ>
 */
class FAQFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question' => $this->faker->sentence(rand(5, 10)) . '?',
            'answer' => $this->faker->paragraphs(rand(2, 5), true),
            'category_id' => Category::factory(),
            'user_id' => User::factory(),
            'is_public' => $this->faker->boolean(80), // 80% chance of being public
            'view_count' => $this->faker->numberBetween(0, 1000),
        ];
    }

    /**
     * Indicate that the FAQ is public.
     *
     * @return $this
     */
    public function public()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_public' => true,
            ];
        });
    }

    /**
     * Indicate that the FAQ is private.
     *
     * @return $this
     */
    public function private()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_public' => false,
            ];
        });
    }
}
