<?php

use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\ScoringRule;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\PredictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makePredictionFixture(array $fixtureAttribs = []): Fixture
{
    $tournament = Tournament::factory()->create();
    // Explicit, unique country codes — the factory's random pool can otherwise
    // collide on the unique country_code column.
    $home = Team::factory()->create(['country_code' => 'HMA']);
    $away = Team::factory()->create(['country_code' => 'AWB']);

    ScoringRule::create([
        'tournament_id' => $tournament->id,
        'correct_winner_points' => 3,
        'correct_draw_points' => 5,
        'exact_score_points' => 10,
        'correct_goal_difference_points' => 5,
        'correct_one_team_score_points' => 2,
        'knockout_multiplier' => 1,
    ]);

    return Fixture::factory()->create(array_merge([
        'tournament_id' => $tournament->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'scheduled_at' => now()->addHours(12),
        'status' => 'upcoming',
    ], $fixtureAttribs));
}

// Builds a finished fixture using the live (seeded) scoring values:
// exact 10, winner 5, draw 3, goal difference 2, one team score 1.
function makeScoredFixture(array $result): Fixture
{
    $tournament = Tournament::factory()->create();

    ScoringRule::create([
        'tournament_id' => $tournament->id,
        'correct_winner_points' => 5,
        'correct_draw_points' => 3,
        'exact_score_points' => 10,
        'correct_goal_difference_points' => 2,
        'correct_one_team_score_points' => 1,
        'knockout_multiplier' => 2,
    ]);

    return Fixture::factory()->create(array_merge([
        'tournament_id' => $tournament->id,
        'home_team_id' => Team::factory()->create(['country_code' => 'HOM'])->id,
        'away_team_id' => Team::factory()->create(['country_code' => 'AWY'])->id,
        'round' => 'group_stage',
        'status' => 'finished',
        'scheduled_at' => now()->subHour(),
    ], $result));
}

function scorePrediction(Fixture $fixture, int $home, int $away): Prediction
{
    $prediction = Prediction::factory()->create([
        'user_id' => User::factory()->create()->id,
        'fixture_id' => $fixture->id,
        'predicted_home_score' => $home,
        'predicted_away_score' => $away,
        'is_calculated' => false,
    ]);

    app(PredictionService::class)->calculatePointsForFixture($fixture);

    return $prediction->refresh();
}

test('a correct draw scores the draw points, not the goal-difference points', function () {
    $fixture = makeScoredFixture(['home_score' => 0, 'away_score' => 0, 'winner' => 'draw']);

    $prediction = scorePrediction($fixture, 1, 1);

    expect($prediction->points_earned)->toBe(3)
        ->and($prediction->is_correct_winner)->toBeTrue()
        ->and($prediction->is_exact_score)->toBeFalse();
});

test('an exact draw still scores the exact-score points', function () {
    $fixture = makeScoredFixture(['home_score' => 1, 'away_score' => 1, 'winner' => 'draw']);

    expect(scorePrediction($fixture, 1, 1)->points_earned)->toBe(10);
});

test('a correct winner with the right margin adds the goal-difference bonus', function () {
    $fixture = makeScoredFixture(['home_score' => 3, 'away_score' => 1, 'winner' => 'home']);

    // Home win, margin 2 — matches actual margin but not the exact score: 5 + 2.
    $prediction = scorePrediction($fixture, 2, 0);

    expect($prediction->points_earned)->toBe(7)
        ->and($prediction->is_correct_goal_difference)->toBeTrue();
});

test('a correct winner with the wrong margin scores only the winner points', function () {
    $fixture = makeScoredFixture(['home_score' => 3, 'away_score' => 1, 'winner' => 'home']);

    expect(scorePrediction($fixture, 1, 0)->points_earned)->toBe(5);
});

test('knockout fixtures double the points', function () {
    // Correct draw in a knockout: draw points (3) x knockout multiplier (2).
    $fixture = makeScoredFixture([
        'round' => 'round_of_16',
        'home_score' => 2,
        'away_score' => 2,
        'winner' => 'draw',
    ]);

    expect(scorePrediction($fixture, 1, 1)->points_earned)->toBe(6);
});

test('saves a new prediction for an unlocked fixture', function () {
    $user = User::factory()->create();
    $fixture = makePredictionFixture();

    $service = app(PredictionService::class);
    $prediction = $service->savePrediction($user, $fixture, [
        'predicted_home_score' => 2,
        'predicted_away_score' => 1,
    ]);

    expect($prediction)->toBeInstanceOf(Prediction::class)
        ->and($prediction->predicted_home_score)->toBe(2)
        ->and($prediction->predicted_away_score)->toBe(1);
});

