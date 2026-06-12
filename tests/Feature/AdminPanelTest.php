<?php

use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\ScoringRule;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\LeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

function admin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

// Create a team with a guaranteed-unique country code (the factory's random pool
// otherwise collides on the unique country_code column).
function uniqueTeam(): Team
{
    static $i = 0;
    $i++;
    $code = 'X'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);

    return Team::factory()->create([
        'name' => "Team {$code}",
        'slug' => 'team-'.strtolower($code),
        'country_code' => $code,
    ]);
}

function upcomingFixture(): Fixture
{
    $tournament = Tournament::factory()->create();

    return Fixture::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => uniqueTeam()->id,
        'away_team_id' => uniqueTeam()->id,
        'scheduled_at' => now()->addHours(2),
        'status' => 'upcoming',
    ]);
}

test('non-admins cannot access the admin panel', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('admin can view every admin page', function () {
    $admin = admin();

    foreach (['/admin', '/admin/fixtures', '/admin/fixtures/create', '/admin/users', '/admin/teams'] as $url) {
        $this->actingAs($admin)->get($url)->assertSuccessful();
    }
});

test('admin can view a user profile and a match predictions page', function () {
    $admin = admin();
    $fixture = upcomingFixture();

    $player = User::factory()->create();
    Prediction::factory()->create([
        'user_id' => $player->id,
        'fixture_id' => $fixture->id,
        'predicted_home_score' => 1,
        'predicted_away_score' => 0,
    ]);

    $this->actingAs($admin)
        ->get("/admin/users/{$player->id}")
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Users/Show')
            ->where('player.id', $player->id)
            ->has('predictions', 1)
        );

    $this->actingAs($admin)
        ->get("/admin/fixtures/{$fixture->id}/predictions")
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Fixtures/Predictions')
            ->where('fixture.id', $fixture->id)
            ->has('predictions', 1)
        );
});

test('admin can change a user password', function () {
    $admin = admin();
    $player = User::factory()->create();

    $this->actingAs($admin)
        ->patch("/admin/users/{$player->id}/password", [
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])
        ->assertRedirect();

    expect(Hash::check('new-secret-password', $player->refresh()->password))->toBeTrue();
});

test('changing a user password requires a matching confirmation', function () {
    $admin = admin();
    $player = User::factory()->create();

    $this->actingAs($admin)
        ->patch("/admin/users/{$player->id}/password", [
            'password' => 'new-secret-password',
            'password_confirmation' => 'does-not-match',
        ])
        ->assertSessionHasErrors('password');
});

test('non-admins cannot change a user password', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $player = User::factory()->create();

    $this->actingAs($user)
        ->patch("/admin/users/{$player->id}/password", [
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])
        ->assertForbidden();
});

test('admin can create a fixture', function () {
    $admin = admin();
    $tournament = Tournament::factory()->create();
    $home = uniqueTeam();
    $away = uniqueTeam();

    $this->actingAs($admin)
        ->post('/admin/fixtures', [
            'tournament_id' => $tournament->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'stadium_id' => null,
            'round' => 'group_stage',
            'group' => 'A',
            'match_day' => 1,
            'scheduled_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
        ])
        ->assertRedirect(route('admin.fixtures.index'));

    expect(Fixture::where('home_team_id', $home->id)->where('away_team_id', $away->id)->exists())->toBeTrue();
});

test('admin can view the edit page and update a fixture', function () {
    $admin = admin();
    $fixture = upcomingFixture();
    $newHome = uniqueTeam();
    $newAway = uniqueTeam();

    $this->actingAs($admin)->get("/admin/fixtures/{$fixture->id}/edit")->assertSuccessful();

    $newTime = now()->addDays(10)->startOfMinute();

    $this->actingAs($admin)
        ->patch("/admin/fixtures/{$fixture->id}", [
            'tournament_id' => $fixture->tournament_id,
            'home_team_id' => $newHome->id,
            'away_team_id' => $newAway->id,
            'stadium_id' => null,
            'round' => 'final',
            'group' => null,
            'match_day' => null,
            'scheduled_at' => $newTime->format('Y-m-d\TH:i'),
        ])
        ->assertRedirect(route('admin.fixtures.index'));

    $fixture->refresh();
    expect($fixture->home_team_id)->toBe($newHome->id)
        ->and($fixture->away_team_id)->toBe($newAway->id)
        ->and($fixture->round)->toBe('final')
        ->and($fixture->scheduled_at->format('Y-m-d H:i'))->toBe($newTime->format('Y-m-d H:i'))
        // Editing the kick-off also moves the prediction lock.
        ->and($fixture->predictions_locked_at->format('Y-m-d H:i'))->toBe($newTime->format('Y-m-d H:i'));
});

