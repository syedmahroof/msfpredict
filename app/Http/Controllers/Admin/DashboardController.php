<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'total_users' => User::count(),
            'total_predictions' => Prediction::count(),
            'total_fixtures' => Fixture::count(),
            'live_fixtures' => Fixture::where('status', 'live')->count(),
            'predictions_today' => Prediction::whereDate('created_at', today())->count(),
        ];

        $recentUsers = User::latest()->limit(10)->get(['id', 'name', 'email', 'country_code', 'created_at']);

        $upcomingFixtures = Fixture::with(['homeTeam', 'awayTeam'])
            ->upcoming()
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'upcomingFixtures' => $upcomingFixtures,
        ]);
    }
}
