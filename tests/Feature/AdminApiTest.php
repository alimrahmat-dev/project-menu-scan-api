<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_login_and_read_dashboard(): void
    {
        $login = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@example.test',
            'password' => 'password',
        ])->assertOk()->json();

        $this->withHeader('Authorization', 'Bearer '.$login['token'])
            ->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'stats' => ['revenue_today', 'orders_today', 'active_orders'],
                'week',
                'top_menus',
                'recent_orders',
            ]);
    }

    public function test_login_rejects_wrong_password(): void
    {
        $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@example.test',
            'password' => 'salah',
        ])->assertStatus(422);
    }

    public function test_admin_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertUnauthorized();
        $this->getJson('/api/v1/admin/orders')->assertUnauthorized();
    }

    public function test_non_admin_user_cannot_login_to_admin_panel(): void
    {
        User::create([
            'name' => 'Kasir',
            'email' => 'kasir@example.test',
            'password' => Hash::make('password'),
            'role' => 'staff',
        ]);

        $this->postJson('/api/v1/admin/login', [
            'email' => 'kasir@example.test',
            'password' => 'password',
        ])->assertForbidden();
    }

    public function test_admin_can_manage_menu_categories_and_tables(): void
    {
        $token = $this->loginToken();

        $category = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/admin/categories', [
                'name' => 'Minuman',
                'icon' => '🥤',
                'sort_order' => 2,
            ])->assertCreated()->json('data');

        $menu = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/admin/menus', [
                'category_id' => $category['id'],
                'name' => 'Es Teh',
                'price' => 12000,
                'is_available' => true,
            ])->assertCreated()->json('data');

        $this->assertSame('es-teh', $menu['slug']);

        $table = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/admin/tables', [
                'code' => 't99',
                'name' => 'Meja 99',
                'capacity' => 4,
            ])->assertCreated()->json('data');

        $this->assertSame('T99', $table['code']);
        $this->assertStringContainsString('/t/', $table['menu_url']);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/admin/menus/{$menu['id']}")
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/admin/categories/{$category['id']}")
            ->assertOk();
    }

    public function test_admin_can_update_order_status_and_mark_cash_order_paid(): void
    {
        $token = $this->loginToken();
        $order = $this->makeOrder();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'preparing'])
            ->assertOk()
            ->assertJsonPath('data.status', 'preparing');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/admin/orders/{$order->id}/mark-paid")
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'paid');

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'manual',
            'status' => 'paid',
        ]);
    }

    public function test_admin_can_regenerate_table_qr_token(): void
    {
        $token = $this->loginToken();
        $table = DiningTable::create([
            'code' => 'T01', 'name' => 'Meja 01', 'capacity' => 2, 'is_active' => true,
        ]);
        $oldToken = $table->qr_token;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/admin/tables/{$table->id}/regenerate")
            ->assertOk();

        $this->assertNotSame($oldToken, $table->fresh()->qr_token);
    }

    private function loginToken(): string
    {
        return $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@example.test',
            'password' => 'password',
        ])->json('token');
    }

    private function makeOrder(): Order
    {
        $category = Category::create(['name' => 'Makanan', 'sort_order' => 1, 'is_active' => true]);
        $menu = Menu::create([
            'category_id' => $category->id,
            'name' => 'Nasi Goreng',
            'price' => 40000,
            'is_available' => true,
        ]);
        $table = DiningTable::create([
            'code' => 'T02', 'name' => 'Meja 02', 'capacity' => 2, 'is_active' => true,
        ]);

        $order = Order::create([
            'order_number' => 'RN-TEST-0001',
            'tracking_token' => (string) Str::uuid(),
            'dining_table_id' => $table->id,
            'customer_name' => 'Budi',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
            'subtotal' => 40000,
            'tax_amount' => 4000,
            'service_amount' => 2000,
            'total' => 46000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'unit_price' => 40000,
            'quantity' => 1,
            'subtotal' => 40000,
        ]);

        return $order;
    }
}
