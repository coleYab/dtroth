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
        Schema::create('verification_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained()->cascadeOnDelete();
            $table->string('logo_path')->nullable();
            $table->string('brand_color', 7)->default('#0f172a');
            $table->string('success_redirect_url')->nullable();
            $table->string('failure_redirect_url')->nullable();
            $table->string('callback_url')->nullable();
            $table->timestamps();

            $table->unique('app_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_flows');
    }
};
