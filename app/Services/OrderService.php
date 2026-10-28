<?php

namespace App\Services;

use App\Models\DiningTable;
use App\Models\Menu;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Buat pesanan baru dari item yang dikirim pelanggan.
     * Harga, pajak, dan total selalu dihitung ulang di server.
     */
    public function create(DiningTable $table, array $data): Order
    {
        $items = collect($data['items']);

        $menuIds = $items->pluck('menu_id')->unique()->values();

        $menus = Menu::query()
            ->whereIn('id', $menuIds)
            ->get()
            ->keyBy('id');

        $lineItems = [];

        foreach ($items as $item) {
            /** @var Menu|null $menu */
            $menu = $menus->get($item['menu_id']);

            if (! $menu || ! $menu->is_available) {
                throw ValidationException::withMessages([
                    'items' => 'Salah satu menu sudah tidak tersedia. Silakan perbarui pesanan Anda.',
                ]);
            }

            $quantity = (int) $item['quantity'];

            $lineItems[] = [
                'menu_id' => $menu->id,
                'menu_name' => $menu->name,
                'unit_price' => $menu->price,
                'quantity' => $quantity,
                'subtotal' => $menu->price * $quantity,
                'notes' => $item['notes'] ?? null,
            ];
        }

        $subtotal = (int) collect($lineItems)->sum('subtotal');
        $tax = (int) round($subtotal * (float) config('menuscan.tax_percent') / 100);
        $service = (int) round($subtotal * (float) config('menuscan.service_percent') / 100);
        $total = $subtotal + $tax + $service;

        return DB::transaction(function () use ($table, $data, $lineItems, $subtotal, $tax, $service, $total) {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'tracking_token' => (string) Str::uuid(),
                'dining_table_id' => $table->id,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => Order::STATUS_PENDING,
                'payment_status' => Order::PAYMENT_UNPAID,
                'payment_method' => $data['payment_method'],
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'service_amount' => $service,
                'total' => $total,
            ]);

            $order->items()->createMany($lineItems);

            return $order->load(['items', 'diningTable']);
        });
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'RN-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
