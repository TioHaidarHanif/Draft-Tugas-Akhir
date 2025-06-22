<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Ensure we have at least one category and subcategory
        $category = Category::first() ?? Category::factory()->create();
        $subCategory = SubCategory::where('category_id', $category->id)->first() ?? 
                       SubCategory::factory()->create(['category_id' => $category->id]);
        
        // Get a student user or create one
        $student = User::where('role', 'student')->first() ?? 
                   User::factory()->create(['role' => 'student']);
        
        return [
            'user_id' => $student->id,
            'anonymous' => $this->faker->boolean(20), // 20% chance of being anonymous
            'judul' => $this->faker->sentence(),
            'deskripsi' => $this->faker->paragraphs(3, true),
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'status' => $this->faker->randomElement(['open', 'in_progress', 'resolved', 'closed']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'assigned_to' => null,
            'nim' => $this->faker->numerify('########'),
            'nama' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'prodi' => $this->faker->randomElement(['Informatika', 'Sistem Informasi', 'Teknik Elektro']),
            'semester' => $this->faker->numberBetween(1, 8),
            'no_hp' => $this->faker->phoneNumber(),
            'read_by_admin' => false,
            'read_by_disposisi' => false,
            'read_by_student' => true,
        ];
    }
    
    /**
     * Indicate that the ticket is open.
     *
     * @return static
     */
    public function open()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
        ]);
    }
    
    /**
     * Indicate that the ticket is in progress.
     *
     * @return static
     */
    public function inProgress()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }
    
    /**
     * Indicate that the ticket is resolved.
     *
     * @return static
     */
    public function resolved()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
        ]);
    }
    
    /**
     * Indicate that the ticket is closed.
     *
     * @return static
     */
    public function closed()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }
    
    /**
     * Indicate that the ticket is assigned to a specific user.
     *
     * @param mixed $userId
     * @return static
     */
    public function assignedTo($userId)
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $userId,
            'status' => 'in_progress',
        ]);
    }
    
    /**
     * Indicate that the ticket is anonymous.
     *
     * @return static
     */
    public function anonymous()
    {
        return $this->state(fn (array $attributes) => [
            'anonymous' => true,
        ]);
    }
}
