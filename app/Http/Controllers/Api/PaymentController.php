<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymentController extends Controller
{
    public function midtransNotification(Request $request, PaymentService $paymentService): JsonResponse
    {
        $payload = $request->all();
        $signature = $request->header('X-Callback-Signature') ?? ($payload['signature_key'] ?? null);

        try {
            $order = $paymentService->handleNotification($payload, $signature);
        } catch (RuntimeException $exception) {
            Log::warning('Midtrans notification rejected', [
                'message' => $exception->getMessage(),
                'payload' => $payload,
            ]);

            return response()->json(['message' => $exception->getMessage()], 400);
        }

        return response()->json([
            'message' => 'Notification processed.',
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status,
        ]);
    }
}
