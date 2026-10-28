<?php

namespace App\Http\Requests;

use App\Models\DiningTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TableRequest extends FormRequest
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
        $table = $this->route('table');

        return [
            'code' => [
                'required', 'alpha_dash', 'max:20',
                Rule::unique('dining_tables', 'code')->ignore($table instanceof DiningTable ? $table->id : $table),
            ],
            'name' => ['required', 'string', 'max:80'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
