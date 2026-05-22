<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\TransaccionLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /**
     * POST /api/webhooks/stripe
     */
    public function stripe(Request $request): JsonResponse
    {
        $payload = $request->all();
        $evento  = $payload['type'] ?? 'desconocido';

        // Buscar el pago por referencia externa si viene en el payload
        $referenciaExterna = $payload['data']['object']['id'] ?? null;
        $pago = $referenciaExterna
            ? Pago::where('referencia_externa', $referenciaExterna)->first()
            : null;

        if ($pago) {
            // Mapear el evento de Stripe a nuestro estado interno
            $nuevoEstado = match ($evento) {
                'charge.succeeded'         => 'completado',
                'charge.failed'            => 'fallido',
                'charge.refunded'          => 'reembolsado',
                default                    => null,
            };

            if ($nuevoEstado) {
                $pago->update(['estado' => $nuevoEstado]);
            }

            TransaccionLog::create([
                'pago_id'   => $pago->id,
                'accion'    => 'webhook_received',
                'resultado' => 'exito',
                'mensaje'   => "Evento de Stripe recibido: {$evento}",
                'payload'   => $payload,
                'respuesta' => [],
            ]);
        }

        // Stripe exige que respondas 200 rápidamente, sino reintenta
        return response()->json(['recibido' => true], 200);
    }

    /**
     * POST /api/webhooks/mercadopago
     */
    public function mercadopago(Request $request): JsonResponse
    {
        $payload = $request->all();
        $topic   = $request->query('topic') ?? $payload['type'] ?? 'desconocido';
        $recursoId = $payload['data']['id'] ?? null;

        $pago = $recursoId
            ? Pago::where('referencia_externa', (string) $recursoId)->first()
            : null;

        if ($pago) {
            $nuevoEstado = match ($topic) {
                'payment'  => 'completado',
                default    => null,
            };

            if ($nuevoEstado) {
                $pago->update(['estado' => $nuevoEstado]);
            }

            TransaccionLog::create([
                'pago_id'   => $pago->id,
                'accion'    => 'webhook_received',
                'resultado' => 'exito',
                'mensaje'   => "Evento de MercadoPago recibido: {$topic}",
                'payload'   => $payload,
                'respuesta' => [],
            ]);
        }

        return response()->json(['recibido' => true], 200);
    }
}