test('updates an existing prediction', function () {
    $user = User::factory()->create();
    $fixture = makePredictionFixture();

    $service = app(PredictionService::class);
    $service->savePrediction($user, $fixture, ['predicted_home_score' => 1, 'predicted_away_score' => 0]);
    $updated = $service->savePrediction($user, $fixture, ['predicted_home_score' => 3, 'predicted_away_score' => 2]);

    expect(Prediction::where('user_id', $user->id)->where('fixture_id', $fixture->id)->count())->toBe(1)
        ->and($updated->predicted_home_score)->toBe(3)
        ->and($updated->predicted_away_score)->toBe(2);
});

test('throws when fixture is locked via predictions_locked_at', function () {
    $user = User::factory()->create();
    $fixture = makePredictionFixture([
        'predictions_locked_at' => now()->subMinute(),
    ]);

    $service = app(PredictionService::class);

    expect(fn () => $service->savePrediction($user, $fixture, ['predicted_home_score' => 1, 'predicted_away_score' => 0]))
        ->toThrow(RuntimeException::class, 'Predictions are locked for this fixture.');
});

test('throws when kickoff has passed and no explicit lock time is set', function () {
    $user = User::factory()->create();
    $fixture = makePredictionFixture([
        'scheduled_at' => now()->subMinute(),
    ]);

    $service = app(PredictionService::class);

    expect(fn () => $service->savePrediction($user, $fixture, ['predicted_home_score' => 0, 'predicted_away_score' => 0]))
        ->toThrow(RuntimeException::class);
});

test('predictions are not open yet more than 24 hours before kick-off', function () {
    $user = User::factory()->create();
    $fixture = makePredictionFixture(['scheduled_at' => now()->addHours(25)]);

    expect(fn () => app(PredictionService::class)
        ->savePrediction($user, $fixture, ['predicted_home_score' => 1, 'predicted_away_score' => 0]))
        ->toThrow(RuntimeException::class, 'Predictions open 24 hours before kick-off.');
});

test('predictions are open within 24 hours of kick-off', function () {
    $user = User::factory()->create();
    $fixture = makePredictionFixture(['scheduled_at' => now()->addHours(12)]);

    $prediction = app(PredictionService::class)
        ->savePrediction($user, $fixture, ['predicted_home_score' => 1, 'predicted_away_score' => 0]);

    expect($prediction)->toBeInstanceOf(Prediction::class);
});

test('awards exact score points (10) when prediction matches result exactly', function () {
    $user = User::factory()->create();
    $fixture = makePredictionFixture([
        'status' => 'finished',
        'home_score' => 2,
        'away_score' => 1,
        'winner' => 'home',
        'scheduled_at' => now()->subHour(),
    ]);

    Prediction::factory()->create([
        'user_id' => $user->id,
        'fixture_id' => $fixture->id,
        'predicted_home_score' => 2,
        'predicted_away_score' => 1,
        'is_calculated' => false,
    ]);

    $service = app(PredictionService::class);
    $service->calculatePointsForFixture($fixture);

    $prediction = Prediction::where('user_id', $user->id)->where('fixture_id', $fixture->id)->first();

    expect($prediction->points_earned)->toBe(10)
        ->and($prediction->is_exact_score)->toBeTrue()
        ->and($prediction->is_calculated)->toBeTrue();
});

test('awards correct winner points (3) for correct non-draw winner without exact score', function () {
    $user = User::factory()->create();
    $fixture = makePredictionFixture([
        'status' => 'finished',
        'home_score' => 3,
        'away_score' => 1,
        'winner' => 'home',
        'scheduled_at' => now()->subHour(),
    ]);

    Prediction::factory()->create([
        'user_id' => $user->id,
        'fixture_id' => $fixture->id,
        'predicted_home_score' => 2,
        'predicted_away_score' => 1,
        'is_calculated' => false,
    ]);

    $service = app(PredictionService::class);
    $service->calculatePointsForFixture($fixture);

    $prediction = Prediction::where('user_id', $user->id)->where('fixture_id', $fixture->id)->first();

    expect($prediction->points_earned)->toBe(3)
        ->and($prediction->is_correct_winner)->toBeTrue()
        ->and($prediction->is_exact_score)->toBeFalse();
});

test('awards correct draw points (5) for a correctly predicted draw', function () {
    $user = User::factory()->create();
    $fixture = makePredictionFixture([
        'round' => 'group_stage',
        'status' => 'finished',
        'home_score' => 1,
        'away_score' => 1,
        'winner' => 'draw',
        'scheduled_at' => now()->subHour(),
    ]);

    Prediction::factory()->create([
        'user_id' => $user->id,
        'fixture_id' => $fixture->id,
        'predicted_home_score' => 2,
        'predicted_away_score' => 2,
        'is_calculated' => false,
    ]);

    $service = app(PredictionService::class);
    $service->calculatePointsForFixture($fixture);

    $prediction = Prediction::where('user_id', $user->id)->where('fixture_id', $fixture->id)->first();

    expect($prediction->points_earned)->toBe(5)
        ->and($prediction->is_correct_winner)->toBeTrue();
});

