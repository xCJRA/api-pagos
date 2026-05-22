<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReembolsoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Monto es opcional — si no viene, se reembolsa el total
            'monto' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto.min' => 'El monto mínimo a reembolsar es 0.01.',
        ];
    }
}
