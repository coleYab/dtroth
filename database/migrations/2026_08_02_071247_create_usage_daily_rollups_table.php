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
        Schema::create('usage_daily_rollups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('calls_total')->default(0);
            $table->unsignedInteger('verifications_total')->default(0);
            $table->unsignedInteger('failed_calls')->default(0);
            $table->decimal('success_rate', 5, 2)->default(0);
            $table->string('most_used_query')->nullable();
            $table->timestamps();

            $table->unique(['app_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_daily_rollups');
    }
};
