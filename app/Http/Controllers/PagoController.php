<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePagoRequest;
use App\Http\Requests\StoreReembolsoRequest;
use App\Models\Pago;
use App\Models\TransaccionLog;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class PagoController extends Controller
{
    public function __construct(private PaymentService $paymentService)
    {
    }

    #[OA\Post(
        path: '/pagos',
        summary: 'Procesar un nuevo cobro',
        description: 'Realiza un cobro a través de Stripe o MercadoPago según el gateway indicado.',
        security: [['sanctum' => []]],
        tags: ['Pagos'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['monto', 'moneda', 'gateway', 'token_tarjeta'],
                properties: [
                    new OA\Property(property: 'monto', type: 'number', example: 500.00),
                    new OA\Property(property: 'moneda', type: 'string', example: 'MXN', description: 'Código ISO 4217'),
                    new OA\Property(property: 'gateway', type: 'string', enum: ['stripe', 'mercadopago']),
                    new OA\Property(property: 'descripcion', type: 'string', example: 'Suscripción mensual'),
                    new OA\Property(property: 'token_tarjeta', type: 'string', example: 'tok_visa', description: 'Token generado por Stripe.js o MercadoPago.js'),
                    new OA\Property(property: 'email_pagador', type: 'string', example: 'cliente@email.com', description: 'Requerido para MercadoPago'),
                    new OA\Property(property: 'payment_method_id', type: 'string', example: 'visa', description: 'Requerido para MercadoPago'),
                    new OA\Property(property: 'cuotas', type: 'integer', example: 1, description: 'Solo MercadoPago'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Pago procesado correctamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'exito', type: 'boolean', example: true),
                        new OA\Property(property: 'mensaje', type: 'string', example: 'Pago realizado correctamente'),
                        new OA\Property(
                            property: 'pago',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'uuid', type: 'string', example: '550e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'monto', type: 'number', example: 500.00),
                                new OA\Property(property: 'moneda', type: 'string', example: 'MXN'),
                                new OA\Property(property: 'gateway', type: 'string', example: 'stripe'),
                                new OA\Property(property: 'estado', type: 'string', example: 'completado'),
                                new OA\Property(property: 'referencia_externa', type: 'string', example: 'ch_3Qx...'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación o pago rechazado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'exito', type: 'boolean', example: false),
                        new OA\Property(property: 'mensaje', type: 'string', example: 'Tu tarjeta fue rechazada.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function store(StorePagoRequest $request): JsonResponse
    {
        $pago = Pago::create([
            'uuid'        => Str::uuid(),
            'monto'       => $request->monto,
            'moneda'      => strtoupper($request->moneda),
            'gateway'     => $request->gateway,
            'estado'      => 'pendiente',
            'descripcion' => $request->descripcion,
        ]);

        $gateway = $this->paymentService->resolver($request->gateway);
        $resultado = $gateway->charge($request->validated());

        TransaccionLog::create([
            'pago_id'   => $pago->id,
            'accion'    => 'charge',
            'resultado' => $resultado['exito'] ? 'exito' : 'error',
            'mensaje'   => $resultado['mensaje'],
            'payload'   => $request->validated(),
            'respuesta' => $resultado['respuesta_raw'],
        ]);

        $pago->update([
            'estado'             => $resultado['exito'] ? 'completado' : 'fallido',
            'referencia_externa' => $resultado['referencia_externa'],
            'metadata'           => $resultado['respuesta_raw'],
        ]);

        return response()->json([
            'exito'   => $resultado['exito'],
            'mensaje' => $resultado['mensaje'],
            //'detalle' => $resultado['respuesta_raw'], // solo para depuración
            'pago'    => [
                'uuid'               => $pago->uuid,
                'monto'              => $pago->monto,
                'moneda'             => $pago->moneda,
                'gateway'            => $pago->gateway,
                'estado'             => $pago->fresh()->estado,
                'referencia_externa' => $pago->fresh()->referencia_externa,
            ],
        ], $resultado['exito'] ? 201 : 422);
    }

    #[OA\Get(
        path: '/pagos/{uuid}',
        summary: 'Consultar estado de un pago',
        description: 'Devuelve el detalle de un pago y su historial de eventos.',
        security: [['sanctum' => []]],
        tags: ['Pagos'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                in: 'path',
                required: true,
                description: 'UUID del pago',
                schema: new OA\Schema(type: 'string', example: '550e8400-e29b-41d4-a716-446655440000')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle del pago',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'pago',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'uuid', type: 'string'),
                                new OA\Property(property: 'monto', type: 'number', example: 500.00),
                                new OA\Property(property: 'moneda', type: 'string', example: 'MXN'),
                                new OA\Property(property: 'gateway', type: 'string', example: 'stripe'),
                                new OA\Property(property: 'estado', type: 'string', example: 'completado'),
                                new OA\Property(property: 'descripcion', type: 'string'),
                                new OA\Property(property: 'referencia_externa', type: 'string'),
                                new OA\Property(property: 'creado_en', type: 'string', example: '2026-05-22 10:00:00'),
                            ]
                        ),
                        new OA\Property(
                            property: 'historial',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'accion', type: 'string', example: 'charge'),
                                    new OA\Property(property: 'resultado', type: 'string', example: 'exito'),
                                    new OA\Property(property: 'mensaje', type: 'string'),
                                    new OA\Property(property: 'fecha', type: 'string'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Pago no encontrado'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function show(string $uuid): JsonResponse
    {
        $pago = Pago::where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'pago' => [
                'uuid'               => $pago->uuid,
                'monto'              => $pago->monto,
                'moneda'             => $pago->moneda,
                'gateway'            => $pago->gateway,
                'estado'             => $pago->estado,
                'descripcion'        => $pago->descripcion,
                'referencia_externa' => $pago->referencia_externa,
                'creado_en'          => $pago->created_at->toDateTimeString(),
            ],
            'historial' => $pago->logs->map(fn($log) => [
                'accion'    => $log->accion,
                'resultado' => $log->resultado,
                'mensaje'   => $log->mensaje,
                'fecha'     => $log->created_at->toDateTimeString(),
            ]),
        ]);
    }

    #[OA\Post(
        path: '/pagos/{uuid}/reembolso',
        summary: 'Reembolsar un pago',
        description: 'Procesa un reembolso parcial o total. Si no se envía monto, se reembolsa el total.',
        security: [['sanctum' => []]],
        tags: ['Pagos'],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                in: 'path',
                required: true,
                description: 'UUID del pago a reembolsar',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'monto', type: 'number', example: 100.00, description: 'Monto a reembolsar. Omitir para reembolso total.'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Reembolso procesado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'exito', type: 'boolean', example: true),
                        new OA\Property(property: 'mensaje', type: 'string', example: 'Reembolso procesado correctamente'),
                        new OA\Property(property: 'estado', type: 'string', example: 'reembolsado'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'El pago no está en estado completado'),
            new OA\Response(response: 404, description: 'Pago no encontrado'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function reembolso(StoreReembolsoRequest $request, string $uuid): JsonResponse
    {
        $pago = Pago::where('uuid', $uuid)->firstOrFail();

        if ($pago->estado !== 'completado') {
            return response()->json([
                'exito'   => false,
                'mensaje' => "No se puede reembolsar un pago en estado '{$pago->estado}'.",
            ], 422);
        }

        $gateway = $this->paymentService->resolver($pago->gateway);
        $resultado = $gateway->refund($pago->referencia_externa, $request->monto);

        TransaccionLog::create([
            'pago_id'   => $pago->id,
            'accion'    => 'refund',
            'resultado' => $resultado['exito'] ? 'exito' : 'error',
            'mensaje'   => $resultado['mensaje'],
            'payload'   => ['monto' => $request->monto],
            'respuesta' => $resultado['respuesta_raw'],
        ]);

        if ($resultado['exito']) {
            $pago->update(['estado' => 'reembolsado']);
        }

        return response()->json([
            'exito'   => $resultado['exito'],
            'mensaje' => $resultado['mensaje'],
            'estado'  => $pago->fresh()->estado,
        ], $resultado['exito'] ? 200 : 422);
    }
}
