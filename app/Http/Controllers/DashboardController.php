<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\LeaderboardService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly LeaderboardService $leaderboardService) {}

    public function index(): Response
    {
        $user = Auth::user();
        $tournament = Tournament::active()->first();

        $predictions = $user->predictions()
            ->with(['fixture.homeTeam', 'fixture.awayTeam'])
            ->when($tournament, fn ($q) => $q->whereHas('fixture', fn ($fq) => $fq->where('tournament_id', $tournament->id)))
            ->latest()
            ->limit(8)
            ->get();

        $calculated = $predictions->where('is_calculated', true);

        $stats = [
            'total_predictions' => $predictions->count(),
            'total_points' => $calculated->sum('points_earned'),
            'correct_winners' => $calculated->where('is_correct_winner', true)->count(),
            'exact_scores' => $calculated->where('is_exact_score', true)->count(),
            'global_rank' => $tournament ? $this->leaderboardService->getUserRank($user, $tournament) : null,
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentPredictions' => $predictions,
        ]);
    }
}
