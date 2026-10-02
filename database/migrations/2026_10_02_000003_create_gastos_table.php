<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comprobacion_id')->constrained('comprobaciones')->cascadeOnDelete();

            $table->string('tipo', 2)->default('sf');   // cf | sf
            $table->date('fecha')->nullable();
            $table->string('categoria')->nullable();
            $table->string('compania')->nullable();      // emisor nombre / concepto
            $table->string('rfc', 13)->nullable();
            $table->string('uuid', 40)->nullable();

            $table->decimal('importe', 12, 2)->default(0); // subtotal
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('justificacion')->nullable();

            // Stored CFDI files (base64 is decoded to storage, not kept in DB).
            $table->string('xml_filename')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('pdf_filename')->nullable();
            $table->string('pdf_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
