<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    private const CACHE_TTL = 300;

    public function getGlobalLeaderboard(Tournament $tournament, int $limit = 100): Collection
    {
        $cacheKey = "leaderboard:global:{$tournament->id}";

        return collect(Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tournament, $limit) {
            return $this->buildLeaderboard($tournament, $limit)->all();
        }));
    }

    public function getCountryLeaderboard(Tournament $tournament, string $countryCode, int $limit = 100): Collection
    {
        $cacheKey = "leaderboard:country:{$tournament->id}:{$countryCode}";

        return collect(Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tournament, $countryCode, $limit) {
            return $this->buildLeaderboard($tournament, $limit, $countryCode)->all();
        }));
    }

    public function getDailyLeaderboard(Tournament $tournament, string $date, int $limit = 50): Collection
    {
        $cacheKey = "leaderboard:daily:{$tournament->id}:{$date}";

        return collect(Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tournament, $date, $limit) {
            return DB::table('predictions')
                ->join('fixtures', 'predictions.fixture_id', '=', 'fixtures.id')
                ->join('users', 'predictions.user_id', '=', 'users.id')
                ->where('fixtures.tournament_id', $tournament->id)
                ->whereDate('fixtures.scheduled_at', $date)
                ->where('predictions.is_calculated', true)
                ->select(
                    'users.id', 'users.name', 'users.username', 'users.avatar', 'users.country_code',
                    DB::raw('SUM(predictions.points_earned) as total_points'),
                    DB::raw('COUNT(predictions.id) as prediction_count'),
                    DB::raw('SUM(predictions.is_correct_winner) as correct_predictions')
                )
                ->groupBy('users.id', 'users.name', 'users.username', 'users.avatar', 'users.country_code')
                ->orderByDesc('total_points')
                ->limit($limit)
                ->get()
                ->map(fn ($row, $index) => array_merge((array) $row, ['rank' => $index + 1]))
                ->all();
        }));
    }

    public function getUserRank(User $user, Tournament $tournament): int
    {
        $cacheKey = "leaderboard:rank:{$tournament->id}:{$user->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $tournament) {
            $userPoints = DB::table('predictions')
                ->join('fixtures', 'predictions.fixture_id', '=', 'fixtures.id')
                ->where('fixtures.tournament_id', $tournament->id)
                ->where('predictions.user_id', $user->id)
                ->sum('predictions.points_earned');

            return DB::table('predictions')
                ->join('fixtures', 'predictions.fixture_id', '=', 'fixtures.id')
                ->where('fixtures.tournament_id', $tournament->id)
                ->where('predictions.is_calculated', true)
                ->select('predictions.user_id', DB::raw('SUM(predictions.points_earned) as total'))
                ->groupBy('predictions.user_id')
                ->having('total', '>', $userPoints)
                ->count() + 1;
        });
    }

    public function invalidateCache(Tournament $tournament): void
    {
        Cache::forget("leaderboard:global:{$tournament->id}");
        Cache::forget("leaderboard:daily:{$tournament->id}:".now()->toDateString());
    }

    private function buildLeaderboard(Tournament $tournament, int $limit, ?string $countryCode = null): Collection
    {
        $query = DB::table('predictions')
            ->join('fixtures', 'predictions.fixture_id', '=', 'fixtures.id')
            ->join('users', 'predictions.user_id', '=', 'users.id')
            ->where('fixtures.tournament_id', $tournament->id)
            ->where('predictions.is_calculated', true)
            ->select(
                'users.id', 'users.name', 'users.username', 'users.avatar', 'users.country_code', 'users.level',
                DB::raw('SUM(predictions.points_earned) as total_points'),
                DB::raw('COUNT(predictions.id) as prediction_count'),
                DB::raw('SUM(predictions.is_correct_winner) as correct_predictions')
            )
            ->groupBy('users.id', 'users.name', 'users.username', 'users.avatar', 'users.country_code', 'users.level')
            ->orderByDesc('total_points')
            ->limit($limit);

        if ($countryCode) {
            $query->where('users.country_code', $countryCode);
        }

        return $query->get()->map(fn ($row, $index) => array_merge((array) $row, ['rank' => $index + 1]));
    }
}
