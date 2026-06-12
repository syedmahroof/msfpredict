<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue'
import { countryFlag as flagEmoji } from '@/lib/flag'
import { Link, useForm } from '@inertiajs/vue3'

interface Team {
    id: number
    name: string
    country_code: string
}

interface Prediction {
    id: number
    predicted_home_score: number | null
    predicted_away_score: number | null
    points_earned: number
    is_exact_score: boolean
    is_correct_winner: boolean
    is_calculated: boolean
    fixture: {
        id: number
        scheduled_at: string
        status: string
        home_score: number | null
        away_score: number | null
        home_team: Team
        away_team: Team
    }
}

const props = defineProps<{
    player: { id: number; name: string; email: string; username?: string; country_code?: string; is_admin: boolean; created_at: string }
    stats: { total_points: number; predictions_count: number; scored_count: number; exact_scores: number; correct_winners: number }
    predictions: Prediction[]
}>()

function dateLabel(date: string): string {
    return new Date(date).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true })
}

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
})

function updatePassword() {
    passwordForm.patch(`/admin/users/${props.player.id}/password`, {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    })
}
</script>

<template>
    <AdminLayout>
        <div class="p-6">
            <Link href="/admin/users" class="mb-5 inline-flex items-center gap-1 text-sm font-medium text-muted-foreground hover:text-foreground">← Users</Link>

            <!-- Header -->
            <div class="flex flex-col gap-4 rounded-xl border border-border bg-card p-6 sm:flex-row sm:items-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-muted text-xl font-black text-foreground ring-1 ring-border">
                    {{ player.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="font-display text-2xl font-black uppercase tracking-wide text-foreground">{{ player.name }}</h1>
                        <span v-if="player.is_admin" class="rounded bg-pitch/15 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-pitch">Admin</span>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        <span v-if="player.country_code">{{ flagEmoji(player.country_code) }} </span>{{ player.email }}
                    </p>
                </div>
                <Link :href="`/players/${player.id}`" class="sm:ml-auto flex h-9 items-center rounded-lg border border-border px-4 text-xs font-medium text-foreground hover:bg-muted transition-colors">
                    Public profile ↗
                </Link>
            </div>

            <!-- Stats -->
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-5">
                <div v-for="stat in [
                    { label: 'Points', value: stats.total_points, accent: true },
                    { label: 'Predictions', value: stats.predictions_count },
                    { label: 'Scored', value: stats.scored_count },
                    { label: 'Exact', value: stats.exact_scores },
                    { label: 'Winners', value: stats.correct_winners },
                ]" :key="stat.label" class="rounded-xl border border-border bg-card p-4 text-center">
                    <div class="font-display text-2xl font-black tabular-nums" :class="stat.accent ? 'text-pitch' : 'text-foreground'">{{ stat.value }}</div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">{{ stat.label }}</div>
                </div>
            </div>

            <!-- Change password -->
            <div class="mt-6 rounded-xl border border-border bg-card overflow-hidden">
                <div class="border-b border-border px-5 py-4">
                    <h2 class="font-display text-lg font-bold uppercase tracking-wide text-foreground">Change Password</h2>
                    <p class="mt-0.5 text-xs text-muted-foreground">Set a new password for this user. They are not notified.</p>
                </div>
                <form @submit.prevent="updatePassword" class="space-y-5 p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-muted-foreground">New password</label>
                            <input v-model="passwordForm.password" type="password" autocomplete="new-password" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm text-foreground focus:border-pitch focus:outline-none" />
                            <p v-if="passwordForm.errors.password" class="mt-1 text-xs text-destructive">{{ passwordForm.errors.password }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-muted-foreground">Confirm password</label>
                            <input v-model="passwordForm.password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm text-foreground focus:border-pitch focus:outline-none" />
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="passwordForm.processing" class="flex h-10 items-center justify-center rounded-lg bg-pitch px-6 text-sm font-bold text-pitch-foreground hover:bg-pitch/90 disabled:opacity-50 transition-colors">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Predictions -->
            <div class="mt-6 rounded-xl border border-border bg-card overflow-hidden">
                <div class="border-b border-border px-5 py-4">
                    <h2 class="font-display text-lg font-bold uppercase tracking-wide text-foreground">All Predictions</h2>
                </div>
                <div v-if="predictions.length === 0" class="p-10 text-center text-sm text-muted-foreground">No predictions yet.</div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/20 text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                                <th class="px-5 py-3 text-left">Match</th>
                                <th class="px-5 py-3 text-left hidden sm:table-cell">Kick-off</th>
                                <th class="px-5 py-3 text-center">Pick</th>
                                <th class="px-5 py-3 text-center">Result</th>
                                <th class="px-5 py-3 text-right">Points</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="prediction in predictions" :key="prediction.id" class="hover:bg-muted/10 transition-colors">
                                <td class="px-5 py-3">
                                    <Link :href="`/admin/fixtures/${prediction.fixture.id}/predictions`" class="flex items-center gap-1.5 font-medium text-foreground hover:text-pitch">
                                        <span>{{ flagEmoji(prediction.fixture.home_team.country_code) }}</span>
                                        <span class="truncate">{{ prediction.fixture.home_team.name }}</span>
                                        <span class="text-xs text-muted-foreground/40">v</span>
                                        <span class="truncate">{{ prediction.fixture.away_team.name }}</span>
                                        <span>{{ flagEmoji(prediction.fixture.away_team.country_code) }}</span>
                                    </Link>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground hidden sm:table-cell text-xs">{{ dateLabel(prediction.fixture.scheduled_at) }}</td>
                                <td class="px-5 py-3 text-center font-display font-black tabular-nums text-foreground">{{ prediction.predicted_home_score }}–{{ prediction.predicted_away_score }}</td>
                                <td class="px-5 py-3 text-center tabular-nums">
                                    <span v-if="prediction.fixture.home_score !== null" class="font-semibold text-foreground">{{ prediction.fixture.home_score }}–{{ prediction.fixture.away_score }}</span>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <span v-if="prediction.is_calculated" class="font-display font-black tabular-nums" :class="prediction.points_earned > 0 ? 'text-pitch' : 'text-muted-foreground'">{{ prediction.points_earned }}</span>
                                    <span v-else class="text-xs text-muted-foreground">Pending</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
