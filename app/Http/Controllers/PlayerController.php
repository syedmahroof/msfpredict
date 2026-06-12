<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\User;
use App\Services\LeaderboardService;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function __construct(private readonly LeaderboardService $leaderboardService) {}

    public function show(User $user): Response
    {
        $tournament = Tournament::active()->first();

        // Only predictions for matches that have kicked off are shown (picks stay
        // private until then). Most recent first.
        $predictions = $user->predictions()
            ->when($tournament, fn ($q) => $q->whereHas('fixture', fn ($fq) => $fq->where('tournament_id', $tournament->id)))
            ->whereHas('fixture', fn ($fq) => $fq->where('scheduled_at', '<=', now()))
            ->with(['fixture.homeTeam', 'fixture.awayTeam'])
            ->get()
            ->sortByDesc(fn ($prediction) => $prediction->fixture->scheduled_at)
            ->values();

        $scored = $user->predictions()
            ->when($tournament, fn ($q) => $q->whereHas('fixture', fn ($fq) => $fq->where('tournament_id', $tournament->id)))
            ->where('is_calculated', true);

        $stats = [
            'total_points' => (int) (clone $scored)->sum('points_earned'),
            'scored_count' => (clone $scored)->count(),
            'exact_scores' => (clone $scored)->where('is_exact_score', true)->count(),
            'correct_winners' => (clone $scored)->where('is_correct_winner', true)->count(),
            'rank' => $tournament ? $this->leaderboardService->getUserRank($user, $tournament) : null,
        ];

        return Inertia::render('Players/Show', [
            'player' => $user->only(['id', 'name', 'username', 'country_code', 'level']),
            'stats' => $stats,
            'predictions' => $predictions,
        ]);
    }
}
