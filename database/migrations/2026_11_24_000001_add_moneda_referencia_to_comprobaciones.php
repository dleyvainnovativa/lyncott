<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobaciones', function (Blueprint $table) {
            $table->string('moneda', 8)->nullable()->after('monto_comprobacion');
            $table->string('referencia', 40)->nullable()->after('moneda');
            $table->date('fecha_dispersacion')->nullable()->after('referencia');
        });
    }

    public function down(): void
    {
        Schema::table('comprobaciones', function (Blueprint $table) {
            $table->dropColumn(['moneda', 'referencia', 'fecha_dispersacion']);
        });
    }
};
