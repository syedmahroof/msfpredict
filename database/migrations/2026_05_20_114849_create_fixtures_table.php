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
        Schema::create('fixtures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_team_id')->constrained('teams');
            $table->foreignId('away_team_id')->constrained('teams');
            $table->foreignId('stadium_id')->nullable()->constrained()->nullOnDelete();
            $table->string('round');
            $table->string('group', 1)->nullable();
            $table->unsignedTinyInteger('match_day')->nullable();
            $table->timestamp('scheduled_at');
            $table->timestamp('predictions_locked_at')->nullable();
            $table->string('status')->default('upcoming');
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();
            $table->string('winner')->nullable();
            $table->boolean('points_calculated')->default(false);
            $table->timestamps();

            $table->index(['tournament_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixtures');
    }
};
