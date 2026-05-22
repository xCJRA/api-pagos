<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacciones_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos')->onDelete('cascade');
            $table->string('accion');          // 'charge', 'refund', 'webhook_received'
            $table->enum('resultado', ['exito', 'error']);
            $table->text('mensaje')->nullable(); // Mensaje de éxito o error de la pasarela
            $table->json('payload')->nullable(); // Lo que mandamos a la pasarela
            $table->json('respuesta')->nullable(); // Lo que nos devolvió
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacciones_log');
    }
};
