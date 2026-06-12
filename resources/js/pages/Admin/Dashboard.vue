<script setup lang="ts">
import { countryFlag as flagEmoji } from '@/lib/flag'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Link } from '@inertiajs/vue3'
import flagsData from '@/data/flags.json'

interface Fixture {
    id: number
    scheduled_at: string
    home_team: { name: string; country_code: string }
    away_team: { name: string; country_code: string }
    status: string
}

defineProps<{
    stats: {
        total_users: number
        total_predictions: number
        total_fixtures: number
        live_fixtures: number
        predictions_today: number
    }
    recentUsers: Array<{ id: number; name: string; email: string; country_code?: string; created_at: string }>
    upcomingFixtures: Fixture[]
}>()

function getFlagUrl(code: string): string {
    if (!code) return 'https://flagcdn.com/w320/un.png'
    const cleanCode = code.toUpperCase()
    const mapped = (flagsData as Record<string, { flag_url: string }>)[cleanCode]
    return mapped ? mapped.flag_url : 'https://flagcdn.com/w320/un.png'
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 p-6">
            <div>
                <h1 class="font-display text-3xl font-black uppercase tracking-wide text-foreground">Admin Dashboard</h1>
                <p class="text-sm text-muted-foreground mt-1">Platform overview and management</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <div
                    v-for="[label, value, accent] in [
                        ['Users', stats.total_users, false],
                        ['Predictions', stats.total_predictions, false],
                        ['Fixtures', stats.total_fixtures, false],
                        ['Live Now', stats.live_fixtures, true],
                        ['Today Picks', stats.predictions_today, false],
                    ]"
                    :key="label"
                    class="rounded-xl border border-border bg-card p-4"
                    :class="accent && (value as number) > 0 ? 'border-pitch/40 bg-pitch/5' : ''"
                >
                    <div class="font-display font-black text-2xl tabular-nums" :class="accent && (value as number) > 0 ? 'text-pitch' : 'text-foreground'">
                        {{ (value as number).toLocaleString() }}
                    </div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground mt-0.5">{{ label }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Quick Actions -->
                <div class="rounded-xl border border-border bg-card p-5">
                    <h2 class="font-display font-bold text-lg uppercase tracking-wide text-foreground mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <Link href="/admin/fixtures/create" class="flex h-12 items-center justify-center rounded-lg border border-border text-sm font-medium text-foreground hover:bg-muted transition-colors text-center">
                            + Add Fixture
                        </Link>
                        <Link href="/admin/fixtures" class="flex h-12 items-center justify-center rounded-lg border border-border text-sm font-medium text-foreground hover:bg-muted transition-colors text-center">
                            Manage Fixtures
                        </Link>
                        <Link href="/admin/users" class="flex h-12 items-center justify-center rounded-lg border border-border text-sm font-medium text-foreground hover:bg-muted transition-colors text-center">
                            Users
                        </Link>
                        <a href="/horizon" target="_blank" class="flex h-12 items-center justify-center rounded-lg bg-pitch/10 border border-pitch/30 text-sm font-medium text-pitch hover:bg-pitch/20 transition-colors text-center">
                            Horizon ↗
                        </a>
                    </div>
                </div>

                <!-- Upcoming Fixtures -->
                <div class="rounded-xl border border-border bg-card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-border px-5 py-4">
                        <h2 class="font-display font-bold text-lg uppercase tracking-wide text-foreground">Upcoming Fixtures</h2>
                        <Link href="/admin/fixtures" class="text-xs font-medium text-pitch hover:underline">All →</Link>
                    </div>
                    <div v-if="upcomingFixtures.length === 0" class="p-6 text-center text-sm text-muted-foreground">No upcoming fixtures.</div>
                    <div v-else class="divide-y divide-border">
                        <div v-for="fixture in upcomingFixtures.slice(0, 6)" :key="fixture.id" class="flex items-center gap-3 px-5 py-3 text-sm">
                            <span v-if="fixture.home_team.country_code === 'TBD'">🏳️</span>
                            <img v-else :src="getFlagUrl(fixture.home_team.country_code)" :alt="fixture.home_team.name" class="h-4 w-6 object-cover rounded shadow-xs border border-border inline-block" />
                            <span class="font-medium text-foreground min-w-0 flex-1 truncate">{{ fixture.home_team.name }} vs {{ fixture.away_team.name }}</span>
                            <span class="shrink-0 text-xs text-muted-foreground">{{ new Date(fixture.scheduled_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="rounded-xl border border-border bg-card overflow-hidden">
                <div class="border-b border-border px-5 py-4">
                    <h2 class="font-display font-bold text-lg uppercase tracking-wide text-foreground">Recent Registrations</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/20">
                                <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted-foreground">User</th>
                                <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Email</th>
                                <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Country</th>
                                <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="user in recentUsers" :key="user.id" class="hover:bg-muted/20 transition-colors">
                                <td class="px-5 py-3 font-medium text-foreground">{{ user.name }}</td>
                                <td class="px-5 py-3 text-muted-foreground">{{ user.email }}</td>
                                <td class="px-5 py-3 text-muted-foreground">{{ user.country_code ? flagEmoji(user.country_code) : '—' }}</td>
                                <td class="px-5 py-3 text-muted-foreground">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
