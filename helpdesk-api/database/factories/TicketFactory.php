<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        $category = Category::factory()->create();
        $subCategory = SubCategory::factory()->create(['category_id' => $category->id]);
        return [
            'user_id' => User::factory(),
            'judul' => $this->faker->sentence(4),
            'deskripsi' => $this->faker->paragraph(),
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'status' => 'open',
            'prodi' => 'Informatika',
            'semester' => '6',
            'no_hp' => '08123456789',
            'anonymous' => false,
            'read_by_admin' => false,
            'read_by_disposisi' => false,
            'read_by_student' => false,
        ];
    }
}
