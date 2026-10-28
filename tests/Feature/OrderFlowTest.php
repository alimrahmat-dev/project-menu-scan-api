<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private DiningTable $table;

    private Menu $menu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = DiningTable::create([
            'code' => 'T01',
            'name' => 'Meja 01',
            'capacity' => 4,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Makanan Utama',
            'icon' => '🍛',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->menu = Menu::create([
            'category_id' => $category->id,
            'name' => 'Nasi Goreng',
            'description' => 'Nasi goreng kampung',
            'price' => 40000,
            'is_available' => true,
        ]);
    }

    public function test_customer_can_create_order_with_server_calculated_totals(): void
    {
        $response = $this->postJson('/api/v1/orders', $this->orderPayload());

        $response->assertCreated()
            ->assertJsonPath('data.subtotal', 80000)
            ->assertJsonPath('data.tax_amount', 8000)
            ->assertJsonPath('data.service_amount', 4000)
            ->assertJsonPath('data.total', 92000)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_status', 'unpaid');

        $this->assertNotEmpty($response->json('tracking_token'));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_order_cannot_be_read_or_paid_without_tracking_token(): void
    {
        $created = $this->postJson('/api/v1/orders', $this->orderPayload())->json();
        $number = $created['data']['order_number'];

        $this->getJson("/api/v1/orders/{$number}")->assertNotFound();
        $this->postJson("/api/v1/orders/{$number}/pay")->assertNotFound();

        $this->getJson("/api/v1/orders/{$number}?token=wrong-token")->assertNotFound();
    }

    public function test_customer_can_pay_with_mock_gateway_and_order_becomes_paid(): void
    {
        $created = $this->postJson('/api/v1/orders', $this->orderPayload())->json();
        $number = $created['data']['order_number'];
        $token = $created['tracking_token'];

        $this->postJson("/api/v1/orders/{$number}/pay", ['token' => $token])
            ->assertOk()
            ->assertJsonPath('payment.mode', 'mock')
            ->assertJsonPath('data.payment_status', 'pending');

        $this->postJson("/api/v1/orders/{$number}/mock-pay", [
            'token' => $token,
            'outcome' => 'success',
        ])
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('orders', [
            'order_number' => $number,
            'payment_status' => 'paid',
        ]);
    }

    public function test_failed_mock_payment_marks_order_as_failed_and_can_be_retried(): void
    {
        $created = $this->postJson('/api/v1/orders', $this->orderPayload())->json();
        $number = $created['data']['order_number'];
        $token = $created['tracking_token'];

        $this->postJson("/api/v1/orders/{$number}/pay", ['token' => $token])->assertOk();
        $this->postJson("/api/v1/orders/{$number}/mock-pay", [
            'token' => $token,
            'outcome' => 'failed',
        ])->assertOk()->assertJsonPath('data.payment_status', 'failed');

        $this->postJson("/api/v1/orders/{$number}/pay", ['token' => $token])->assertOk();
        $this->postJson("/api/v1/orders/{$number}/mock-pay", [
            'token' => $token,
            'outcome' => 'success',
        ])->assertOk()->assertJsonPath('data.payment_status', 'paid');
    }

    public function test_cash_order_cannot_be_paid_online(): void
    {
        $payload = $this->orderPayload();
        $payload['payment_method'] = 'cash';

        $created = $this->postJson('/api/v1/orders', $payload)->json();

        $this->postJson("/api/v1/orders/{$created['data']['order_number']}/pay", [
            'token' => $created['tracking_token'],
            'method' => 'cash',
        ])->assertStatus(422);
    }

    public function test_unpaid_order_can_be_cancelled_by_customer(): void
    {
        $created = $this->postJson('/api/v1/orders', $this->orderPayload())->json();

        $this->postJson("/api/v1/orders/{$created['data']['order_number']}/cancel", [
            'token' => $created['tracking_token'],
        ])->assertOk()->assertJsonPath('data.status', 'cancelled');
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(): array
    {
        return [
            'table_token' => $this->table->qr_token,
            'customer_name' => 'Budi',
            'customer_phone' => '081234567890',
            'payment_method' => 'qris',
            'items' => [
                ['menu_id' => $this->menu->id, 'quantity' => 2],
            ],
        ];
    }
}
