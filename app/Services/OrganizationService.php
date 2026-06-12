<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Support\Str;

class OrganizationService
{
    /**
     * @param  array{name: string, description?: string, is_public?: bool, requires_approval?: bool, max_members?: int}  $data
     */
    public function createOrganization(User $owner, array $data): Organization
    {
        $organization = Organization::create([
            'owner_id' => $owner->id,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::random(4),
            'description' => $data['description'] ?? null,
            'is_public' => $data['is_public'] ?? false,
            'requires_approval' => $data['requires_approval'] ?? false,
            'max_members' => $data['max_members'] ?? null,
            'invite_code' => $this->generateInviteCode(),
        ]);

        OrganizationMember::create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return $organization;
    }

    public function joinByInviteCode(User $user, string $inviteCode): OrganizationMember
    {
        $organization = Organization::where('invite_code', $inviteCode)->firstOrFail();

        $existingMember = OrganizationMember::where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingMember) {
            throw new \RuntimeException('You are already a member of this organization.');
        }

        if ($organization->max_members && $organization->organizationMembers()->where('status', 'active')->count() >= $organization->max_members) {
            throw new \RuntimeException('This organization has reached its maximum member limit.');
        }

        $status = $organization->requires_approval ? 'pending' : 'active';

        return OrganizationMember::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => $status,
            'joined_at' => $status === 'active' ? now() : null,
        ]);
    }

    public function updateMemberPoints(Organization $organization): void
    {
        $organization->organizationMembers()
            ->where('status', 'active')
            ->each(function (OrganizationMember $member) use ($organization) {
                $points = \App\Models\Prediction::whereHas(
                    'fixture',
                    fn ($q) => $q->whereHas('tournament')
                )
                    ->where('user_id', $member->user_id)
                    ->sum('points_earned');

                $member->update(['total_points' => $points]);
            });

        $rank = 1;
        $organization->organizationMembers()
            ->where('status', 'active')
            ->orderByDesc('total_points')
            ->each(function (OrganizationMember $member) use (&$rank) {
                $member->update(['rank' => $rank++]);
            });
    }

    public function regenerateInviteCode(Organization $organization): string
    {
        $inviteCode = $this->generateInviteCode();
        $organization->update(['invite_code' => $inviteCode]);

        return $inviteCode;
    }

    private function generateInviteCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Organization::where('invite_code', $code)->exists());

        return $code;
    }
}
