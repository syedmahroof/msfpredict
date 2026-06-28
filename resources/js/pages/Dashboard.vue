<script setup lang="ts">
import { countryFlag as flagEmoji } from '@/lib/flag'
import WorldCupLayout from '@/layouts/WorldCupLayout.vue'
import { Link, usePage } from '@inertiajs/vue3'
import flagsData from '@/data/flags.json'

const page = usePage()
const auth = page.props.auth as { user: { name: string; country_code?: string; level: number; xp_points: number; prediction_streak: number } }

interface RecentPrediction {
    id: number
    points_earned: number
    is_calculated: boolean
    is_exact_score: boolean
    is_correct_winner: boolean
    predicted_home_score: number
    predicted_away_score: number
    predicted_winner?: string | null
    fixture: {
        round: string
        scheduled_at: string
        home_score: number | null
        away_score: number | null
        status: string
        home_team: { name: string; country_code: string }
        away_team: { name: string; country_code: string }
    }
}

defineProps<{
    stats: {
        total_predictions: number
        total_points: number
        correct_winners: number
        exact_scores: number
        global_rank: number | null
    }
    recentPredictions: RecentPrediction[]
}>()

function getFlagUrl(code: string): string {
    if (!code) return 'https://flagcdn.com/w320/un.png'
    const cleanCode = code.toUpperCase()
    const mapped = (flagsData as Record<string, { flag_url: string }>)[cleanCode]
    return mapped ? mapped.flag_url : 'https://flagcdn.com/w320/un.png'
}

function accuracy(correct: number, total: number): string {
    if (!total) return '0%'
    return Math.round((correct / total) * 100) + '%'
}
</script>

