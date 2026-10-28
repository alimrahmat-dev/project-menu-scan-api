<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentService
{
    public const METHOD_LABELS = [
        'qris' => 'QRIS',
        'ewallet' => 'E-Wallet',
        'va' => 'Virtual Account',
        'cash' => 'Bayar di Kasir',
    ];

    public function mode(): string
    {
        return config('services.midtrans.mode', 'mock') === 'midtrans' ? 'midtrans' : 'mock';
    }

    public function isMock(): bool
    {
        return $this->mode() === 'mock';
    }

    /**
     * Siapkan pembayaran untuk sebuah order.
     *
     * @return array{mode: string, redirect_url: ?string, token: ?string, reference: ?string}
     */
    public function createPayment(Order $order): array
    {
        if ($order->isPaid()) {
            return [
                'mode' => $this->mode(),
                'redirect_url' => null,
                'token' => null,
                'reference' => $order->payment_reference,
            ];
        }

        if ($this->isMock()) {
            $reference = 'MOCK-'.$order->order_number.'-'.Str::upper(Str::random(4));

            Payment::create([
                'order_id' => $order->id,
                'provider' => 'mock',
                'reference' => $reference,
                'status' => Order::PAYMENT_PENDING,
                'amount' => $order->total,
                'payload' => ['mode' => 'demo'],
            ]);

            $order->forceFill([
                'payment_status' => Order::PAYMENT_PENDING,
                'payment_reference' => $reference,
                'payment_method' => $order->payment_method ?: 'qris',
                'payment_url' => null,
            ])->save();

            return [
                'mode' => 'mock',
                'redirect_url' => null,
                'token' => null,
                'reference' => $reference,
            ];
        }

        return $this->createSnapTransaction($order);
    }

    /**
     * Buat transaksi di Midtrans Snap.
     */
    private function createSnapTransaction(Order $order): array
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum diatur pada file .env.');
        }

        $attempt = $order->payments()->count();
        $midtransOrderId = $order->order_number.($attempt > 0 ? '-'.Str::upper(Str::random(4)) : '');

        $itemDetails = $order->items->map(fn ($item) => [
            'id' => (string) ($item->menu_id ?? $item->id),
            'price' => $item->unit_price,
            'quantity' => $item->quantity,
            'name' => Str::limit($item->menu_name, 45),
        ])->values()->all();

        if ($order->tax_amount > 0) {
            $itemDetails[] = [
                'id' => 'TAX',
                'price' => $order->tax_amount,
                'quantity' => 1,
                'name' => 'Pajak ('.config('menuscan.tax_percent').'%)',
            ];
        }

        if ($order->service_amount > 0) {
            $itemDetails[] = [
                'id' => 'SERVICE',
                'price' => $order->service_amount,
                'quantity' => 1,
                'name' => 'Biaya Layanan ('.config('menuscan.service_percent').'%)',
            ];
        }

        $baseUrl = config('services.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->timeout(20)
            ->post($baseUrl, [
                'transaction_details' => [
                    'order_id' => $midtransOrderId,
                    'gross_amount' => $order->total,
                ],
                'item_details' => $itemDetails,
                'customer_details' => [
                    'first_name' => $order->customer_name,
                    'phone' => $order->customer_phone ?? '',
                ],
                'callbacks' => [
                    'finish' => config('menuscan.frontend_url').'/order/'.$order->order_number.'?token='.$order->tracking_token,
                    'unfinish' => config('menuscan.frontend_url').'/order/'.$order->order_number.'?token='.$order->tracking_token,
                ],
                'enabled_payments' => $this->enabledPayments($order->payment_method),
            ]);

        if ($response->failed()) {
            Log::error('Midtrans Snap error', [
                'order' => $order->order_number,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new RuntimeException(
                $response->json('error_messages.0') ?? 'Gagal membuat transaksi pembayaran Midtrans.'
            );
        }

        $payload = $response->json();
        $token = $payload['token'] ?? null;
        $redirectUrl = $payload['redirect_url'] ?? null;

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'reference' => $midtransOrderId,
            'status' => Order::PAYMENT_PENDING,
            'amount' => $order->total,
            'payload' => $payload,
        ]);

        $order->forceFill([
            'payment_status' => Order::PAYMENT_PENDING,
            'payment_reference' => $midtransOrderId,
            'payment_url' => $redirectUrl,
        ])->save();

        return [
            'mode' => 'midtrans',
            'redirect_url' => $redirectUrl,
            'token' => $token,
            'reference' => $midtransOrderId,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function enabledPayments(?string $method): array
    {
        return match ($method) {
            'qris' => ['other_qris'],
            'ewallet' => ['gopay', 'shopeepay'],
            'va' => ['bca_va', 'bni_va', 'bri_va', 'permata_va', 'echannel'],
            default => ['other_qris', 'gopay', 'shopeepay', 'bca_va', 'bni_va', 'bri_va'],
        };
    }

    /**
     * Selesaikan pembayaran pada mode demo.
     */
    public function completeMockPayment(Order $order, bool $success): Order
    {
        if (! $this->isMock()) {
            throw new RuntimeException('Pembayaran demo tidak tersedia pada mode Midtrans.');
        }

        $payment = $order->payments()->latest()->first();

        if ($success) {
            $order->markAsPaid($order->payment_method, $payment?->reference);

            $payment?->update(['status' => Order::PAYMENT_PAID]);

            $order->payments()->create([
                'provider' => 'mock',
                'reference' => 'MOCK-SETTLE-'.Str::upper(Str::random(6)),
                'status' => Order::PAYMENT_PAID,
                'amount' => $order->total,
                'payload' => ['simulated' => true, 'result' => 'success'],
            ]);
        } else {
            $order->forceFill(['payment_status' => Order::PAYMENT_FAILED])->save();
            $payment?->update(['status' => Order::PAYMENT_FAILED]);
        }

        return $order->fresh(['items', 'diningTable']);
    }

    /**
     * Tangani notifikasi HTTP dari Midtrans.
     */
    public function handleNotification(array $payload, ?string $signatureKey = null): Order
    {
        $serverKey = (string) config('services.midtrans.server_key');

        $expectedSignature = hash('sha512',
            ($payload['order_id'] ?? '').
            ($payload['status_code'] ?? '').
            ($payload['gross_amount'] ?? '').
            $serverKey
        );

        if (! $signatureKey || ! hash_equals($expectedSignature, $signatureKey)) {
            throw new RuntimeException('Signature notifikasi Midtrans tidak valid.');
        }

        $reference = $payload['order_id'] ?? null;

        $payment = Payment::query()
            ->where('reference', $reference)
            ->latest()
            ->first();

        $order = $payment?->order
            ?? Order::where('order_number', $reference)->first();

        if (! $order) {
            throw new RuntimeException('Pesanan untuk notifikasi ini tidak ditemukan.');
        }

        $grossAmount = (float) ($payload['gross_amount'] ?? 0);

        if ($grossAmount > 0 && (int) round($grossAmount) !== $order->total) {
            throw new RuntimeException('Nominal notifikasi tidak sesuai dengan total pesanan.');
        }

        $transactionStatus = $payload['transaction_status'] ?? 'pending';
        $fraudStatus = $payload['fraud_status'] ?? null;

        $newStatus = match (true) {
            in_array($transactionStatus, ['capture', 'settlement'], true)
                && $fraudStatus !== 'challenge' => Order::PAYMENT_PAID,
            $transactionStatus === 'pending' => Order::PAYMENT_PENDING,
            $transactionStatus === 'expire' => Order::PAYMENT_EXPIRED,
            $transactionStatus === 'refund', $transactionStatus === 'partial_refund' => Order::PAYMENT_REFUNDED,
            default => Order::PAYMENT_FAILED,
        };

        $payment?->update([
            'status' => $newStatus,
            'payload' => array_merge($payment->payload ?? [], $payload),
        ]);

        if ($newStatus === Order::PAYMENT_PAID && ! $order->isPaid()) {
            $order->markAsPaid(
                $this->methodFromMidtrans($payload),
                $reference,
            );
        } elseif ($newStatus !== Order::PAYMENT_PAID) {
            $order->forceFill(['payment_status' => $newStatus])->save();
        }

        return $order->fresh(['items', 'diningTable']);
    }

    private function methodFromMidtrans(array $payload): string
    {
        $type = strtolower((string) ($payload['payment_type'] ?? ''));

        return match (true) {
            str_contains($type, 'qris') => 'qris',
            str_contains($type, 'gopay'), str_contains($type, 'shopeepay'), str_contains($type, 'wallet') => 'ewallet',
            str_contains($type, 'va'), str_contains($type, 'bank'), str_contains($type, 'echannel') => 'va',
            default => 'qris',
        };
    }
}
