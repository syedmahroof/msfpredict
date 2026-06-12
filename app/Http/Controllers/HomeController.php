<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\Fixture;
use App\Models\Tournament;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $tournament = Tournament::active()->first();

        $liveFixtures = Fixture::with(['homeTeam', 'awayTeam', 'stadium'])
            ->live()
            ->orderBy('scheduled_at')
            ->get();

        $upcomingFixtures = Fixture::with(['homeTeam', 'awayTeam', 'stadium'])
            ->upcoming()
            ->orderBy('scheduled_at')
            ->limit(6)
            ->get();

        return Inertia::render('Home', [
            'tournament' => $tournament,
            'liveFixtures' => $liveFixtures,
            'upcomingFixtures' => $upcomingFixtures,
            'scoringRule' => $tournament?->scoringRule,
            'advertisement' => $this->homeAdvertisement(),
        ]);
    }

    /**
     * Resolve the active home page advertisement banner, if any.
     *
     * @return array{image_url: string, link_url: ?string, alt: string}|null
     */
    private function homeAdvertisement(): ?array
    {
        $advertisement = Advertisement::forPlacement('home_hero')->first();

        if ($advertisement === null) {
            return null;
        }

        return [
            'image_url' => $advertisement->image_url,
            'link_url' => $advertisement->link_url,
            'alt' => $advertisement->title,
        ];
    }
}
