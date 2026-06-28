<?php

namespace App\Services;

use App\FixtureRound;
use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\ScoringRule;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PredictionService
{
    /**
     * @param  array{predicted_home_score?: int, predicted_away_score?: int, predicted_winner?: string}  $data
     */
    public function savePrediction(User $user, Fixture $fixture, array $data): Prediction
    {
        if (! $fixture->isPredictionOpen()) {
            throw new \RuntimeException(
                now()->isBefore($fixture->predictionsOpenAt())
                    ? 'Predictions open 24 hours before kick-off.'
                    : 'Predictions are locked for this fixture.'
            );
        }

        return DB::transaction(function () use ($user, $fixture, $data) {
            $prediction = Prediction::updateOrCreate(
                ['user_id' => $user->id, 'fixture_id' => $fixture->id],
                [
                    'predicted_home_score' => $data['predicted_home_score'] ?? null,
                    'predicted_away_score' => $data['predicted_away_score'] ?? null,
                    'predicted_winner' => $data['predicted_winner'] ?? null,
                    'locked_at' => null,
                ]
            );

            $user->update(['last_prediction_at' => now()]);

            return $prediction;
        });
    }

    public function calculatePointsForFixture(Fixture $fixture): void
    {
        if ($fixture->home_score === null || $fixture->away_score === null) {
            return;
        }

        $scoringRule = ScoringRule::where('tournament_id', $fixture->tournament_id)->first();

        if (! $scoringRule) {
            $scoringRule = new ScoringRule([
                'correct_winner_points' => 3,
                'correct_draw_points' => 5,
                'exact_score_points' => 10,
                'correct_goal_difference_points' => 5,
                'correct_one_team_score_points' => 2,
                'knockout_multiplier' => 1,
            ]);
        }

        $actualWinner = $fixture->winner ?: $this->determineWinner($fixture->home_score, $fixture->away_score);
        $isKnockout = FixtureRound::from($fixture->round)->isKnockout();
        $multiplier = $isKnockout ? $scoringRule->knockout_multiplier : 1;

        // Re-score every prediction (not just uncalculated ones) so a corrected
        // result recalculates points idempotently — each prediction's points are
        // overwritten with the value for the current result.
        $fixture->predictions()
            ->chunkById(200, function ($predictions) use ($fixture, $scoringRule, $actualWinner, $multiplier) {
                foreach ($predictions as $prediction) {
                    $points = $this->computePoints($prediction, $fixture, $scoringRule, $actualWinner, $multiplier);

                    $prediction->update([
                        'points_earned' => $points['total'],
                        'is_exact_score' => $points['is_exact_score'],
                        'is_correct_winner' => $points['is_correct_winner'],
                        'is_correct_goal_difference' => $points['is_correct_goal_difference'],
                        'is_calculated' => true,
                    ]);
                }
            });

        $fixture->update(['points_calculated' => true]);
    }

    /**
     * @return array{total: int, is_exact_score: bool, is_correct_winner: bool, is_correct_goal_difference: bool}
     */
    private function computePoints(
        Prediction $prediction,
        Fixture $fixture,
        ScoringRule $rule,
        string $actualWinner,
        int $multiplier
    ): array {
        if ($prediction->predicted_home_score === null || $prediction->predicted_away_score === null) {
            return ['total' => 0, 'is_exact_score' => false, 'is_correct_winner' => false, 'is_correct_goal_difference' => false];
        }

        $isKnockout = FixtureRound::from($fixture->round)->isKnockout();

        // Determine Predicted Winner
        $predictedWinner = $this->determineWinner($prediction->predicted_home_score, $prediction->predicted_away_score);
        if ($isKnockout && $predictedWinner === 'draw') {
            $predictedWinner = $prediction->predicted_winner;
        }

        // Match Logic
        $scoresMatchExactly = $prediction->predicted_home_score === $fixture->home_score
            && $prediction->predicted_away_score === $fixture->away_score;
        $winnerMatches = $predictedWinner === $actualWinner;

        $points = 0;
        $isExactScore = false;
        $isCorrectWinner = false;
        $isCorrectGoalDiff = false;

        if ($scoresMatchExactly && $winnerMatches) {
            // Exact Score (e.g. 10 pts * 2)
            $isExactScore = true;
            $isCorrectWinner = true;
            $points = $rule->exact_score_points * $multiplier;
        } elseif ($winnerMatches) {
            // Correct Winner (e.g. 5 pts * 2)
            $isCorrectWinner = true;
            if ($actualWinner === 'draw') {
                $points = $rule->correct_draw_points * $multiplier;
            } else {
                $points = $rule->correct_winner_points;
                $actualDiff = abs($fixture->home_score - $fixture->away_score);
                $predictedDiff = abs($prediction->predicted_home_score - $prediction->predicted_away_score);
                if ($actualDiff === $predictedDiff) {
                    $isCorrectGoalDiff = true;
                    $points += $rule->correct_goal_difference_points;
                }
                $points *= $multiplier;
            }
        } elseif ($prediction->predicted_home_score === $prediction->predicted_away_score && $fixture->home_score === $fixture->away_score) {
            // Score matches but wrong team advanced: Award Correct Draw points (e.g. 3 pts * 2)
            $points = $rule->correct_draw_points * $multiplier;
        } elseif (
            $prediction->predicted_home_score === $fixture->home_score ||
            $prediction->predicted_away_score === $fixture->away_score
        ) {
            // One team score match (e.g. 1 pt * 2)
            $points = $rule->correct_one_team_score_points * $multiplier;
        }

        return [
            'total' => $points,
            'is_exact_score' => $isExactScore,
            'is_correct_winner' => $isCorrectWinner,
            'is_correct_goal_difference' => $isCorrectGoalDiff,
        ];
    }

    private function determineWinner(int $homeScore, int $awayScore): string
    {
        if ($homeScore > $awayScore) {
            return 'home';
        }

        if ($awayScore > $homeScore) {
            return 'away';
        }

        return 'draw';
    }
}
