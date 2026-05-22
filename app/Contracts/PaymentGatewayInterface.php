<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Realiza un cobro a través de la pasarela.
     *
     * @param  array  $datos  ['monto', 'moneda', 'descripcion', 'token_tarjeta']
     * @return array          ['exito', 'referencia_externa', 'mensaje', 'respuesta_raw']
     */
    public function charge(array $datos): array;

    /**
     * Realiza un reembolso parcial o total.
     *
     * @param  string     $referenciaExterna  El ID del cobro en la pasarela
     * @param  float|null $monto              Null = reembolso total
     * @return array                          ['exito', 'mensaje', 'respuesta_raw']
     */
    public function refund(string $referenciaExterna, ?float $monto = null): array;
}
