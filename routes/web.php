<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FixtureController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Middleware\EnsureIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/fixtures', [FixtureController::class, 'index'])->name('fixtures.index');
Route::get('/fixtures/{fixture}', [FixtureController::class, 'show'])->name('fixtures.show');

Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

Route::get('/players/{user}', [PlayerController::class, 'show'])->name('players.show');

Route::inertia('/faq', 'Faq')->name('faq');

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('auth.social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('auth.social.callback');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/predict', [PredictionController::class, 'index'])->name('predictions.index');
    Route::post('/predict/{fixture}', [PredictionController::class, 'store'])->name('predictions.store');
    Route::post('/predict/bulk', [PredictionController::class, 'bulkStore'])->name('predictions.bulk-store');
    Route::get('/my-predictions', [PredictionController::class, 'myPredictions'])->name('predictions.my');
});

Route::middleware(['auth', 'verified', EnsureIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/fixtures', [Admin\FixtureController::class, 'index'])->name('fixtures.index');
    Route::get('/fixtures/create', [Admin\FixtureController::class, 'create'])->name('fixtures.create');
    Route::post('/fixtures', [Admin\FixtureController::class, 'store'])->name('fixtures.store');
    Route::get('/fixtures/{fixture}/edit', [Admin\FixtureController::class, 'edit'])->name('fixtures.edit');
    Route::get('/fixtures/{fixture}/predictions', [Admin\FixtureController::class, 'predictions'])->name('fixtures.predictions');
    Route::patch('/fixtures/{fixture}', [Admin\FixtureController::class, 'update'])->name('fixtures.update');
    Route::patch('/fixtures/{fixture}/result', [Admin\FixtureController::class, 'updateResult'])->name('fixtures.update-result');
    Route::patch('/fixtures/{fixture}/teams', [Admin\FixtureController::class, 'updateTeams'])->name('fixtures.update-teams');
    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [Admin\UserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}/password', [Admin\UserController::class, 'updatePassword'])->name('users.update-password');
    Route::get('/teams', [Admin\TeamController::class, 'index'])->name('teams.index');
    Route::get('/advertisements', [Admin\AdvertisementController::class, 'index'])->name('advertisements.index');
    Route::get('/advertisements/create', [Admin\AdvertisementController::class, 'create'])->name('advertisements.create');
    Route::post('/advertisements', [Admin\AdvertisementController::class, 'store'])->name('advertisements.store');
    Route::get('/advertisements/{advertisement}/edit', [Admin\AdvertisementController::class, 'edit'])->name('advertisements.edit');
    Route::patch('/advertisements/{advertisement}', [Admin\AdvertisementController::class, 'update'])->name('advertisements.update');
    Route::delete('/advertisements/{advertisement}', [Admin\AdvertisementController::class, 'destroy'])->name('advertisements.destroy');
});

require __DIR__.'/settings.php';
