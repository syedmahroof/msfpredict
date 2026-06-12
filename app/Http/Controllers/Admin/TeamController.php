<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(): Response
    {
        $teams = Team::orderBy('name')->get();

        return Inertia::render('Admin/Teams/Index', [
            'teams' => $teams,
        ]);
    }
}
