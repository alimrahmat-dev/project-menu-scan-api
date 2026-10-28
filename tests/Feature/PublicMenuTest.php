<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_endpoint_returns_categories_and_available_menus_only(): void
    {
        $category = Category::create([
            'name' => 'Makanan Utama', 'icon' => '🍛', 'sort_order' => 1, 'is_active' => true,
        ]);

        Menu::create([
            'category_id' => $category->id,
            'name' => 'Tersedia',
            'price' => 25000,
            'is_available' => true,
            'is_featured' => true,
        ]);

        Menu::create([
            'category_id' => $category->id,
            'name' => 'Habis',
            'price' => 30000,
            'is_available' => false,
        ]);

        $this->getJson('/api/v1/menu')
            ->assertOk()
            ->assertJsonCount(1, 'categories')
            ->assertJsonCount(1, 'categories.0.menus')
            ->assertJsonPath('categories.0.menus.0.name', 'Tersedia')
            ->assertJsonPath('tax_percent', 10)
            ->assertJsonPath('service_percent', 5);
    }

    public function test_table_can_be_opened_using_qr_token(): void
    {
        $table = DiningTable::create([
            'code' => 'T01', 'name' => 'Meja 01', 'capacity' => 2, 'is_active' => true,
        ]);

        $this->getJson('/api/v1/tables/'.$table->qr_token)
            ->assertOk()
            ->assertJsonPath('data.code', 'T01')
            ->assertJsonPath('data.menu_url', config('menuscan.frontend_url').'/t/'.$table->qr_token);

        $this->getJson('/api/v1/tables/tidak-valid')->assertNotFound();
    }

    public function test_inactive_table_is_rejected(): void
    {
        $table = DiningTable::create([
            'code' => 'T02', 'name' => 'Meja 02', 'capacity' => 2, 'is_active' => false,
        ]);

        $this->getJson('/api/v1/tables/'.$table->qr_token)->assertNotFound();
    }
}
