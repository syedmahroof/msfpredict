<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue'
import { countryFlag as flagEmoji } from '@/lib/flag'
import { Link } from '@inertiajs/vue3'

interface Team {
    id: number
    name: string
    country_code: string
}

interface Prediction {
    id: number
    user: { id: number; name: string; username?: string; country_code?: string }
    predicted_home_score: number | null
    predicted_away_score: number | null
    predicted_winner: string | null
    points_earned: number
    is_exact_score: boolean
    is_correct_winner: boolean
    is_calculated: boolean
}

defineProps<{
    fixture: {
        id: number
        round: string
        group?: string | null
        scheduled_at: string
        status: string
        home_score: number | null
        away_score: number | null
        home_team: Team
        away_team: Team
        stadium?: { name: string; city: string } | null
    }
    predictions: Prediction[]
}>()

function kickoff(date: string): string {
    return new Date(date).toLocaleString('en-US', { weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true })
}
</script>

<template>
    <AdminLayout>
        <div class="p-6">
            <Link href="/admin/fixtures" class="mb-5 inline-flex items-center gap-1 text-sm font-medium text-muted-foreground hover:text-foreground">← Fixtures</Link>

            <!-- Match header -->
            <div class="rounded-xl border border-border bg-card p-6">
                <div class="mb-3 text-center text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">
                    {{ fixture.round.replace(/_/g, ' ') }}<span v-if="fixture.group"> · Group {{ fixture.group }}</span> · {{ kickoff(fixture.scheduled_at) }}
                </div>
                <div class="flex items-center justify-center gap-4">
                    <div class="flex flex-1 items-center justify-end gap-2 text-right">
                        <span class="font-bold text-foreground">{{ fixture.home_team.name }}</span>
                        <span class="text-2xl">{{ flagEmoji(fixture.home_team.country_code) }}</span>
                    </div>
                    <div class="shrink-0 px-2 text-center">
                        <div v-if="fixture.home_score !== null" class="font-display text-3xl font-black tabular-nums text-foreground">{{ fixture.home_score }} – {{ fixture.away_score }}</div>
                        <div v-else class="font-display text-2xl font-black tracking-widest text-muted-foreground/30">VS</div>
                        <div class="text-[10px] uppercase tracking-wider text-muted-foreground">{{ fixture.status }}</div>
                    </div>
                    <div class="flex flex-1 items-center gap-2">
                        <span class="text-2xl">{{ flagEmoji(fixture.away_team.country_code) }}</span>
                        <span class="font-bold text-foreground">{{ fixture.away_team.name }}</span>
                    </div>
                </div>
            </div>

            <!-- Predictions -->
            <div class="mt-6 rounded-xl border border-border bg-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-border px-5 py-4">
                    <h2 class="font-display text-lg font-bold uppercase tracking-wide text-foreground">Predictions</h2>
                    <span class="text-xs text-muted-foreground">{{ predictions.length }} total</span>
                </div>
                <div v-if="predictions.length === 0" class="p-10 text-center text-sm text-muted-foreground">No predictions for this match.</div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/20 text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                                <th class="px-5 py-3 text-left">Player</th>
                                <th class="px-5 py-3 text-center">Pick</th>
                                <th class="px-5 py-3 text-right">Points</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="prediction in predictions" :key="prediction.id" class="hover:bg-muted/10 transition-colors">
                                <td class="px-5 py-3">
                                    <Link :href="`/admin/users/${prediction.user.id}`" class="flex items-center gap-2 font-medium text-foreground hover:text-pitch">
                                        <span>{{ prediction.user.country_code ? flagEmoji(prediction.user.country_code) : '👤' }}</span>
                                        <span>{{ prediction.user.name }}</span>
                                    </Link>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="font-display font-black tabular-nums text-foreground">{{ prediction.predicted_home_score }}–{{ prediction.predicted_away_score }}</span>
                                    <span v-if="prediction.is_exact_score" class="ml-1 rounded bg-pitch/15 px-1.5 py-0.5 text-[9px] font-bold uppercase text-pitch">Exact</span>
                                    <span v-else-if="prediction.is_correct_winner" class="ml-1 rounded bg-muted px-1.5 py-0.5 text-[9px] font-bold uppercase text-muted-foreground">Winner</span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <span v-if="prediction.is_calculated" class="font-display font-black tabular-nums" :class="prediction.points_earned > 0 ? 'text-pitch' : 'text-muted-foreground'">{{ prediction.points_earned }}</span>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
