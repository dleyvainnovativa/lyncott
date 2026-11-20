<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empleado extends Model
{
    protected $table = 'empleados';

    protected $fillable = [
        'numero_empleado', 'rfc', 'nombre_completo', 'correo', 'puesto',
        'gerencia', 'departamento', 'direccion', 'dias_disponibles',
        'centro_costos', 'clabe', 'banco', 'sucursal_cedis',
        'autorizador_nombre', 'autorizador_correo', 'autorizador_numero', 'autorizador_rfc',
    ];

    protected $casts = [
        'dias_disponibles' => 'integer',
    ];

    /** Fields safe to return to the front end on lookup. */
    public const PUBLIC_FIELDS = [
        'id', 'numero_empleado', 'rfc', 'nombre_completo', 'correo', 'puesto',
        'gerencia', 'departamento', 'direccion', 'dias_disponibles',
        'centro_costos', 'clabe', 'banco', 'sucursal_cedis',
        'autorizador_nombre', 'autorizador_correo', 'autorizador_numero', 'autorizador_rfc',
    ];

    public function tarjetas(): HasMany
    {
        return $this->hasMany(Tarjeta::class);
    }

    /** Active cards only (the ones selectable in the flow). */
    public function tarjetasActivas(): HasMany
    {
        return $this->tarjetas()->where('activa', true);
    }
}
