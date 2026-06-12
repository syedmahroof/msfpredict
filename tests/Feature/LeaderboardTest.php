<?php

use App\Models\ScoringRule;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the leaderboard renders (not a 404) when there is no active tournament', function () {
    $this->get('/leaderboard')->assertSuccessful();
});

test('the leaderboard renders with an active tournament and its scoring rules', function () {
    $tournament = Tournament::factory()->create(['is_active' => true]);
    ScoringRule::create([
        'tournament_id' => $tournament->id,
        'correct_winner_points' => 5,
        'correct_draw_points' => 3,
        'exact_score_points' => 10,
        'correct_goal_difference_points' => 2,
        'correct_one_team_score_points' => 1,
        'knockout_multiplier' => 2,
    ]);

    $this->get('/leaderboard')->assertSuccessful();
});
