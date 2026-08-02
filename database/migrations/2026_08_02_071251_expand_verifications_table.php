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
        Schema::table('verifications', function (Blueprint $table) {
            $table->string('query_type')->nullable()->after('status');
            $table->unsignedTinyInteger('query_value')->nullable()->after('query_type');
            $table->boolean('result')->nullable()->after('query_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verifications', function (Blueprint $table) {
            $table->dropColumn(['query_type', 'query_value', 'result']);
        });
    }
};
