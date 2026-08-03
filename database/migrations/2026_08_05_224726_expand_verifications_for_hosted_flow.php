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
            $table->foreignId('app_environment_id')->nullable()->after('app_id')->constrained()->nullOnDelete();
            $table->string('token')->nullable()->unique()->after('app_environment_id');
            $table->timestamp('expires_at')->nullable()->after('token');
            $table->timestamp('completed_at')->nullable()->after('expires_at');
            $table->string('subject_identifier')->nullable()->after('completed_at');
            $table->string('callback_url')->nullable()->after('subject_identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('app_environment_id');
            $table->dropColumn(['token', 'expires_at', 'completed_at', 'subject_identifier', 'callback_url']);
        });
    }
};
