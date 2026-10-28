<?php

namespace App\Http\Resources;

use App\Models\DiningTable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DiningTable */
class DiningTableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'qr_token' => $this->qr_token,
            'is_active' => $this->is_active,
            'menu_url' => $this->menu_url,
            'orders_count' => $this->whenCounted('orders'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
