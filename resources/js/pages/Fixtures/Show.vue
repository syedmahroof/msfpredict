<script setup lang="ts">
import WorldCupLayout from '@/layouts/WorldCupLayout.vue'
import { countryFlag as flagEmoji } from '@/lib/flag'
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

interface Team {
    id: number
    name: string
    country_code: string
}

interface Fixture {
    id: number
    round: string
    group?: string | null
    scheduled_at: string
    status: string
    home_score?: number | null
    away_score?: number | null
    home_team: Team
    away_team: Team
    stadium?: { name: string; city: string } | null
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

const props = defineProps<{
    fixture: Fixture
    userPrediction?: Prediction | null
    predictionStats: { home: number; draw: number; away: number; total: number }
    predictions: Prediction[]
    isLocked: boolean
    pointsCalculated: boolean
}>()

const page = usePage()
const currentUserId = computed(() => (page.props.auth as { user?: { id: number } } | undefined)?.user?.id)

const isFinished = computed(() => props.fixture.status === 'finished')
const isLive = computed(() => props.fixture.status === 'live')

function pct(n: number): number {
    return props.predictionStats.total > 0 ? Math.round((n / props.predictionStats.total) * 100) : 0
}

function formatRound(round: string): string {
    const labels: Record<string, string> = {
        group_stage: 'Group Stage',
        round_of_32: 'Round of 32',
        round_of_16: 'Round of 16',
        quarter_final: 'Quarter-Finals',
        semi_final: 'Semi-Finals',
        third_place: 'Third Place',
        final: 'Final',
    }
    return labels[round] ?? round.replace(/_/g, ' ')
}

const kickoff = computed(() =>
    new Date(props.fixture.scheduled_at).toLocaleString('en-US', {
        weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true,
    }),
)
</script>

<template>
    <WorldCupLayout>
        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
            <Link href="/fixtures" class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-muted-foreground hover:text-foreground">
                ← All fixtures
            </Link>

            <!-- Match header -->
            <div class="rounded-2xl border border-border bg-card p-6">
                <div class="mb-4 flex items-center justify-center gap-2 text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">
                    <span>{{ formatRound(fixture.round) }}</span>
                    <span v-if="fixture.group">· Group {{ fixture.group }}</span>
                </div>

                <div class="flex items-center justify-between gap-4">
                    <div class="flex flex-1 flex-col items-center gap-2 text-center">
                        <span class="text-4xl">{{ flagEmoji(fixture.home_team.country_code) }}</span>
                        <span class="text-sm font-bold text-foreground">{{ fixture.home_team.name }}</span>
                    </div>

                    <div class="shrink-0 text-center">
                        <div v-if="isLive || isFinished" class="font-display text-4xl font-black tabular-nums text-foreground">
                            {{ fixture.home_score ?? 0 }} – {{ fixture.away_score ?? 0 }}
                        </div>
                        <div v-else class="font-display text-3xl font-black tracking-widest text-muted-foreground/30">VS</div>
                        <div class="mt-1 text-[11px] font-medium" :class="isLive ? 'text-pitch' : 'text-muted-foreground'">
                            <span v-if="isLive" class="inline-flex items-center gap-1">
                                <span class="live-dot h-1.5 w-1.5 rounded-full bg-pitch" /> Live
                            </span>
                            <span v-else-if="isFinished">Full Time</span>
                            <span v-else>{{ kickoff }}</span>
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col items-center gap-2 text-center">
                        <span class="text-4xl">{{ flagEmoji(fixture.away_team.country_code) }}</span>
                        <span class="text-sm font-bold text-foreground">{{ fixture.away_team.name }}</span>
                    </div>
                </div>

                <p v-if="fixture.stadium" class="mt-5 text-center text-xs text-muted-foreground">
                    {{ fixture.stadium.name }} · {{ fixture.stadium.city }}
                </p>
            </div>

