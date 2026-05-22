<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\PaymentMethod\PaymentMethodClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Exceptions\MPApiException;
use Exception;

class MercadoPagoGateway implements PaymentGatewayInterface
{
    private PaymentClient $client;

    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
        $this->client = new PaymentClient();
    }

    public function charge(array $datos): array
    {
        try {
            $pago = $this->client->create([
                'transaction_amount' => (float) $datos['monto'],
                'description'        => $datos['descripcion'] ?? 'Pago via API',
                'payment_method_id'  => $datos['payment_method_id'] ?? 'visa', // ej: visa, master
                'token'              => $datos['token_tarjeta'],
                'installments'       => $datos['cuotas'] ?? 1,
                'payer'              => [
                    'email' => $datos['email_pagador'],
                ],
            ]);

            $exitoso = in_array($pago->status, ['approved', 'in_process']);

            return [
                'exito'              => $exitoso,
                'referencia_externa' => (string) $pago->id,
                'mensaje'            => $exitoso
                    ? 'Pago procesado correctamente'
                    : 'Pago rechazado: ' . $pago->status_detail,
                'respuesta_raw'      => (array) $pago,
            ];
        } catch (MPApiException $e) {
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
            // MercadoPago usa un endpoint diferente para reembolsos parciales vs totales
            $params = $monto !== null ? ['amount' => $monto] : [];

            $reembolso = $this->client->refund((int) $referenciaExterna, $params);

            return [
                'exito'         => true,
                'mensaje'       => 'Reembolso procesado correctamente',
                'respuesta_raw' => (array) $reembolso,
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
