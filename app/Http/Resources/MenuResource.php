<?php

namespace App\Http\Resources;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Menu */
class MenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'image_url' => $this->image_url,
            'is_available' => $this->is_available,
            'is_featured' => $this->is_featured,
            'calories' => $this->calories,
            'prep_time' => $this->prep_time,
            'badge' => $this->badge,
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
