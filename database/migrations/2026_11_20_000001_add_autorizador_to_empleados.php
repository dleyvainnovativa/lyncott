<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            // Approver tied to the usuario de carga (from the cards spreadsheet).
            $table->string('autorizador_nombre')->nullable()->after('sucursal_cedis');
            $table->string('autorizador_correo')->nullable()->after('autorizador_nombre');
            $table->string('autorizador_numero', 30)->nullable()->after('autorizador_correo');
            $table->string('autorizador_rfc', 13)->nullable()->after('autorizador_numero');
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn([
                'autorizador_nombre', 'autorizador_correo',
                'autorizador_numero', 'autorizador_rfc',
            ]);
        });
    }
};
