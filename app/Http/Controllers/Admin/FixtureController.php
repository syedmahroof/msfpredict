<?php

namespace App\Http\Controllers\Admin;

use App\Events\FixtureResultUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFixtureRequest;
use App\Jobs\CalculatePredictionPoints;
use App\Jobs\LockFixturePredictions;
use App\Models\Fixture;
use App\Models\Stadium;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FixtureController extends Controller
{
    public function index(): Response
    {
        $fixtures = Fixture::with(['homeTeam', 'awayTeam', 'stadium', 'tournament'])
            ->orderBy('scheduled_at')
            ->paginate(30);

        return Inertia::render('Admin/Fixtures/Index', [
            'fixtures' => $fixtures,
            'teams' => Team::orderBy('name')->get(['id', 'name', 'country_code']),
        ]);
    }

    public function predictions(Fixture $fixture): Response
    {
        $fixture->load(['homeTeam', 'awayTeam', 'stadium']);

        $predictions = $fixture->predictions()
            ->with('user:id,name,username,country_code')
            ->orderByDesc('points_earned')
            ->orderBy('id')
            ->get();

        return Inertia::render('Admin/Fixtures/Predictions', [
            'fixture' => $fixture,
            'predictions' => $predictions,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Fixtures/Create', [
            'tournaments' => Tournament::all(['id', 'name']),
            'teams' => Team::orderBy('name')->get(['id', 'name', 'country_code']),
            'stadiums' => Stadium::orderBy('name')->get(['id', 'name', 'city']),
            'rounds' => ['group_stage', 'round_of_32', 'round_of_16', 'quarter_final', 'semi_final', 'third_place', 'final'],
        ]);
    }

    public function edit(Fixture $fixture): Response
    {
        return Inertia::render('Admin/Fixtures/Edit', [
            'fixture' => [
                'id' => $fixture->id,
                'tournament_id' => $fixture->tournament_id,
                'home_team_id' => $fixture->home_team_id,
                'away_team_id' => $fixture->away_team_id,
                'stadium_id' => $fixture->stadium_id,
                'round' => $fixture->round,
                'group' => $fixture->group,
                'match_day' => $fixture->match_day,
                'scheduled_at' => $fixture->scheduled_at->format('Y-m-d\TH:i'),
            ],
            'tournaments' => Tournament::all(['id', 'name']),
            'teams' => Team::orderBy('name')->get(['id', 'name', 'country_code']),
            'stadiums' => Stadium::orderBy('name')->get(['id', 'name', 'city']),
            'rounds' => ['group_stage', 'round_of_32', 'round_of_16', 'quarter_final', 'semi_final', 'third_place', 'final'],
        ]);
    }

    public function update(StoreFixtureRequest $request, Fixture $fixture): RedirectResponse
    {
        // Editing the kick-off also moves the prediction close time (kick-off).
        $fixture->update([
            ...$request->validated(),
            'predictions_locked_at' => $request->date('scheduled_at'),
        ]);

        return redirect()->route('admin.fixtures.index')->with('success', 'Fixture updated!');
    }

    /**
     * Assign the two teams for a fixture — used to fill in knockout (TBD) slots
     * once group results are known.
     */
    public function updateTeams(Request $request, Fixture $fixture): RedirectResponse
    {
        $validated = $request->validate([
            'home_team_id' => ['required', 'exists:teams,id'],
            'away_team_id' => ['required', 'exists:teams,id', 'different:home_team_id'],
        ]);

        $fixture->update($validated);

        return back()->with('success', 'Teams updated!');
    }

    public function store(StoreFixtureRequest $request): RedirectResponse
    {
        // Predictions close at kick-off (they open 24h before).
        $kickoff = $request->date('scheduled_at');

        $fixture = Fixture::create([
            ...$request->validated(),
            'predictions_locked_at' => $kickoff,
        ]);

        LockFixturePredictions::dispatch($fixture->id)
            ->delay($kickoff);

        return redirect()->route('admin.fixtures.index')->with('success', 'Fixture created!');
    }

    public function updateResult(Request $request, Fixture $fixture): RedirectResponse
    {
        $validated = $request->validate([
            'home_score' => ['required', 'integer', 'min:0', 'max:20'],
            'away_score' => ['required', 'integer', 'min:0', 'max:20'],
            'status' => ['required', 'in:live,finished'],
            'winner' => ['nullable', 'in:home,away,draw'],
        ]);

        $fixture->update([
            'home_score' => $validated['home_score'],
            'away_score' => $validated['away_score'],
            'status' => $validated['status'],
            // Use the explicitly chosen winner (e.g. a knockout decided on penalties);
            // otherwise infer it from the score.
            'winner' => $validated['winner'] ?? $this->determineWinner($validated['home_score'], $validated['away_score']),
        ]);

        FixtureResultUpdated::dispatch($fixture);

        if ($request->status === 'finished') {
            // Queued — a worker (Horizon) (re)scores the predictions and refreshes the
            // leaderboard. Dispatched every time the result is saved so a corrected
            // score recalculates points.
            CalculatePredictionPoints::dispatch($fixture->id);
        }

        return back()->with('success', 'Result updated!');
    }

    private function determineWinner(int $home, int $away): string
    {
        if ($home > $away) {
            return 'home';
        }

        if ($away > $home) {
            return 'away';
        }

        return 'draw';
    }
}