<template>
    <WorldCupLayout>
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            <!-- Welcome header -->
            <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-display text-3xl font-black uppercase tracking-wide text-foreground">
                        Welcome back, {{ auth.user.name.split(' ')[0] }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">Here's how you're doing this tournament.</p>
                </div>
                <Link
                    href="/predict"
                    class="inline-flex h-10 items-center justify-center rounded-xl bg-pitch px-6 text-sm font-bold text-pitch-foreground hover:bg-pitch/90 transition-colors"
                >
                    Make Predictions
                </Link>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5 mb-8">
                <div class="rounded-xl border border-border bg-card p-4">
                    <div class="font-display font-black text-3xl tabular-nums text-foreground">{{ stats.total_points }}</div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground mt-0.5">Total Points</div>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <div class="font-display font-black text-3xl tabular-nums text-foreground">{{ stats.total_predictions }}</div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground mt-0.5">Predictions</div>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <div class="font-display font-black text-3xl tabular-nums text-pitch">{{ stats.exact_scores }}</div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground mt-0.5">Exact Scores</div>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <div class="font-display font-black text-3xl tabular-nums text-foreground">
                        {{ accuracy(stats.correct_winners, stats.total_predictions) }}
                    </div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground mt-0.5">Accuracy</div>
                </div>
                <div class="rounded-xl border border-pitch/30 bg-pitch/5 p-4 col-span-2 sm:col-span-1">
                    <div class="font-display font-black text-3xl tabular-nums text-foreground">
                        {{ stats.global_rank ? '#' + stats.global_rank : '—' }}
                    </div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground mt-0.5">Global Rank</div>
                </div>
            </div>

            <!-- Recent Predictions -->
            <div class="rounded-xl border border-border bg-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-border px-5 py-4">
                    <h2 class="font-display font-bold text-lg uppercase tracking-wide text-foreground">Recent Predictions</h2>
                    <Link href="/my-predictions" class="text-xs font-medium text-pitch hover:underline">View all →</Link>
                </div>

                <div v-if="recentPredictions.length === 0" class="flex flex-col items-center justify-center py-14 text-center">
                    <div class="text-4xl mb-3">⚽</div>
                    <p class="text-sm text-muted-foreground">No predictions yet.</p>
                    <Link href="/predict" class="mt-3 text-sm font-semibold text-pitch hover:underline">Start predicting →</Link>
                </div>

                <div v-else class="divide-y divide-border">
                    <div
                        v-for="prediction in recentPredictions"
                        :key="prediction.id"
                        class="flex items-center gap-4 px-5 py-3"
                    >
                        <!-- Teams -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 text-sm font-medium text-foreground">
                                <span v-if="prediction.fixture.home_team.country_code === 'TBD'">🏳️</span>
                                <img v-else :src="getFlagUrl(prediction.fixture.home_team.country_code)" :alt="prediction.fixture.home_team.name" class="h-4 w-6 object-cover rounded shadow-xs border border-border inline-block" />
                                <span class="truncate">{{ prediction.fixture.home_team.name }}</span>
                                <span class="text-muted-foreground/40 text-xs px-0.5">vs</span>
                                <span class="truncate">{{ prediction.fixture.away_team.name }}</span>
                                <span v-if="prediction.fixture.away_team.country_code === 'TBD'">🏳️</span>
                                <img v-else :src="getFlagUrl(prediction.fixture.away_team.country_code)" :alt="prediction.fixture.away_team.name" class="h-4 w-6 object-cover rounded shadow-xs border border-border inline-block" />
                            </div>
                            <div class="text-xs text-muted-foreground mt-0.5">
                                Your pick: <span class="font-semibold text-foreground tabular-nums">{{ prediction.predicted_home_score }} – {{ prediction.predicted_away_score }}</span>
                                <template v-if="prediction.fixture.round !== 'group_stage' && prediction.predicted_home_score === prediction.predicted_away_score && prediction.predicted_winner">
                                    <span class="text-[10px] font-normal text-muted-foreground ml-1">
                                        ({{ prediction.predicted_winner === 'home' ? prediction.fixture.home_team.name : prediction.fixture.away_team.name }} on penalties)
                                    </span>
                                </template>
                                <template v-if="prediction.fixture.home_score !== null">
                                    · Result: <span class="font-semibold text-foreground tabular-nums">{{ prediction.fixture.home_score }} – {{ prediction.fixture.away_score }}</span>
                                </template>
                            </div>
                        </div>

                        <!-- Points badge -->
                        <div class="shrink-0 text-right">
                            <template v-if="prediction.is_calculated">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-sm font-bold tabular-nums"
                                    :class="prediction.points_earned > 0 ? 'bg-pitch/15 text-pitch' : 'bg-muted text-muted-foreground'"
                                >
                                    {{ prediction.points_earned > 0 ? '+' : '' }}{{ prediction.points_earned }}pts
                                </span>
                                <div v-if="prediction.is_exact_score" class="text-[10px] text-pitch font-bold mt-0.5">Exact!</div>
                            </template>
                            <span v-else class="text-xs text-muted-foreground">Pending</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick links -->
            <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <Link href="/leaderboard" class="flex items-center justify-between rounded-xl border border-border bg-card px-5 py-4 hover:bg-muted/50 transition-colors">
                    <div>
                        <div class="text-sm font-semibold text-foreground">Leaderboard</div>
                        <div class="text-xs text-muted-foreground">See global rankings</div>
                    </div>
                    <span class="text-muted-foreground">→</span>
                </Link>
                <Link href="/my-predictions" class="flex items-center justify-between rounded-xl border border-border bg-card px-5 py-4 hover:bg-muted/50 transition-colors">
                    <div>
                        <div class="text-sm font-semibold text-foreground">My Picks</div>
                        <div class="text-xs text-muted-foreground">Review your predictions</div>
                    </div>
                    <span class="text-muted-foreground">→</span>
                </Link>
                <Link href="/fixtures" class="flex items-center justify-between rounded-xl border border-border bg-card px-5 py-4 hover:bg-muted/50 transition-colors">
                    <div>
                        <div class="text-sm font-semibold text-foreground">All Fixtures</div>
                        <div class="text-xs text-muted-foreground">Browse all matches</div>
                    </div>
                    <span class="text-muted-foreground">→</span>
                </Link>
            </div>
        </div>
    </WorldCupLayout>
</template>
