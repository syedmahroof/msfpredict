<?php

use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

function makeUpcomingFixture(): Fixture
{
    $tournament = Tournament::factory()->create();
    $home = Team::factory()->create();
    $away = Team::factory()->create();

    return Fixture::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'scheduled_at' => now()->addHours(12),
        'status' => 'upcoming',
    ]);
}

test('guests are redirected to login when accessing predictions page', function () {
    $this->get(route('predictions.index'))->assertRedirect(route('login'));
});

test('authenticated users can access the predictions index', function () {
    $user = User::factory()->create();
    Tournament::factory()->create();

    $this->actingAs($user)->get(route('predictions.index'))->assertSuccessful();
});

test('authenticated user can submit a prediction', function () {
    $user = User::factory()->create();
    $fixture = makeUpcomingFixture();

    $this->actingAs($user)
        ->post(route('predictions.store', $fixture), [
            'predicted_home_score' => 2,
            'predicted_away_score' => 1,
            'predicted_winner' => 'home',
        ])
        ->assertRedirect();

    expect(Prediction::where('user_id', $user->id)->where('fixture_id', $fixture->id)->exists())->toBeTrue();
});

test('guests cannot submit a prediction', function () {
    $fixture = makeUpcomingFixture();

    $this->post(route('predictions.store', $fixture), [
        'predicted_home_score' => 1,
        'predicted_away_score' => 0,
        'predicted_winner' => 'home',
    ])->assertRedirect(route('login'));
});

test('prediction score cannot be negative', function () {
    $user = User::factory()->create();
    $fixture = makeUpcomingFixture();

    $this->actingAs($user)
        ->post(route('predictions.store', $fixture), [
            'predicted_home_score' => -1,
            'predicted_away_score' => 0,
            'predicted_winner' => 'home',
        ])
        ->assertSessionHasErrors('predicted_home_score');
});

test('prediction score cannot exceed 20', function () {
    $user = User::factory()->create();
    $fixture = makeUpcomingFixture();

    $this->actingAs($user)
        ->post(route('predictions.store', $fixture), [
            'predicted_home_score' => 21,
            'predicted_away_score' => 0,
            'predicted_winner' => 'home',
        ])
        ->assertSessionHasErrors('predicted_home_score');
});

test('the predict list only shows fixtures whose 24h window is open', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create();

    // In window: kicks off within the next 24 hours.
    $inWindow = Fixture::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => Team::factory()->create(['country_code' => 'P01'])->id,
        'away_team_id' => Team::factory()->create(['country_code' => 'P02'])->id,
        'scheduled_at' => now()->addHours(6),
        'status' => 'upcoming',
    ]);

    // Already kicked off — must not be predictable anymore.
    Fixture::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => Team::factory()->create(['country_code' => 'P03'])->id,
        'away_team_id' => Team::factory()->create(['country_code' => 'P04'])->id,
        'scheduled_at' => now()->subMinute(),
        'status' => 'upcoming',
    ]);

    // More than 24 hours away — window not open yet.
    Fixture::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => Team::factory()->create(['country_code' => 'P05'])->id,
        'away_team_id' => Team::factory()->create(['country_code' => 'P06'])->id,
        'scheduled_at' => now()->addDays(3),
        'status' => 'upcoming',
    ]);

    $this->actingAs($user)
        ->get(route('predictions.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('fixtures.data', 1)
            ->where('fixtures.data.0.id', $inWindow->id)
        );
});

test('the predict list includes live matches', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create();

    $live = Fixture::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => Team::factory()->create(['country_code' => 'L01'])->id,
        'away_team_id' => Team::factory()->create(['country_code' => 'L02'])->id,
        'scheduled_at' => now()->subMinutes(30),
        'status' => 'live',
    ]);

    $this->actingAs($user)
        ->get(route('predictions.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('fixtures.data', 1)
            ->where('fixtures.data.0.id', $live->id)
        );
});

test('cannot predict on a locked fixture', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->create();
    $home = Team::factory()->create();
    $away = Team::factory()->create();

    $fixture = Fixture::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'scheduled_at' => now()->subHour(),
        'status' => 'upcoming',
    ]);

    $this->actingAs($user)
        ->post(route('predictions.store', $fixture), [
            'predicted_home_score' => 1,
            'predicted_away_score' => 0,
            'predicted_winner' => 'home',
        ])
        ->assertSessionHasErrors('prediction');
});

test('authenticated user can view their own predictions', function () {
    $user = User::factory()->create();
    Tournament::factory()->create();

    $this->actingAs($user)->get(route('predictions.my'))->assertSuccessful();
});
