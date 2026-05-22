<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas con Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/pagos',                  [PagoController::class, 'store']);
    Route::get('/pagos/{uuid}',            [PagoController::class, 'show']);
    Route::post('/pagos/{uuid}/reembolso', [PagoController::class, 'reembolso']);
});

// Webhooks — SIN autenticación
Route::post('/webhooks/stripe',       [WebhookController::class, 'stripe']);
Route::post('/webhooks/mercadopago',  [WebhookController::class, 'mercadopago']);
