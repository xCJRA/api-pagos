<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaccionLog extends Model
{
    protected $fillable = [
        'pago_id',
        'accion',
        'resultado',
        'mensaje',
        'payload',
        'respuesta',
    ];

    protected $casts = [
        'payload'   => 'array',
        'respuesta' => 'array',
    ];

    // Relación inversa: un log pertenece a un pago
    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class);
    }
}
