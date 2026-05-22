<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Refund;
use Exception;

class StripeGateway implements PaymentGatewayInterface
{
    public function __construct()
    {
        // Configura la API key de Stripe al instanciar la clase
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function charge(array $datos): array
    {
        try {
            $cobro = Charge::create([
                'amount'      => (int) ($datos['monto'] * 100), // Stripe trabaja en centavos
                'currency'    => strtolower($datos['moneda']),
                'description' => $datos['descripcion'] ?? 'Pago via API',
                'source'      => $datos['token_tarjeta'],       // Token generado por Stripe.js
            ]);

            return [
                'exito'              => true,
                'referencia_externa' => $cobro->id,             // ej: ch_3Qx...
                'mensaje'            => 'Pago realizado correctamente',
                'respuesta_raw'      => $cobro->toArray(),
            ];
        } catch (Exception $e) {
            return [
                'exito'              => false,
                'referencia_externa' => null,
                'mensaje'            => $e->getMessage(),
                'respuesta_raw'      => [],
            ];
        }
    }

    public function refund(string $referenciaExterna, ?float $monto = null): array
    {
        try {
            $params = ['charge' => $referenciaExterna];

            // Si se especifica monto, es reembolso parcial
            if ($monto !== null) {
                $params['amount'] = (int) ($monto * 100);
            }

            $reembolso = Refund::create($params);

            return [
                'exito'         => true,
                'mensaje'       => 'Reembolso procesado correctamente',
                'respuesta_raw' => $reembolso->toArray(),
            ];
        } catch (Exception $e) {
            return [
                'exito'         => false,
                'mensaje'       => $e->getMessage(),
                'respuesta_raw' => [],
            ];
        }
    }
}
