<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'fixture_id', 'predicted_home_score', 'predicted_away_score',
    'predicted_winner', 'points_earned', 'is_exact_score', 'is_correct_winner',
    'is_correct_goal_difference', 'is_calculated', 'locked_at',
])]
class Prediction extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
            'is_exact_score' => 'boolean',
            'is_correct_winner' => 'boolean',
            'is_correct_goal_difference' => 'boolean',
            'is_calculated' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    public function scopeCalculated($query)
    {
        return $query->where('is_calculated', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_calculated', false);
    }
}
