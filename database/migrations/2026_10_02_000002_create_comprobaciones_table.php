<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobaciones', function (Blueprint $table) {
            $table->id();

            // Solicitante snapshot (captured at submit time).
            $table->string('numero_empleado', 30);
            $table->string('nombre_empleado');
            $table->string('rfc', 13)->nullable();
            $table->string('departamento')->nullable();
            $table->string('gerencia')->nullable();
            $table->string('direccion')->nullable();
            $table->string('centro_costos')->nullable();
            $table->string('clabe', 18)->nullable();
            $table->string('banco')->nullable();
            $table->string('sucursal_cedis')->nullable();

            // Viaje.
            $table->date('fecha_salida')->nullable();
            $table->date('fecha_regreso')->nullable();
            $table->unsignedSmallInteger('dias')->default(0);
            $table->unsignedSmallInteger('noches')->default(0);
            $table->string('origen')->nullable();
            $table->string('destino')->nullable();
            $table->string('folio_anticipo')->nullable();
            $table->decimal('monto_anticipo', 12, 2)->default(0);
            $table->string('medio_transporte')->nullable();
            $table->text('observaciones')->nullable();

            // Clasificación + totales.
            $table->string('tipo_gasto')->default('Viaje');
            $table->text('justificacion')->nullable();
            $table->decimal('total_con_factura', 12, 2)->default(0);
            $table->decimal('total_sin_factura', 12, 2)->default(0);
            $table->decimal('iva_total', 12, 2)->default(0);
            $table->decimal('monto_comprobacion', 12, 2)->default(0);

            $table->string('estatus')->default('capturada');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobaciones');
    }
};