test('awards zero points for a completely wrong prediction', function () {
    $user = User::factory()->create();
    $fixture = makePredictionFixture([
        'status' => 'finished',
        'home_score' => 3,
        'away_score' => 0,
        'winner' => 'home',
        'scheduled_at' => now()->subHour(),
    ]);

    Prediction::factory()->create([
        'user_id' => $user->id,
        'fixture_id' => $fixture->id,
        'predicted_home_score' => 0,
        'predicted_away_score' => 3,
        'is_calculated' => false,
    ]);

    $service = app(PredictionService::class);
    $service->calculatePointsForFixture($fixture);

    $prediction = Prediction::where('user_id', $user->id)->where('fixture_id', $fixture->id)->first();

    expect($prediction->points_earned)->toBe(0)
        ->and($prediction->is_correct_winner)->toBeFalse()
        ->and($prediction->is_exact_score)->toBeFalse();
});

test('awards exact score points in knockout when score and penalty winner match', function () {
    $user = User::factory()->create();
    $fixture = makePredictionFixture([
        'round' => 'round_of_16',
        'status' => 'finished',
        'home_score' => 1,
        'away_score' => 1,
        'winner' => 'home', // admin recorded penalty winner
        'scheduled_at' => now()->subHour(),
    ]);

    Prediction::factory()->create([
        'user_id' => $user->id,
        'fixture_id' => $fixture->id,
        'predicted_home_score' => 1,
        'predicted_away_score' => 1,
        'predicted_winner' => 'home',
        'is_calculated' => false,
    ]);

    $service = app(PredictionService::class);
    $service->calculatePointsForFixture($fixture);

    $prediction = Prediction::where('user_id', $user->id)->where('fixture_id', $fixture->id)->first();

    // exact score points (10) * knockout multiplier (1 in this test setup because makePredictionFixture sets knockout_multiplier to 1, wait, let's check: makePredictionFixture sets knockout_multiplier to 1. makeScoredFixture sets it to 2. Let's look at makePredictionFixture definition. It has knockout_multiplier = 1.)
    expect($prediction->points_earned)->toBe(10)
        ->and($prediction->is_exact_score)->toBeTrue()
        ->and($prediction->is_correct_winner)->toBeTrue();
});

test('awards correct draw points in knockout when score matches but wrong team advanced on penalties', function () {
    $user = User::factory()->create();
    $fixture = makePredictionFixture([
        'round' => 'round_of_16',
        'status' => 'finished',
        'home_score' => 1,
        'away_score' => 1,
        'winner' => 'away', // admin recorded penalty winner
        'scheduled_at' => now()->subHour(),
    ]);

    Prediction::factory()->create([
        'user_id' => $user->id,
        'fixture_id' => $fixture->id,
        'predicted_home_score' => 1,
        'predicted_away_score' => 1,
        'predicted_winner' => 'home', // wrong team predicted to advance
        'is_calculated' => false,
    ]);

    $service = app(PredictionService::class);
    $service->calculatePointsForFixture($fixture);

    $prediction = Prediction::where('user_id', $user->id)->where('fixture_id', $fixture->id)->first();

    // correct draw points (5) * knockout multiplier (1)
    expect($prediction->points_earned)->toBe(5)
        ->and($prediction->is_exact_score)->toBeFalse()
        ->and($prediction->is_correct_winner)->toBeFalse();
});

test('awards correct draw points in knockout when winner matches but score is a different draw', function () {
    $user = User::factory()->create();
    $fixture = makePredictionFixture([
        'round' => 'round_of_16',
        'status' => 'finished',
        'home_score' => 2,
        'away_score' => 2,
        'winner' => 'home',
        'scheduled_at' => now()->subHour(),
    ]);

    Prediction::factory()->create([
        'user_id' => $user->id,
        'fixture_id' => $fixture->id,
        'predicted_home_score' => 1,
        'predicted_away_score' => 1,
        'predicted_winner' => 'home',
        'is_calculated' => false,
    ]);

    $service = app(PredictionService::class);
    $service->calculatePointsForFixture($fixture);

    $prediction = Prediction::where('user_id', $user->id)->where('fixture_id', $fixture->id)->first();

    // correct winner (3) + margin bonus (5) * knockout multiplier (1) = 8
    expect($prediction->points_earned)->toBe(8)
        ->and($prediction->is_exact_score)->toBeFalse()
        ->and($prediction->is_correct_winner)->toBeTrue();
});
