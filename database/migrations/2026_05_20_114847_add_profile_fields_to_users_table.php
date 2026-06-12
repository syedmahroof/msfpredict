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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('name');
            $table->string('avatar')->nullable()->after('email');
            $table->string('country_code', 3)->nullable()->after('avatar');
            $table->string('phone')->nullable()->after('country_code');
            $table->unsignedBigInteger('xp_points')->default(0)->after('phone');
            $table->unsignedTinyInteger('level')->default(1)->after('xp_points');
            $table->unsignedSmallInteger('prediction_streak')->default(0)->after('level');
            $table->unsignedSmallInteger('longest_streak')->default(0)->after('prediction_streak');
            $table->string('referral_code', 12)->unique()->nullable()->after('longest_streak');
            $table->foreignId('referred_by_id')->nullable()->constrained('users')->nullOnDelete()->after('referral_code');
            $table->boolean('is_admin')->default(false)->after('referred_by_id');
            $table->timestamp('last_prediction_at')->nullable()->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by_id']);
            $table->dropColumn([
                'username', 'avatar', 'country_code', 'phone', 'xp_points', 'level',
                'prediction_streak', 'longest_streak', 'referral_code', 'referred_by_id',
                'is_admin', 'last_prediction_at',
            ]);
        });
    }
};
