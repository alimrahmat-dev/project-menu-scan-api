<?php

namespace App\Http\Requests;

use App\Models\Menu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $menu = $this->route('menu');

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('menus', 'name')->ignore($menu instanceof Menu ? $menu->id : $menu),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'is_available' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'calories' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'prep_time' => ['nullable', 'integer', 'min:1', 'max:240'],
            'badge' => ['nullable', 'string', 'max:40'],
        ];
    }
}
