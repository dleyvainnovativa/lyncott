<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smartker_payloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comprobacion_id')->constrained('comprobaciones')->cascadeOnDelete();

            $table->string('endpoint')->default('complemento');
            $table->boolean('live')->default(false);   // was it actually sent?
            $table->boolean('sent')->default(false);
            $table->unsignedInteger('file_count')->default(0);

            // The built attributes array, base64 file values omitted for storage.
            $table->json('attributes');
            $table->text('response')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smartker_payloads');
    }
};
