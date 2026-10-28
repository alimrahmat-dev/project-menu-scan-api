<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MidtransNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_KEY = 'SB-Mid-server-test-key';

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.midtrans.mode' => 'midtrans',
            'services.midtrans.server_key' => self::SERVER_KEY,
            'services.midtrans.client_key' => 'SB-Mid-client-test',
        ]);

        $table = DiningTable::create([
            'code' => 'T01', 'name' => 'Meja 01', 'capacity' => 2, 'is_active' => true,
        ]);

        $this->order = Order::create([
            'order_number' => 'RN-TEST-0001',
            'tracking_token' => (string) Str::uuid(),
            'dining_table_id' => $table->id,
            'customer_name' => 'Budi',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'qris',
            'subtotal' => 100000,
            'tax_amount' => 10000,
            'service_amount' => 5000,
            'total' => 115000,
            'payment_reference' => 'RN-TEST-0001',
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'menu_name' => 'Nasi Goreng',
            'unit_price' => 100000,
            'quantity' => 1,
            'subtotal' => 100000,
        ]);

        Payment::create([
            'order_id' => $this->order->id,
            'provider' => 'midtrans',
            'reference' => 'RN-TEST-0001',
            'status' => 'pending',
            'amount' => 115000,
        ]);
    }

    public function test_valid_settlement_notification_marks_order_paid(): void
    {
        $grossAmount = '115000.00';

        $payload = [
            'order_id' => 'RN-TEST-0001',
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'signature_key' => hash('sha512', 'RN-TEST-0001'.'200'.$grossAmount.self::SERVER_KEY),
        ];

        $this->postJson('/api/v1/payments/midtrans/notification', $payload)
            ->assertOk()
            ->assertJsonPath('payment_status', 'paid');

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $this->postJson('/api/v1/payments/midtrans/notification', [
            'order_id' => 'RN-TEST-0001',
            'status_code' => '200',
            'gross_amount' => '115000.00',
            'transaction_status' => 'settlement',
            'signature_key' => 'invalid',
        ])->assertStatus(400);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'payment_status' => 'pending',
        ]);
    }

    public function test_expired_notification_marks_payment_expired(): void
    {
        $grossAmount = '115000.00';

        $this->postJson('/api/v1/payments/midtrans/notification', [
            'order_id' => 'RN-TEST-0001',
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'transaction_status' => 'expire',
            'signature_key' => hash('sha512', 'RN-TEST-0001'.'200'.$grossAmount.self::SERVER_KEY),
        ])->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'payment_status' => 'expired',
        ]);
    }
}
