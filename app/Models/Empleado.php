<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $table = 'empleados';

    protected $fillable = [
        'numero_empleado', 'rfc', 'nombre_completo', 'correo', 'puesto',
        'gerencia', 'departamento', 'direccion', 'dias_disponibles',
        'centro_costos', 'clabe', 'banco', 'sucursal_cedis',
    ];

    protected $casts = [
        'dias_disponibles' => 'integer',
    ];

    /** Fields safe to return to the front end on lookup. */
    public const PUBLIC_FIELDS = [
        'id', 'numero_empleado', 'rfc', 'nombre_completo', 'correo', 'puesto',
        'gerencia', 'departamento', 'direccion', 'dias_disponibles',
        'centro_costos', 'clabe', 'banco', 'sucursal_cedis',
    ];
}