test('finishing a match scores predictions immediately and fills the leaderboard', function () {
    $admin = admin();
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

    $fixture = Fixture::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => uniqueTeam()->id,
        'away_team_id' => uniqueTeam()->id,
        'round' => 'group_stage',
        'scheduled_at' => now()->subHour(),
        'status' => 'upcoming',
    ]);

    $player = User::factory()->create();
    Prediction::factory()->create([
        'user_id' => $player->id,
        'fixture_id' => $fixture->id,
        'predicted_home_score' => 2,
        'predicted_away_score' => 1,
        'is_calculated' => false,
    ]);

    $this->actingAs($admin)
        ->patch("/admin/fixtures/{$fixture->id}/result", [
            'home_score' => 2,
            'away_score' => 1,
            'status' => 'finished',
            'winner' => 'home',
        ])
        ->assertRedirect();

    $prediction = Prediction::where('user_id', $player->id)->first();
    expect($prediction->is_calculated)->toBeTrue()
        ->and($prediction->points_earned)->toBe(10); // exact score

    $board = app(LeaderboardService::class)->getGlobalLeaderboard($tournament);
    expect($board)->not->toBeEmpty()
        ->and((int) $board->first()['total_points'])->toBe(10);
});

test('correcting a finished result recalculates the points', function () {
    $admin = admin();
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

    $fixture = Fixture::factory()->create([
        'tournament_id' => $tournament->id,
        'home_team_id' => uniqueTeam()->id,
        'away_team_id' => uniqueTeam()->id,
        'round' => 'group_stage',
        'scheduled_at' => now()->subHour(),
        'status' => 'upcoming',
    ]);

    $player = User::factory()->create();
    Prediction::factory()->create([
        'user_id' => $player->id,
        'fixture_id' => $fixture->id,
        'predicted_home_score' => 2,
        'predicted_away_score' => 1,
        'is_calculated' => false,
    ]);

    // First result 2–1 → exact score → 10 points.
    $this->actingAs($admin)->patch("/admin/fixtures/{$fixture->id}/result", [
        'home_score' => 2, 'away_score' => 1, 'status' => 'finished', 'winner' => 'home',
    ])->assertRedirect();

    expect(Prediction::where('user_id', $player->id)->first()->points_earned)->toBe(10);

    // Corrected result 3–1 → only correct winner → 5 points.
    $this->actingAs($admin)->patch("/admin/fixtures/{$fixture->id}/result", [
        'home_score' => 3, 'away_score' => 1, 'status' => 'finished', 'winner' => 'home',
    ])->assertRedirect();

    $prediction = Prediction::where('user_id', $player->id)->first();
    expect($prediction->points_earned)->toBe(5)
        ->and($prediction->is_exact_score)->toBeFalse();
});

test('admin can update a result and explicitly choose the winner', function () {
    $admin = admin();
    $fixture = upcomingFixture();

    $this->actingAs($admin)
        ->patch("/admin/fixtures/{$fixture->id}/result", [
            'home_score' => 1,
            'away_score' => 1,
            'status' => 'finished',
            'winner' => 'home',
        ])
        ->assertRedirect();

    $fixture->refresh();
    expect($fixture->home_score)->toBe(1)
        ->and($fixture->away_score)->toBe(1)
        ->and($fixture->winner)->toBe('home')
        ->and($fixture->status)->toBe('finished');
});

test('winner is inferred from the score when not provided', function () {
    $admin = admin();
    $fixture = upcomingFixture();

    $this->actingAs($admin)
        ->patch("/admin/fixtures/{$fixture->id}/result", [
            'home_score' => 0,
            'away_score' => 2,
            'status' => 'finished',
        ])
        ->assertRedirect();

    expect($fixture->refresh()->winner)->toBe('away');
});

test('admin can assign teams to a TBD knockout fixture', function () {
    $admin = admin();
    $fixture = upcomingFixture();
    $newHome = uniqueTeam();
    $newAway = uniqueTeam();

    $this->actingAs($admin)
        ->patch("/admin/fixtures/{$fixture->id}/teams", [
            'home_team_id' => $newHome->id,
            'away_team_id' => $newAway->id,
        ])
        ->assertRedirect();

    $fixture->refresh();
    expect($fixture->home_team_id)->toBe($newHome->id)
        ->and($fixture->away_team_id)->toBe($newAway->id);
});

test('assigning the same team to both sides is rejected', function () {
    $admin = admin();
    $fixture = upcomingFixture();
    $team = uniqueTeam();

    $this->actingAs($admin)
        ->patch("/admin/fixtures/{$fixture->id}/teams", [
            'home_team_id' => $team->id,
            'away_team_id' => $team->id,
        ])
        ->assertSessionHasErrors('away_team_id');
});
