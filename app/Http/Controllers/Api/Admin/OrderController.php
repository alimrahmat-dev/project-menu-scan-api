<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->with(['items', 'diningTable'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->string('payment_status')))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('created_at', $request->string('date')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = '%'.$request->string('q').'%';
                $query->where(fn ($inner) => $inner
                    ->where('order_number', 'like', $search)
                    ->orWhere('customer_name', 'like', $search)
                    ->orWhereHas('diningTable', fn ($table) => $table->where('code', 'like', $search)));
            })
            ->latest()
            ->paginate(min(max($request->integer('per_page', 20), 5), 100));

        return OrderResource::collection($orders);
    }

    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load(['items', 'diningTable', 'payments']));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $order->forceFill(['status' => $request->validated('status')])->save();

        return response()->json([
            'message' => 'Status pesanan diperbarui.',
            'data' => new OrderResource($order->load(['items', 'diningTable'])),
        ]);
    }

    public function markPaid(Order $order): JsonResponse
    {
        if ($order->isPaid()) {
            return response()->json([
                'message' => 'Pesanan ini sudah lunas.',
                'data' => new OrderResource($order->load(['items', 'diningTable'])),
            ]);
        }

        if ($order->status === Order::STATUS_CANCELLED) {
            return response()->json(['message' => 'Pesanan yang dibatalkan tidak dapat ditandai lunas.'], 422);
        }

        DB::transaction(function () use ($order) {
            $reference = 'MANUAL-'.Str::upper(Str::random(6));

            $order->markAsPaid($order->payment_method ?: 'cash', $reference);

            Payment::create([
                'order_id' => $order->id,
                'provider' => 'manual',
                'reference' => $reference,
                'status' => Order::PAYMENT_PAID,
                'amount' => $order->total,
                'payload' => ['marked_by' => 'admin'],
            ]);
        });

        return response()->json([
            'message' => 'Pesanan ditandai lunas.',
            'data' => new OrderResource($order->fresh(['items', 'diningTable'])),
        ]);
    }
}
