<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();          // Folio único que le damos al cliente
            $table->decimal('monto', 10, 2);           // Ej: 1500.00
            $table->string('moneda', 3)->default('MXN'); // ISO 4217: MXN, USD
            $table->enum('gateway', ['stripe', 'mercadopago']);
            $table->enum('estado', ['pendiente', 'completado', 'fallido', 'reembolsado'])
                ->default('pendiente');
            $table->string('referencia_externa')->nullable(); // El ID que nos devuelve Stripe/MP
            $table->string('descripcion')->nullable();
            $table->json('metadata')->nullable();       // Datos extra de la pasarela
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
