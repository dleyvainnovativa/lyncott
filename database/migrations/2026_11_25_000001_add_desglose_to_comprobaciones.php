<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobaciones', function (Blueprint $table) {
            // Anticipo breakdown: { alimentos, pasajes, peajes, otros:[{monto,descripcion}], total }
            $table->json('desglose')->nullable()->after('fecha_dispersacion');
        });
    }

    public function down(): void
    {
        Schema::table('comprobaciones', function (Blueprint $table) {
            $table->dropColumn('desglose');
        });
    }
};
