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
        Schema::table('api_keys', function (Blueprint $table) {
            $table->renameColumn('key', 'public_key');
            $table->string('secret_hash')->nullable()->after('public_key');
            $table->string('secret_preview')->nullable()->after('secret_hash');
            $table->timestamp('revoked_at')->nullable()->after('last_used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->renameColumn('public_key', 'key');
            $table->dropColumn(['secret_hash', 'secret_preview', 'revoked_at']);
        });
    }
};
