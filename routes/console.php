<?php

use App\Jobs\LockFixturePredictions;
use App\Jobs\UpdateLeaderboardSnapshot;
use App\Models\Fixture;
use App\Models\Tournament;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $fixturesToLock = Fixture::where('status', 'upcoming')
        ->where('scheduled_at', '<=', now()->addMinutes(5))
        ->whereNull('predictions_locked_at')
        ->get();

    foreach ($fixturesToLock as $fixture) {
        LockFixturePredictions::dispatch($fixture->id);
    }
})->everyMinute()->name('lock-upcoming-fixtures');

Schedule::call(function () {
    $tournament = Tournament::active()->first();

    if ($tournament) {
        UpdateLeaderboardSnapshot::dispatch($tournament->id);
    }
})->everySixHours()->name('update-leaderboard-snapshots');
