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
        Schema::create('leaderboard_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('period');
            $table->date('period_date');
            $table->unsignedBigInteger('total_points')->default(0);
            $table->unsignedSmallInteger('prediction_count')->default(0);
            $table->unsignedSmallInteger('correct_predictions')->default(0);
            $table->unsignedSmallInteger('rank')->nullable();
            $table->string('country_code', 3)->nullable();
            $table->timestamps();

            $table->index(['tournament_id', 'period', 'period_date', 'total_points'],'tournament_period_leaderboard_snapshots');
            $table->index(['tournament_id', 'country_code', 'total_points'],'tournament_country_leaderboard_snapshots');
            $table->unique(['user_id', 'tournament_id', 'period', 'period_date'],'user_tournament_period_leaderboard_snapshots');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaderboard_snapshots');
    }
};
