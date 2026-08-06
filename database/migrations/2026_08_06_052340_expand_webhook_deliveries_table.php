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
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->string('idempotency_key')->nullable()->after('event');
            $table->index(['webhook_id', 'event', 'idempotency_key'], 'webhook_deliveries_idempotency_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->dropIndex('webhook_deliveries_idempotency_index');
            $table->dropColumn('idempotency_key');
        });
    }
};
