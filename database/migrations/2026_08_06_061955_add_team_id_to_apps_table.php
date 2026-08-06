<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('user_id')->constrained('teams');
        });

        foreach (DB::table('apps')->distinct()->pluck('user_id') as $userId) {
            $user = DB::table('users')->where('id', $userId)->first();

            if ($user === null) {
                continue;
            }

            $teamId = DB::table('teams')->where('owner_user_id', $userId)->value('id');

            if ($teamId === null) {
                $teamId = DB::table('teams')->insertGetId([
                    'owner_user_id' => $userId,
                    'name' => $user->name."'s Team",
                    'slug' => Str::slug($user->name).'-'.Str::lower(Str::random(6)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('team_members')->insert([
                    'team_id' => $teamId,
                    'user_id' => $userId,
                    'role' => 'owner',
                    'accepted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('apps')->where('user_id', $userId)->update(['team_id' => $teamId]);
        }

        Schema::table('apps', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
        });
    }
};
