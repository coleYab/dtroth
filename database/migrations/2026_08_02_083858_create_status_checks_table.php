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
        Schema::create('status_checks', function (Blueprint $table) {
            $table->id();
            $table->string('component');
            $table->string('status'); // ok | degraded | down
            $table->unsignedSmallInteger('response_time_ms')->nullable();
            $table->timestamp('checked_at')->index();

            $table->index(['component', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_checks');
    }
};
