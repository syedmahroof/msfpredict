<?php

namespace App\Jobs;

use App\Events\PredictionLocked;
use App\Models\Fixture;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class LockFixturePredictions implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $fixtureId) {}

    public function handle(): void
    {
        $fixture = Fixture::find($this->fixtureId);

        if (! $fixture || $fixture->status === 'locked') {
            return;
        }

        $fixture->update(['status' => 'locked']);

        $fixture->predictions()->whereNull('locked_at')->update(['locked_at' => now()]);

        PredictionLocked::dispatch($fixture);
    }
}
