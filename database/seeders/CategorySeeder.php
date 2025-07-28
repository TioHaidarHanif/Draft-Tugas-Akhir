<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // IT Support category
        $itCategory = Category::create([
            'name' => 'IT Support',
        ]);

        SubCategory::create([
            'category_id' => $itCategory->id,
            'name' => 'Account Issues',
        ]);

        SubCategory::create([
            'category_id' => $itCategory->id,
            'name' => 'Network Problems',
        ]);

        SubCategory::create([
            'category_id' => $itCategory->id,
            'name' => 'Software Issues',
        ]);

        // Academic category
        $academicCategory = Category::create([
            'name' => 'Academic Affairs',
        ]);

        SubCategory::create([
            'category_id' => $academicCategory->id,
            'name' => 'Registration',
        ]);

        SubCategory::create([
            'category_id' => $academicCategory->id,
            'name' => 'Course Enrollment',
        ]);

        SubCategory::create([
            'category_id' => $academicCategory->id,
            'name' => 'Grades',
        ]);

        // Facilities category
        $facilitiesCategory = Category::create([
            'name' => 'Facilities',
        ]);

        SubCategory::create([
            'category_id' => $facilitiesCategory->id,
            'name' => 'Classroom Issues',
        ]);

        SubCategory::create([
            'category_id' => $facilitiesCategory->id,
            'name' => 'Laboratory Equipment',
        ]);

        SubCategory::create([
            'category_id' => $facilitiesCategory->id,
            'name' => 'Building Maintenance',
        ]);
    }
}
