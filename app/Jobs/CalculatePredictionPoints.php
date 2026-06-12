<?php

namespace App\Jobs;

use App\Events\LeaderboardUpdated;
use App\Models\Fixture;
use App\Services\LeaderboardService;
use App\Services\PredictionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CalculatePredictionPoints implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public readonly int $fixtureId) {}

    public function handle(PredictionService $predictionService, LeaderboardService $leaderboardService): void
    {
        $fixture = Fixture::with('tournament')->findOrFail($this->fixtureId);

        $predictionService->calculatePointsForFixture($fixture);

        // Clear the cached leaderboard so the new points show up immediately.
        $leaderboardService->invalidateCache($fixture->tournament);

        LeaderboardUpdated::dispatch($fixture->tournament);

        UpdateLeaderboardSnapshot::dispatch($fixture->tournament_id);
    }
}
