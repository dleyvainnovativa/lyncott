<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('smartker_payloads', function (Blueprint $table) {
            $table->unsignedSmallInteger('http_status')->nullable()->after('sent');
        });
    }

    public function down(): void
    {
        Schema::table('smartker_payloads', function (Blueprint $table) {
            $table->dropColumn('http_status');
        });
    }
};
