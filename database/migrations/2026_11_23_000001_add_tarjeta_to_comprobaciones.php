<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobaciones', function (Blueprint $table) {
            // Card this comprobación belongs to (one comprobación per card).
            $table->string('tarjeta_nombre')->nullable();
            $table->string('tarjeta_numero')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('comprobaciones', function (Blueprint $table) {
            $table->dropColumn(['tarjeta_nombre', 'tarjeta_numero']);
        });
    }
};
