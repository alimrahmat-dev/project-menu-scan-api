<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'payment_url' => $this->payment_url,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'notes' => $this->notes,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'service_amount' => $this->service_amount,
            'total' => $this->total,
            'paid_at' => $this->paid_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'can_pay' => $this->canBePaid() && $this->payment_method !== 'cash',
            'payment_mode' => config('services.midtrans.mode', 'mock') === 'midtrans' ? 'midtrans' : 'mock',
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'dining_table' => $this->whenLoaded('diningTable', fn () => new DiningTableResource($this->diningTable)),
        ];
    }
}
