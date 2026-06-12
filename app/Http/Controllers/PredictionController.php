<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePredictionRequest;
use App\Models\Fixture;
use App\Models\Tournament;
use App\Services\PredictionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PredictionController extends Controller
{
    public function __construct(private readonly PredictionService $predictionService) {}

    public function index(): Response
    {
        $tournament = Tournament::active()->first();

        // Matches whose 24-hour prediction window is open (kick-off within the next
        // 24 hours and not yet started), plus any currently-live match so users can
        // follow it — live matches are no longer predictable.
        $fixtures = Fixture::with(['homeTeam', 'awayTeam', 'predictions' => function ($q) {
            $q->where('user_id', Auth::id());
        }])
            ->when($tournament, fn ($q) => $q->where('tournament_id', $tournament->id))
            ->where(function ($q) {
                $q->openForPredictions()->orWhere('status', 'live');
            })
            ->orderBy('scheduled_at')
            ->paginate(20);

        return Inertia::render('Predictions/Index', [
            'tournament' => $tournament,
            'fixtures' => $fixtures,
            'scoringRule' => $tournament?->scoringRule,
        ]);
    }

    public function store(StorePredictionRequest $request, Fixture $fixture): RedirectResponse
    {
        try {
            $this->predictionService->savePrediction(Auth::user(), $fixture, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['prediction' => $e->getMessage()]);
        }

        return back()->with('success', 'Prediction saved!');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'predictions' => ['required', 'array', 'max:50'],
            'predictions.*.fixture_id' => ['required', 'integer', 'exists:fixtures,id'],
            'predictions.*.predicted_home_score' => ['required', 'integer', 'min:0', 'max:20'],
            'predictions.*.predicted_away_score' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        $saved = 0;
        $errors = [];

        foreach ($validated['predictions'] as $item) {
            try {
                $fixture = Fixture::find($item['fixture_id']);
                $this->predictionService->savePrediction(Auth::user(), $fixture, $item);
                $saved++;
            } catch (\RuntimeException $e) {
                $errors[] = "Fixture #{$item['fixture_id']}: {$e->getMessage()}";
            }
        }

        return back()->with([
            'success' => "{$saved} predictions saved.",
            'prediction_errors' => $errors,
        ]);
    }

    public function myPredictions(): Response
    {
        $tournament = Tournament::active()->first();

        $predictions = Auth::user()
            ->predictions()
            ->with(['fixture.homeTeam', 'fixture.awayTeam'])
            ->when($tournament, fn ($q) => $q->whereHas('fixture', fn ($fq) => $fq->where('tournament_id', $tournament->id)))
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Predictions/My', [
            'tournament' => $tournament,
            'predictions' => $predictions,
        ]);
    }
}
