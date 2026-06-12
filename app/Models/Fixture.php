<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tournament_id', 'home_team_id', 'away_team_id', 'stadium_id',
    'round', 'group', 'match_day', 'scheduled_at', 'predictions_locked_at',
    'status', 'home_score', 'away_score', 'winner', 'points_calculated',
])]
class Fixture extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'predictions_locked_at' => 'datetime',
            'points_calculated' => 'boolean',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function stadium(): BelongsTo
    {
        return $this->belongsTo(Stadium::class);
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    /**
     * Predictions are open only within this many hours before kick-off.
     */
    public const PREDICTION_WINDOW_HOURS = 24;

    /**
     * The moment predictions open — PREDICTION_WINDOW_HOURS before kick-off.
     */
    public function predictionsOpenAt(): CarbonInterface
    {
        return $this->scheduled_at->subHours(self::PREDICTION_WINDOW_HOURS);
    }

    /**
     * The moment predictions close — kick-off, unless an explicit lock time is set.
     */
    public function predictionsCloseAt(): CarbonInterface
    {
        return $this->predictions_locked_at ?? $this->scheduled_at;
    }

    /**
     * True while the 24-hour prediction window is open (opens 24h before
     * kick-off, closes at kick-off).
     */
    public function isPredictionOpen(): bool
    {
        $now = now();

        return $now->greaterThanOrEqualTo($this->predictionsOpenAt())
            && $now->lessThan($this->predictionsCloseAt());
    }

    public function isPredictionLocked(): bool
    {
        return ! $this->isPredictionOpen();
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming')->where('scheduled_at', '>', now());
    }

    /**
     * Fixtures whose 24-hour prediction window is currently open.
     */
    public function scopeOpenForPredictions($query)
    {
        return $query->where('status', 'upcoming')
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<=', now()->addHours(self::PREDICTION_WINDOW_HOURS));
    }

    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    public function scopeFinished($query)
    {
        return $query->where('status', 'finished');
    }
}
