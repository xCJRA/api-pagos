<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pago extends Model
{
    protected $fillable = [
        'uuid',
        'monto',
        'moneda',
        'gateway',
        'estado',
        'referencia_externa',
        'descripcion',
        'metadata',
    ];

    // Le dice a Laravel que estos campos son JSON — los convierte a array automáticamente
    protected $casts = [
        'metadata' => 'array',
        'monto'    => 'decimal:2',
    ];

    // Relación: un pago tiene muchos logs
    public function logs(): HasMany
    {
        return $this->hasMany(TransaccionLog::class);
    }
}
