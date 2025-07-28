<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubCategory>
 */
class SubCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => $this->faker->unique()->word(),
        ];
    }

    /**
     * Indicate that the subcategory belongs to a specific category.
     *
     * @param int $categoryId
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forCategory(int $categoryId)
    {
        return $this->state(function (array $attributes) use ($categoryId) {
            return [
                'category_id' => $categoryId,
            ];
        });
    }
}
