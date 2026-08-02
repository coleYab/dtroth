<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_environments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_live')->default(false);
            $table->json('redirect_uris')->nullable();
            $table->json('allowed_origins')->nullable();
            $table->timestamps();

            $table->unique(['app_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_environments');
    }
};
