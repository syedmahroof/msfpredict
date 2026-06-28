<script setup lang="ts">
import WorldCupLayout from '@/layouts/WorldCupLayout.vue'
import { countryFlag as flagEmoji } from '@/lib/flag'
import { Link } from '@inertiajs/vue3'

interface Team {
    id: number
    name: string
    country_code: string
}

interface Prediction {
    id: number
    predicted_home_score: number | null
    predicted_away_score: number | null
    predicted_winner: string | null
    points_earned: number
    is_exact_score: boolean
    is_correct_winner: boolean
    is_calculated: boolean
    fixture: {
        id: number
        round: string
        scheduled_at: string
        status: string
        home_score: number | null
        away_score: number | null
        home_team: Team
        away_team: Team
    }
}

defineProps<{
    player: { id: number; name: string; username?: string; country_code?: string; level?: number }
    stats: {
        total_points: number
        scored_count: number
        exact_scores: number
        correct_winners: number
        rank: number | null
    }
    predictions: Prediction[]
}>()

function matchDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}
</script>

<template>
    <WorldCupLayout>
        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
            <Link href="/leaderboard" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-muted-foreground hover:text-foreground">
                ← Leaderboard
            </Link>

            <!-- Player header -->
            <div class="flex flex-col items-center gap-4 rounded-2xl border border-border bg-card p-6 sm:flex-row sm:items-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-muted text-2xl font-black text-foreground ring-2 ring-pitch/30">
                    {{ player.name.charAt(0).toUpperCase() }}
                </div>
                <div class="text-center sm:text-left">
                    <h1 class="font-display text-2xl font-black uppercase tracking-wide text-foreground">{{ player.name }}</h1>
                    <p class="text-sm text-muted-foreground">
                        <span v-if="player.country_code">{{ flagEmoji(player.country_code) }} </span>
                        <span v-if="player.username">@{{ player.username }}</span>
                    </p>
                </div>
                <div v-if="stats.rank" class="sm:ml-auto text-center">
                    <div class="font-display text-3xl font-black text-pitch">#{{ stats.rank }}</div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Rank</div>
                </div>
            </div>

            <!-- Stats -->
            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl border border-border bg-card p-4 text-center">
                    <div class="font-display text-2xl font-black tabular-nums text-foreground">{{ stats.total_points }}</div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Points</div>
                </div>
                <div class="rounded-xl border border-border bg-card p-4 text-center">
                    <div class="font-display text-2xl font-black tabular-nums text-foreground">{{ stats.scored_count }}</div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Scored</div>
                </div>
                <div class="rounded-xl border border-border bg-card p-4 text-center">
                    <div class="font-display text-2xl font-black tabular-nums text-pitch">{{ stats.exact_scores }}</div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Exact</div>
                </div>
                <div class="rounded-xl border border-border bg-card p-4 text-center">
                    <div class="font-display text-2xl font-black tabular-nums text-foreground">{{ stats.correct_winners }}</div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Winners</div>
                </div>
            </div>

            <!-- Predictions -->
            <div class="mt-6 rounded-2xl border border-border bg-card overflow-hidden">
                <div class="border-b border-border px-6 py-4">
                    <h2 class="font-display text-lg font-black uppercase tracking-wide text-foreground">Predictions</h2>
                </div>

                <div v-if="predictions.length === 0" class="p-10 text-center text-sm text-muted-foreground">
                    No predictions to show yet.
                </div>

                <div v-else class="divide-y divide-border">
                    <Link
                        v-for="prediction in predictions"
                        :key="prediction.id"
                        :href="`/fixtures/${prediction.fixture.id}`"
                        class="flex items-center gap-3 px-6 py-3.5 transition-colors hover:bg-muted/20"
                    >
                        <span class="hidden w-12 shrink-0 text-[11px] text-muted-foreground sm:block">{{ matchDate(prediction.fixture.scheduled_at) }}</span>

                        <!-- Teams -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 text-sm font-medium text-foreground">
                                <span>{{ flagEmoji(prediction.fixture.home_team.country_code) }}</span>
                                <span class="truncate">{{ prediction.fixture.home_team.name }}</span>
                                <span class="px-0.5 text-xs text-muted-foreground/40">v</span>
                                <span class="truncate">{{ prediction.fixture.away_team.name }}</span>
                                <span>{{ flagEmoji(prediction.fixture.away_team.country_code) }}</span>
                            </div>
                            <div class="mt-0.5 text-[11px] text-muted-foreground">
                                Pick <span class="font-semibold text-foreground tabular-nums">{{ prediction.predicted_home_score }}–{{ prediction.predicted_away_score }}</span>
                                <template v-if="prediction.fixture.round !== 'group_stage' && prediction.predicted_home_score === prediction.predicted_away_score && prediction.predicted_winner">
                                    <span class="text-[10px] font-normal text-muted-foreground ml-1">
                                        ({{ prediction.predicted_winner === 'home' ? prediction.fixture.home_team.name : prediction.fixture.away_team.name }} on penalties)
                                    </span>
                                </template>
                                <template v-if="prediction.fixture.home_score !== null">
                                    · Result <span class="font-semibold text-foreground tabular-nums">{{ prediction.fixture.home_score }}–{{ prediction.fixture.away_score }}</span>
                                </template>
                            </div>
                        </div>

                        <!-- Points -->
                        <div class="shrink-0 text-right">
                            <template v-if="prediction.is_calculated">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-sm font-bold tabular-nums"
                                    :class="prediction.points_earned > 0 ? 'bg-pitch/15 text-pitch' : 'bg-muted text-muted-foreground'"
                                >
                                    {{ prediction.points_earned > 0 ? '+' : '' }}{{ prediction.points_earned }}
                                </span>
                                <div v-if="prediction.is_exact_score" class="mt-0.5 text-[10px] font-bold text-pitch">Exact!</div>
                            </template>
                            <span v-else class="text-xs text-muted-foreground">Pending</span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </WorldCupLayout>
</template>
