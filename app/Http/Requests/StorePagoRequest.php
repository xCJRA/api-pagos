<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto'            => ['required', 'numeric', 'min:1'],
            'moneda'           => ['required', 'string', 'size:3'],        // MXN, USD
            'gateway'          => ['required', 'string', 'in:stripe,mercadopago'],
            'descripcion'      => ['nullable', 'string', 'max:255'],
            'token_tarjeta'    => ['required', 'string'],                  // tok_visa en sandbox
            'email_pagador'    => ['required_if:gateway,mercadopago', 'email'],
            'payment_method_id'=> ['required_if:gateway,mercadopago', 'string'],
            'cuotas'           => ['nullable', 'integer', 'min:1', 'max:24'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto.required'         => 'El monto es obligatorio.',
            'monto.min'              => 'El monto mínimo es 1.',
            'moneda.size'            => 'La moneda debe ser un código de 3 letras (ej: MXN, USD).',
            'gateway.in'             => 'El gateway debe ser stripe o mercadopago.',
            'token_tarjeta.required' => 'El token de tarjeta es obligatorio.',
            'email_pagador.required_if' => 'El email del pagador es obligatorio para MercadoPago.',
            'payment_method_id.required_if' => 'El payment_method_id es obligatorio para MercadoPago.',
        ];
    }
}
