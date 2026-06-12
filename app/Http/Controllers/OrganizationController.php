<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function __construct(private readonly OrganizationService $organizationService) {}

    public function index(): Response
    {
        $myOrganizations = Auth::user()
            ->organizations()
            ->wherePivot('status', 'active')
            ->withCount('activeMembers')
            ->get();

        $publicOrganizations = Organization::public()
            ->withCount('activeMembers')
            ->latest()
            ->limit(12)
            ->get();

        return Inertia::render('Organizations/Index', [
            'myOrganizations' => $myOrganizations,
            'publicOrganizations' => $publicOrganizations,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Organizations/Create');
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $organization = $this->organizationService->createOrganization(Auth::user(), $request->validated());

        return redirect()->route('organizations.show', $organization)->with('success', 'Organization created!');
    }

    public function show(Organization $organization): Response
    {
        $organization->load(['owner']);

        $leaderboard = $organization->organizationMembers()
            ->where('status', 'active')
            ->with('user')
            ->orderByDesc('total_points')
            ->get();

        $isMember = Auth::check()
            ? $organization->organizationMembers()
                ->where('user_id', Auth::id())
                ->where('status', 'active')
                ->exists()
            : false;

        $memberRole = Auth::check()
            ? $organization->organizationMembers()
                ->where('user_id', Auth::id())
                ->value('role')
            : null;

        return Inertia::render('Organizations/Show', [
            'organization' => $organization,
            'leaderboard' => $leaderboard,
            'isMember' => $isMember,
            'memberRole' => $memberRole,
        ]);
    }

    public function join(Request $request): RedirectResponse
    {
        $request->validate(['invite_code' => ['required', 'string', 'size:8']]);

        try {
            $this->organizationService->joinByInviteCode(Auth::user(), $request->invite_code);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['invite_code' => $e->getMessage()]);
        }

        return back()->with('success', 'Joined organization!');
    }

    public function leave(Organization $organization): RedirectResponse
    {
        $organization->organizationMembers()
            ->where('user_id', Auth::id())
            ->where('role', '!=', 'owner')
            ->delete();

        return redirect()->route('organizations.index')->with('success', 'Left organization.');
    }
}
