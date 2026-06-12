<?php

namespace App\Jobs;

use App\LeaderboardPeriod;
use App\Models\LeaderboardSnapshot;
use App\Models\Tournament;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class UpdateLeaderboardSnapshot implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(public readonly int $tournamentId) {}

    public function handle(): void
    {
        $tournament = Tournament::findOrFail($this->tournamentId);

        // Canonical period_date (midnight) as a plain datetime string so it matches
        // existing rows and the unique index consistently.
        $periodDate = now()->startOfDay()->format('Y-m-d H:i:s');
        $timestamp = now();

        $entries = DB::table('predictions')
            ->join('fixtures', 'predictions.fixture_id', '=', 'fixtures.id')
            ->join('users', 'predictions.user_id', '=', 'users.id')
            ->where('fixtures.tournament_id', $tournament->id)
            ->where('predictions.is_calculated', true)
            ->select(
                'users.id as user_id',
                'users.country_code',
                DB::raw('SUM(predictions.points_earned) as total_points'),
                DB::raw('COUNT(predictions.id) as prediction_count'),
                DB::raw('SUM(predictions.is_correct_winner) as correct_predictions')
            )
            ->groupBy('users.id', 'users.country_code')
            ->get();

        $rows = $entries->map(fn ($entry) => [
            'user_id' => $entry->user_id,
            'tournament_id' => $tournament->id,
            'period' => LeaderboardPeriod::Global->value,
            'period_date' => $periodDate,
            'total_points' => $entry->total_points,
            'prediction_count' => $entry->prediction_count,
            'correct_predictions' => $entry->correct_predictions,
            'country_code' => $entry->country_code,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->all();

        if ($rows !== []) {
            // Atomic INSERT ... ON CONFLICT DO UPDATE — idempotent, so re-running
            // (e.g. after a corrected result) updates rows instead of failing on the
            // unique constraint.
            LeaderboardSnapshot::upsert(
                $rows,
                ['user_id', 'tournament_id', 'period', 'period_date'],
                ['total_points', 'prediction_count', 'correct_predictions', 'country_code', 'updated_at']
            );
        }

        $this->updateRanks($tournament->id, LeaderboardPeriod::Global->value, $periodDate);
    }

    private function updateRanks(int $tournamentId, string $period, string $date): void
    {
        $snapshots = LeaderboardSnapshot::where('tournament_id', $tournamentId)
            ->where('period', $period)
            ->where('period_date', $date)
            ->orderByDesc('total_points')
            ->pluck('id');

        foreach ($snapshots as $rank => $id) {
            LeaderboardSnapshot::where('id', $id)->update(['rank' => $rank + 1]);
        }
    }
}
