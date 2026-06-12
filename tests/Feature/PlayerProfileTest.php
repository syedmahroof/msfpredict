<?php

use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('a player profile lists their predictions for started matches with points', function () {
    $tournament = Tournament::factory()->create(['is_active' => true]);
    $player = User::factory()->create();

    // A played match with a scored prediction.
    $played = Fixture::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => Team::factory()->create(['country_code' => 'PP1'])->id,
        'away_team_id' => Team::factory()->create(['country_code' => 'PP2'])->id,
        'scheduled_at' => now()->subHour(),
        'status' => 'finished',
        'home_score' => 1,
        'away_score' => 0,
    ]);
    Prediction::factory()->create([
        'user_id' => $player->id,
        'fixture_id' => $played->id,
        'predicted_home_score' => 1,
        'predicted_away_score' => 0,
        'points_earned' => 10,
        'is_calculated' => true,
        'is_exact_score' => true,
    ]);

    // A future match — its pick must stay hidden.
    $future = Fixture::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => Team::factory()->create(['country_code' => 'PP3'])->id,
        'away_team_id' => Team::factory()->create(['country_code' => 'PP4'])->id,
        'scheduled_at' => now()->addDay(),
        'status' => 'upcoming',
    ]);
    Prediction::factory()->create([
        'user_id' => $player->id,
        'fixture_id' => $future->id,
        'is_calculated' => false,
    ]);

    $this->get(route('players.show', $player))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Players/Show')
            ->where('player.id', $player->id)
            ->where('stats.total_points', 10)
            ->where('stats.exact_scores', 1)
            ->has('predictions', 1) // only the started match is revealed
            ->where('predictions.0.fixture.id', $played->id)
        );
});
