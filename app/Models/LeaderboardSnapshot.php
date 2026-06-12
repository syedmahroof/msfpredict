<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'tournament_id', 'period', 'period_date',
    'total_points', 'prediction_count', 'correct_predictions', 'rank', 'country_code',
])]
class LeaderboardSnapshot extends Model
{
    protected function casts(): array
    {
        return [
            'period_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function getAccuracyAttribute(): float
    {
        if ($this->prediction_count === 0) {
            return 0.0;
        }

        return round(($this->correct_predictions / $this->prediction_count) * 100, 1);
    }
}
