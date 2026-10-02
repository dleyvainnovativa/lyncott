<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();

            // Identity (the two lookup keys).
            $table->string('numero_empleado', 30)->unique();
            $table->string('rfc', 13)->index();

            // Display data (filled read-only on step 1 / review).
            $table->string('nombre_completo');
            $table->string('correo')->nullable();
            $table->string('puesto')->nullable();
            $table->string('gerencia')->nullable();
            $table->string('departamento')->nullable();
            $table->string('direccion')->nullable();          // "Dirección perteneciente"
            $table->unsignedSmallInteger('dias_disponibles')->default(0);

            // Payload-ready fields (shared Smartker attributes, used in later phases).
            $table->string('centro_costos')->nullable();
            $table->string('clabe', 18)->nullable();
            $table->string('banco')->nullable();
            $table->string('sucursal_cedis')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
