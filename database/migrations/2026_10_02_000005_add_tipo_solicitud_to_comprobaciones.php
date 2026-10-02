<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobaciones', function (Blueprint $table) {
            // 'comprobacion' | 'anticipo'
            $table->string('tipo_solicitud')->default('comprobacion')->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('comprobaciones', function (Blueprint $table) {
            $table->dropColumn('tipo_solicitud');
        });
    }
};
