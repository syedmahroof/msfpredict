<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function __construct(private readonly LeaderboardService $leaderboardService) {}

    public function index(Request $request): Response
    {
        $tab = $request->get('tab', 'global');

        $tournament = Tournament::active()->first();

        // No active tournament yet (e.g. before the database is seeded): render the
        // page with an empty leaderboard instead of a 404.
        if (! $tournament) {
            return Inertia::render('Leaderboard/Index', [
                'tournament' => null,
                'globalLeaderboard' => [],
                'dailyLeaderboard' => [],
                'userRank' => null,
                'activeTab' => $tab,
                'scoringRule' => null,
            ]);
        }

        $globalLeaderboard = Inertia::defer(fn () => $this->leaderboardService->getGlobalLeaderboard($tournament, 100));

        $dailyLeaderboard = Inertia::defer(fn () => $this->leaderboardService->getDailyLeaderboard($tournament, now()->toDateString(), 50));

        $userRank = auth()->check()
            ? $this->leaderboardService->getUserRank(auth()->user(), $tournament)
            : null;

        return Inertia::render('Leaderboard/Index', [
            'tournament' => $tournament,
            'globalLeaderboard' => $globalLeaderboard,
            'dailyLeaderboard' => $dailyLeaderboard,
            'userRank' => $userRank,
            'activeTab' => $tab,
        ]);
    }
}
