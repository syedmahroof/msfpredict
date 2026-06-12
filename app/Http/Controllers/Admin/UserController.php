<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::withCount('predictions')
            ->orderByDesc('created_at')
            ->paginate(30);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function show(User $user): Response
    {
        // Admin sees all of the user's predictions, including upcoming ones.
        $predictions = $user->predictions()
            ->with(['fixture.homeTeam', 'fixture.awayTeam'])
            ->get()
            ->sortByDesc(fn ($prediction) => $prediction->fixture?->scheduled_at)
            ->values();

        $scored = $user->predictions()->where('is_calculated', true);

        $stats = [
            'total_points' => (int) (clone $scored)->sum('points_earned'),
            'predictions_count' => $user->predictions()->count(),
            'scored_count' => (clone $scored)->count(),
            'exact_scores' => (clone $scored)->where('is_exact_score', true)->count(),
            'correct_winners' => (clone $scored)->where('is_correct_winner', true)->count(),
        ];

        return Inertia::render('Admin/Users/Show', [
            'player' => $user->only(['id', 'name', 'email', 'username', 'country_code', 'is_admin', 'created_at']),
            'stats' => $stats,
            'predictions' => $predictions,
        ]);
    }

    public function updatePassword(UpdateUserPasswordRequest $request, User $user): RedirectResponse
    {
        $user->update([
            'password' => $request->validated('password'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated for :name.', ['name' => $user->name])]);

        return back();
    }
}
