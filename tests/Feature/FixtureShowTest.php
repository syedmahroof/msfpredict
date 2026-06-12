<?php

use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function showFixture(array $attribs = []): Fixture
{
    $tournament = Tournament::factory()->create();

    return Fixture::factory()->create(array_merge([
        'tournament_id' => $tournament->id,
        'home_team_id' => Team::factory()->create(['country_code' => 'SH1'])->id,
        'away_team_id' => Team::factory()->create(['country_code' => 'SH2'])->id,
        'round' => 'group_stage',
    ], $attribs));
}

test('predictions are hidden before kick-off', function () {
    $fixture = showFixture(['scheduled_at' => now()->addHour(), 'status' => 'upcoming']);

    Prediction::factory()->create([
        'fixture_id' => $fixture->id,
        'user_id' => User::factory()->create()->id,
    ]);

    $this->get(route('fixtures.show', $fixture))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('isLocked', false)
            ->has('predictions', 0)
        );
});

test('all predictions are revealed after kick-off', function () {
    $fixture = showFixture([
        'scheduled_at' => now()->subHour(),
        'status' => 'finished',
        'home_score' => 1,
        'away_score' => 0,
        'winner' => 'home',
    ]);

    User::factory()->count(3)->create()->each(fn ($user) => Prediction::factory()->create([
        'fixture_id' => $fixture->id,
        'user_id' => $user->id,
    ]));

    $this->get(route('fixtures.show', $fixture))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('isLocked', true)
            ->has('predictions', 3)
        );
});
