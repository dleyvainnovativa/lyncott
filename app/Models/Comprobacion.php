<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comprobacion extends Model
{
    protected $table = 'comprobaciones';

    protected $guarded = ['id'];

    protected $casts = [
        'fecha_salida'       => 'date',
        'fecha_regreso'      => 'date',
        'dias'               => 'integer',
        'noches'             => 'integer',
        'monto_anticipo'     => 'decimal:2',
        'total_con_factura'  => 'decimal:2',
        'total_sin_factura'  => 'decimal:2',
        'iva_total'          => 'decimal:2',
        'monto_comprobacion' => 'decimal:2',
    ];

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }

    public function payloads(): HasMany
    {
        return $this->hasMany(SmartkerPayload::class);
    }

    /** Human-friendly folio for display (e.g. LX-00042). */
    public function folio(): string
    {
        return 'LX-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
