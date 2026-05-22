<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use InvalidArgumentException;

class PaymentService
{
    /**
     * Resuelve y devuelve la implementación correcta de la pasarela.
     *
     * @throws InvalidArgumentException Si el gateway no está soportado
     */
    public function resolver(string $gateway): PaymentGatewayInterface
    {
        return match ($gateway) {
            'stripe'       => new StripeGateway(),
            'mercadopago'  => new MercadoPagoGateway(),
            default        => throw new InvalidArgumentException(
                "Gateway '{$gateway}' no está soportado. Usa: stripe, mercadopago"
            ),
        };
    }
}
