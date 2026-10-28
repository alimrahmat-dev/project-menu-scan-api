<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
        return [
            'table_token' => ['required', 'uuid', 'exists:dining_tables,qr_token'],
            'customer_name' => ['required', 'string', 'max:80'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'in:qris,ewallet,va,cash'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.menu_id' => ['required', 'integer', 'exists:menus,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'items.*.notes' => ['nullable', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'table_token.exists' => 'Meja tidak ditemukan. Silakan pindai ulang QR code di meja Anda.',
            'items.required' => 'Pilih minimal satu menu sebelum memesan.',
            'payment_method.in' => 'Metode pembayaran tidak dikenali.',
        ];
    }
}
