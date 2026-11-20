<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarjetas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();

            $table->string('nombre');                     // card label, e.g. "AMEX FRANCISCO REYES PEREZ"
            $table->string('numero')->nullable();         // card number (null when INACTIVA)
            $table->string('tipo', 10);                   // AMEX | TOKA
            $table->string('bloque', 20);                 // amex | reembolso | viaje  (maps to Tipo de Gasto)
            $table->string('subuso')->nullable();         // DIRECCION / VUELOS Y HOSPEDAJES / TOKA GASTOS

            // Original cardholder (the card may belong to a director, loaded by an admin).
            $table->string('cardholder_nombre')->nullable();
            $table->string('cardholder_emp', 30)->nullable();
            $table->string('cardholder_rfc', 13)->nullable();

            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index(['empleado_id', 'activa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarjetas');
    }
};
