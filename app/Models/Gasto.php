<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gasto extends Model
{
    protected $table = 'gastos';

    protected $guarded = ['id'];

    protected $casts = [
        'fecha'   => 'date',
        'importe' => 'decimal:2',
        'iva'     => 'decimal:2',
        'total'   => 'decimal:2',
    ];

    public function comprobacion(): BelongsTo
    {
        return $this->belongsTo(Comprobacion::class);
    }

    public function esConFactura(): bool
    {
        return $this->tipo === 'cf';
    }
}
