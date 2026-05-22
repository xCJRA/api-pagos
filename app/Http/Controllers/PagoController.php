<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePagoRequest;
use App\Http\Requests\StoreReembolsoRequest;
use App\Models\Pago;
use App\Models\TransaccionLog;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PagoController extends Controller
{
    public function __construct(private PaymentService $paymentService)
    {
        // Inyección de dependencias — Laravel instancia PaymentService automáticamente
    }

    /**
     * POST /api/pagos
     * Procesa un nuevo cobro a través de la pasarela indicada.
     */
    public function store(StorePagoRequest $request): JsonResponse
    {
        // 1. Crear el registro del pago en estado 'pendiente'
        $pago = Pago::create([
            'uuid'        => Str::uuid(),
            'monto'       => $request->monto,
            'moneda'      => strtoupper($request->moneda),
            'gateway'     => $request->gateway,
            'estado'      => 'pendiente',
            'descripcion' => $request->descripcion,
        ]);

        // 2. Resolver la pasarela correcta y ejecutar el cobro
        $gateway = $this->paymentService->resolver($request->gateway);
        $resultado = $gateway->charge($request->validated());

        // 3. Registrar el resultado en el log de auditoría
        TransaccionLog::create([
            'pago_id'   => $pago->id,
            'accion'    => 'charge',
            'resultado' => $resultado['exito'] ? 'exito' : 'error',
            'mensaje'   => $resultado['mensaje'],
            'payload'   => $request->validated(),
            'respuesta' => $resultado['respuesta_raw'],
        ]);

        // 4. Actualizar el estado del pago según el resultado
        $pago->update([
            'estado'              => $resultado['exito'] ? 'completado' : 'fallido',
            'referencia_externa'  => $resultado['referencia_externa'],
            'metadata'            => $resultado['respuesta_raw'],
        ]);

        // 5. Responder al cliente
        $statusCode = $resultado['exito'] ? 201 : 422;

        return response()->json([
            'exito'   => $resultado['exito'],
            'mensaje' => $resultado['mensaje'],
            'pago'    => [
                'uuid'               => $pago->uuid,
                'monto'              => $pago->monto,
                'moneda'             => $pago->moneda,
                'gateway'            => $pago->gateway,
                'estado'             => $pago->fresh()->estado,
                'referencia_externa' => $pago->fresh()->referencia_externa,
            ],
        ], $statusCode);
    }

    /**
     * GET /api/pagos/{uuid}
     * Consulta el estado de un pago por su UUID.
     */
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
                'accion'     => $log->accion,
                'resultado'  => $log->resultado,
                'mensaje'    => $log->mensaje,
                'fecha'      => $log->created_at->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * POST /api/pagos/{uuid}/reembolso
     * Procesa un reembolso parcial o total de un pago completado.
     */
    public function reembolso(StoreReembolsoRequest $request, string $uuid): JsonResponse
    {
        $pago = Pago::where('uuid', $uuid)->firstOrFail();

        // Validar que el pago esté en estado completado
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