            <!-- Prediction breakdown -->
            <div v-if="predictionStats.total > 0" class="mt-6 rounded-2xl border border-border bg-card p-6">
                <h2 class="mb-4 font-display text-lg font-black uppercase tracking-wide text-foreground">Who People Backed</h2>
                <div class="space-y-3">
                    <div v-for="row in [
                        { label: fixture.home_team.name + ' win', value: predictionStats.home },
                        { label: 'Draw', value: predictionStats.draw },
                        { label: fixture.away_team.name + ' win', value: predictionStats.away },
                    ]" :key="row.label">
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="font-medium text-foreground">{{ row.label }}</span>
                            <span class="tabular-nums text-muted-foreground">{{ pct(row.value) }}% · {{ row.value }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div class="h-full rounded-full bg-pitch" :style="{ width: pct(row.value) + '%' }" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- All predictions -->
            <div class="mt-6 rounded-2xl border border-border bg-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-border px-6 py-4">
                    <h2 class="font-display text-lg font-black uppercase tracking-wide text-foreground">All Predictions</h2>
                    <span class="text-xs text-muted-foreground">{{ predictionStats.total }} total</span>
                </div>

                <!-- Hidden until kick-off -->
                <div v-if="!isLocked" class="p-10 text-center">
                    <div class="mb-3 text-4xl">🔒</div>
                    <p class="text-sm text-muted-foreground">Everyone's predictions are revealed after kick-off.</p>
                    <div v-if="userPrediction" class="mt-4 flex flex-col items-center gap-1 rounded-lg bg-muted/50 px-4 py-2.5 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-muted-foreground">Your pick:</span>
                            <span class="font-bold text-pitch tabular-nums">
                                {{ userPrediction.predicted_home_score }} – {{ userPrediction.predicted_away_score }}
                            </span>
                        </div>
                        <template v-if="fixture.round !== 'group_stage' && userPrediction.predicted_home_score === userPrediction.predicted_away_score && userPrediction.predicted_winner">
                            <span class="text-[10px] text-muted-foreground">
                                ({{ userPrediction.predicted_winner === 'home' ? fixture.home_team.name : fixture.away_team.name }} on penalties)
                            </span>
                        </template>
                    </div>
                </div>

                <div v-else-if="predictions.length === 0" class="p-10 text-center">
                    <p class="text-sm text-muted-foreground">No predictions were made for this match.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/20 text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                                <th class="px-6 py-3 text-left">Player</th>
                                <th class="px-6 py-3 text-center">Pick</th>
                                <th class="px-6 py-3 text-right">Points</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="prediction in predictions"
                                :key="prediction.id"
                                class="transition-colors hover:bg-muted/10"
                                :class="prediction.user.id === currentUserId ? 'bg-pitch/5' : ''"
                            >
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">{{ prediction.user.country_code ? flagEmoji(prediction.user.country_code) : '👤' }}</span>
                                        <span class="font-medium text-foreground">{{ prediction.user.name }}</span>
                                        <span v-if="prediction.user.id === currentUserId" class="rounded bg-pitch/15 px-1.5 py-0.5 text-[10px] font-bold uppercase text-pitch">You</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <div class="font-display font-black tabular-nums text-foreground flex flex-col items-center justify-center">
                                        <span>{{ prediction.predicted_home_score }} – {{ prediction.predicted_away_score }}</span>
                                        <template v-if="fixture.round !== 'group_stage' && prediction.predicted_home_score === prediction.predicted_away_score && prediction.predicted_winner">
                                            <span class="text-[10px] font-normal text-muted-foreground mt-0.5">
                                                ({{ prediction.predicted_winner === 'home' ? fixture.home_team.name : fixture.away_team.name }} on penalties)
                                            </span>
                                        </template>
                                    </div>
                                    <span v-if="prediction.is_exact_score" class="ml-1 rounded bg-pitch/15 px-1.5 py-0.5 text-[9px] font-bold uppercase text-pitch">Exact</span>
                                    <span v-else-if="prediction.is_correct_winner" class="ml-1 rounded bg-muted px-1.5 py-0.5 text-[9px] font-bold uppercase text-muted-foreground">Winner</span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <span v-if="prediction.is_calculated" class="font-display font-black tabular-nums" :class="prediction.points_earned > 0 ? 'text-pitch' : 'text-muted-foreground'">
                                        {{ prediction.points_earned }}
                                    </span>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </WorldCupLayout>
</template>
