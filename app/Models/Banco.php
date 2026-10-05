<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    protected $table = 'bancos';

    protected $fillable = ['clave', 'nombre', 'activo'];

    protected $casts = ['activo' => 'boolean'];
}
