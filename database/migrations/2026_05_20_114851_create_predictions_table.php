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
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fixture_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('predicted_home_score')->nullable();
            $table->unsignedTinyInteger('predicted_away_score')->nullable();
            $table->string('predicted_winner')->nullable();
            $table->unsignedSmallInteger('points_earned')->default(0);
            $table->boolean('is_exact_score')->default(false);
            $table->boolean('is_correct_winner')->default(false);
            $table->boolean('is_correct_goal_difference')->default(false);
            $table->boolean('is_calculated')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'fixture_id']);
            $table->index(['fixture_id', 'points_earned']);
            $table->index(['user_id', 'is_calculated']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
