<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tournament_id', 'correct_winner_points', 'correct_draw_points',
    'exact_score_points', 'correct_goal_difference_points',
    'correct_one_team_score_points', 'knockout_multiplier',
])]
class ScoringRule extends Model
{
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
}
