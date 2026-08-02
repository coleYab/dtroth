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
        Schema::table('api_calls', function (Blueprint $table) {
            $table->string('query_type')->nullable()->after('path');
            $table->unsignedTinyInteger('query_value')->nullable()->after('query_type');
            $table->boolean('result')->nullable()->after('query_value');
            $table->unsignedSmallInteger('response_time_ms')->nullable()->after('status_code');
            $table->json('request_payload')->nullable()->after('response_time_ms');
            $table->json('response_payload')->nullable()->after('request_payload');

            $table->index(['app_id', 'created_at', 'query_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_calls', function (Blueprint $table) {
            $table->dropIndex(['app_id', 'created_at', 'query_type']);
            $table->dropColumn([
                'query_type',
                'query_value',
                'result',
                'response_time_ms',
                'request_payload',
                'response_payload',
            ]);
        });
    }
};
