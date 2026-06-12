<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\Tournament;
use Inertia\Inertia;
use Inertia\Response;

class FixtureController extends Controller
{
    public function index(): Response
    {
        $tournament = Tournament::active()->first();

        $fixtures = Fixture::with(['homeTeam', 'awayTeam', 'stadium'])
            ->when($tournament, fn ($q) => $q->where('tournament_id', $tournament->id))
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy('round');

        return Inertia::render('Fixtures/Index', [
            'tournament' => $tournament,
            'fixturesByRound' => $fixtures,
        ]);
    }

    public function show(Fixture $fixture): Response
    {
        $fixture->load(['homeTeam', 'awayTeam', 'stadium', 'tournament']);

        $userPrediction = auth()->check()
            ? $fixture->predictions()->where('user_id', auth()->id())->first()
            : null;

        $predictionStats = [
            'home' => $fixture->predictions()->where('predicted_winner', 'home')->count(),
            'draw' => $fixture->predictions()->where('predicted_winner', 'draw')->count(),
            'away' => $fixture->predictions()->where('predicted_winner', 'away')->count(),
            'total' => $fixture->predictions()->count(),
        ];

        // Everyone's predictions are revealed only once predictions close (kick-off),
        // so picks can't be copied beforehand. Best scorers first.
        $predictions = $fixture->isPredictionLocked()
            ? $fixture->predictions()
                ->with('user:id,name,username,country_code')
                ->orderByDesc('points_earned')
                ->orderBy('id')
                ->get()
            : [];

        return Inertia::render('Fixtures/Show', [
            'fixture' => $fixture,
            'userPrediction' => $userPrediction,
            'predictionStats' => $predictionStats,
            'predictions' => $predictions,
            'isLocked' => $fixture->isPredictionLocked(),
            'pointsCalculated' => (bool) $fixture->points_calculated,
        ]);
    }
}
