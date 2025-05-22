<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Check if tables exist before seeding to prevent errors
        if (!\Schema::hasTable('categories')) {
            $this->call(CategorySeeder::class);
        }
        
        if (!\Schema::hasTable('users') || \App\Models\User::count() === 0) {
            // Only seed users if the table is empty
            \App\Models\User::factory(10)->create();
        }
    }
}