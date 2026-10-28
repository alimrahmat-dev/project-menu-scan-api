<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Makanan Utama', 'icon' => '🍛', 'description' => 'Hidangan berat khas Nusantara', 'sort_order' => 1],
            ['name' => 'Pembuka', 'icon' => '🥗', 'description' => 'Camilan ringan untuk membuka selera', 'sort_order' => 2],
            ['name' => 'Camilan', 'icon' => '🍢', 'description' => 'Teman santai di sore hari', 'sort_order' => 3],
            ['name' => 'Minuman', 'icon' => '🥤', 'description' => 'Segar, hangat, dan menyehatkan', 'sort_order' => 4],
            ['name' => 'Dessert', 'icon' => '🍮', 'description' => 'Penutup manis untuk hari Anda', 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                [...$category, 'is_active' => true],
            );
        }
    }
}
