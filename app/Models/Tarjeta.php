<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tarjeta extends Model
{
    protected $table = 'tarjetas';

    protected $fillable = [
        'empleado_id', 'nombre', 'numero', 'tipo', 'bloque', 'subuso',
        'cardholder_nombre', 'cardholder_emp', 'cardholder_rfc', 'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    /** Fields safe to return to the front end. */
    public const PUBLIC_FIELDS = [
        'id', 'nombre', 'numero', 'tipo', 'bloque', 'subuso',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }
}
