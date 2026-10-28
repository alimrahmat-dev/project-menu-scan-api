<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\DiningTable;
use App\Models\Menu;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $today = now()->toDateString();

        $revenueToday = Order::query()
            ->where('payment_status', Order::PAYMENT_PAID)
            ->whereDate('paid_at', $today)
            ->sum('total');

        $ordersToday = Order::query()->whereDate('created_at', $today)->count();

        $paidOrdersToday = Order::query()
            ->where('payment_status', Order::PAYMENT_PAID)
            ->whereDate('paid_at', $today)
            ->count();

        $activeOrders = Order::query()
            ->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_CONFIRMED,
                Order::STATUS_PREPARING,
                Order::STATUS_READY,
            ])
            ->count();

        $week = collect(range(6, 0))->map(function (int $daysAgo) {
            $date = now()->subDays($daysAgo)->toDateString();

            return [
                'date' => $date,
                'label' => now()->subDays($daysAgo)->translatedFormat('D'),
                'revenue' => (int) Order::query()
                    ->where('payment_status', Order::PAYMENT_PAID)
                    ->whereDate('paid_at', $date)
                    ->sum('total'),
                'orders' => Order::query()->whereDate('created_at', $date)->count(),
            ];
        })->values();

        $topMenus = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', Order::PAYMENT_PAID)
            ->groupBy('order_items.menu_name')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get([
                'order_items.menu_name',
                DB::raw('SUM(order_items.quantity) as quantity'),
                DB::raw('SUM(order_items.subtotal) as revenue'),
            ]);

        $statusBreakdown = Order::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentOrders = Order::query()
            ->with(['items', 'diningTable'])
            ->latest()
            ->limit(6)
            ->get();

        return response()->json([
            'stats' => [
                'revenue_today' => (int) $revenueToday,
                'orders_today' => $ordersToday,
                'paid_orders_today' => $paidOrdersToday,
                'active_orders' => $activeOrders,
                'tables_active' => DiningTable::where('is_active', true)->count(),
                'menus_available' => Menu::where('is_available', true)->count(),
            ],
            'week' => $week,
            'top_menus' => $topMenus,
            'status_breakdown' => $statusBreakdown,
            'recent_orders' => OrderResource::collection($recentOrders),
        ]);
    }
}
