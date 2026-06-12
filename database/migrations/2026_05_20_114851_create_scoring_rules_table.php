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
        Schema::create('scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('correct_winner_points')->default(3);
            $table->unsignedTinyInteger('correct_draw_points')->default(5);
            $table->unsignedTinyInteger('exact_score_points')->default(10);
            $table->unsignedTinyInteger('correct_goal_difference_points')->default(5);
            $table->unsignedTinyInteger('correct_one_team_score_points')->default(2);
            $table->unsignedTinyInteger('knockout_multiplier')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scoring_rules');
    }
};
