<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\DiningTable;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, OrderService $orderService): JsonResponse
    {
        $table = DiningTable::query()
            ->where('qr_token', $request->string('table_token'))
            ->where('is_active', true)
            ->firstOrFail();

        $order = $orderService->create($table, $request->validated());

        return response()->json([
            'message' => 'Pesanan berhasil dibuat.',
            'data' => new OrderResource($order),
            'tracking_token' => $order->tracking_token,
        ], 201);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        $this->ensureTrackingToken($request, $order);

        return new OrderResource($order->load(['items', 'diningTable']));
    }

    public function pay(Request $request, Order $order, PaymentService $paymentService): JsonResponse
    {
        $this->ensureTrackingToken($request, $order);

        $validated = $request->validate([
            'method' => ['nullable', 'in:qris,ewallet,va,cash'],
        ]);

        if ($order->status === Order::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'order' => 'Pesanan sudah dibatalkan dan tidak dapat dibayar.',
            ]);
        }

        $method = $validated['method'] ?? $order->payment_method;

        if ($method === 'cash') {
            throw ValidationException::withMessages([
                'method' => 'Pembayaran tunai dilakukan langsung di kasir. Tunjukkan nomor pesanan Anda.',
            ]);
        }

        if ($method && $method !== $order->payment_method) {
            $order->forceFill(['payment_method' => $method])->save();
        }

        if ($order->isPaid()) {
            return response()->json([
                'message' => 'Pesanan sudah dibayar.',
                'data' => new OrderResource($order->load(['items', 'diningTable'])),
                'payment' => [
                    'mode' => $paymentService->mode(),
                    'redirect_url' => null,
                    'already_paid' => true,
                ],
            ]);
        }

        // Jangan buat transaksi baru jika pembayaran masih menunggu.
        if ($order->payment_status === Order::PAYMENT_PENDING) {
            return response()->json([
                'message' => 'Pembayaran sedang menunggu penyelesaian.',
                'data' => new OrderResource($order->load(['items', 'diningTable'])),
                'payment' => [
                    'mode' => $paymentService->mode(),
                    'redirect_url' => $order->payment_url,
                    'already_pending' => true,
                ],
            ]);
        }

        try {
            $payment = $paymentService->createPayment($order);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghubungi penyedia pembayaran.',
            ], 502);
        }

        return response()->json([
            'message' => 'Silakan lanjutkan pembayaran.',
            'data' => new OrderResource($order->fresh(['items', 'diningTable'])),
            'payment' => $payment,
        ]);
    }

    public function mockPay(Request $request, Order $order, PaymentService $paymentService): JsonResponse
    {
        $this->ensureTrackingToken($request, $order);

        abort_unless($paymentService->isMock(), 403, 'Simulasi pembayaran hanya tersedia pada mode demo.');

        $validated = $request->validate([
            'outcome' => ['required', 'in:success,failed'],
        ]);

        if ($order->isPaid()) {
            return response()->json([
                'message' => 'Pesanan sudah dibayar.',
                'data' => new OrderResource($order->load(['items', 'diningTable'])),
            ]);
        }

        $order = $paymentService->completeMockPayment($order, $validated['outcome'] === 'success');

        return response()->json([
            'message' => $validated['outcome'] === 'success'
                ? 'Pembayaran berhasil disimulasikan.'
                : 'Pembayaran gagal disimulasikan.',
            'data' => new OrderResource($order),
        ]);
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        $this->ensureTrackingToken($request, $order);

        if ($order->isPaid()) {
            throw ValidationException::withMessages([
                'order' => 'Pesanan yang sudah dibayar tidak dapat dibatalkan. Silakan hubungi kasir.',
            ]);
        }

        if (in_array($order->status, [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages([
                'order' => 'Pesanan ini tidak dapat dibatalkan lagi.',
            ]);
        }

        $order->forceFill(['status' => Order::STATUS_CANCELLED])->save();

        return response()->json([
            'message' => 'Pesanan berhasil dibatalkan.',
            'data' => new OrderResource($order->load(['items', 'diningTable'])),
        ]);
    }

    private function ensureTrackingToken(Request $request, Order $order): void
    {
        $token = $request->query('token')
            ?? $request->header('X-Order-Token')
            ?? $request->input('token');

        abort_unless($order->trackingTokenMatches(is_string($token) ? $token : null), 404, 'Pesanan tidak ditemukan.');
    }
}
