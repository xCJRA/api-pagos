<?php

namespace App\Http\Controllers;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'API de pasarela de pagos',
    version: '1.0.0',
    description: 'API REST que abstrae Stripe y MercadoPago detrás de una interfaz unificada. Construida con Laravel 11, autenticación Sanctum y patrón Strategy.',
    contact: new OA\Contact(
        name: 'César José Reyes Alonso',
        email: 'cesarjreyesa1@gmail.com'
    )
)]
#[OA\Server(
    url: '/api',
    description: 'Servidor local'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Ingresa el token obtenido del endpoint /api/login'
)]
#[OA\Tag(name: 'Pagos', description: 'Operaciones de cobro, consulta y reembolso')]
#[OA\Tag(name:"Webhooks", description:"Endpoints para notificaciones de las pasarelas")]
abstract class Controller
{
}
