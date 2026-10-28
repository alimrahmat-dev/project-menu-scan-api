<?php

namespace Database\Seeders;

use App\Models\DiningTable;
use App\Models\Menu;
use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (Order::query()->exists()) {
            return;
        }

        $menus = Menu::query()->where('is_available', true)->get();
        $tables = DiningTable::query()->orderBy('code')->get();

        if ($menus->isEmpty() || $tables->isEmpty()) {
            return;
        }

        $names = ['Budi Santoso', 'Siti Rahma', 'Andi Wijaya', 'Maya Putri', 'Rizky Pratama', 'Dewi Lestari'];
        $statuses = [Order::STATUS_COMPLETED, Order::STATUS_COMPLETED, Order::STATUS_PREPARING, Order::STATUS_READY];

        for ($day = 6; $day >= 0; $day--) {
            $ordersToday = $day === 0 ? 4 : random_int(2, 4);

            for ($i = 0; $i < $ordersToday; $i++) {
                $createdAt = now()->subDays($day)->setTime(random_int(11, 20), random_int(0, 59));

                $selected = $menus->random(random_int(1, 3));
                $items = [];
                $subtotal = 0;

                foreach ($selected as $menu) {
                    $quantity = random_int(1, 2);
                    $lineTotal = $menu->price * $quantity;
                    $subtotal += $lineTotal;
                    $items[] = [
                        'menu_id' => $menu->id,
                        'menu_name' => $menu->name,
                        'unit_price' => $menu->price,
                        'quantity' => $quantity,
                        'subtotal' => $lineTotal,
                    ];
                }

                $tax = (int) round($subtotal * 0.10);
                $service = (int) round($subtotal * 0.05);
                $total = $subtotal + $tax + $service;

                $isToday = $day === 0;
                $status = $isToday ? $statuses[array_rand($statuses)] : Order::STATUS_COMPLETED;
                $paymentStatus = $isToday && $i === 0 ? Order::PAYMENT_UNPAID : Order::PAYMENT_PAID;

                $methods = ['qris', 'ewallet', 'va', 'cash'];

                $order = Order::create([
                    'order_number' => 'RN-'.$createdAt->format('ymd').'-'.Str::upper(Str::random(5)),
                    'tracking_token' => (string) Str::uuid(),
                    'dining_table_id' => $tables->random()->id,
                    'customer_name' => $names[array_rand($names)],
                    'status' => $status,
                    'payment_status' => $paymentStatus,
                    'payment_method' => $methods[array_rand($methods)],
                    'subtotal' => $subtotal,
                    'tax_amount' => $tax,
                    'service_amount' => $service,
                    'total' => $total,
                    'paid_at' => $paymentStatus === Order::PAYMENT_PAID ? $createdAt->copy()->addMinutes(10) : null,
                ]);

                $order->items()->createMany($items);

                $order->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

                if ($paymentStatus === Order::PAYMENT_PAID) {
                    $order->payments()->create([
                        'provider' => 'demo',
                        'reference' => 'DEMO-'.Str::upper(Str::random(8)),
                        'status' => Order::PAYMENT_PAID,
                        'amount' => $total,
                        'payload' => ['seeded' => true],
                    ]);
                }
            }
        }
    }
}
