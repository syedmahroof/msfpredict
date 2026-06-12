<script setup lang="ts">
import { countryFlag as flagEmoji } from '@/lib/flag'
import WorldCupLayout from '@/layouts/WorldCupLayout.vue'
import { Link } from '@inertiajs/vue3'
import flagsData from '@/data/flags.json'

interface Team {
    id: number
    name: string
    country_code: string
}

interface Fixture {
    id: number
    scheduled_at: string
    status: string
    home_score?: number | null
    away_score?: number | null
    home_team: Team
    away_team: Team
}

interface Prediction {
    id: number
    predicted_home_score: number | null
    predicted_away_score: number | null
    points_earned: number
    is_exact_score: boolean
    is_correct_winner: boolean
    is_calculated: boolean
    fixture: Fixture
}

interface Paginated<T> {
    data: T[]
    current_page: number
    last_page: number
    total: number
    next_page_url?: string
    prev_page_url?: string
}

defineProps<{
    tournament?: { name: string; year: number }
    predictions: Paginated<Prediction>
}>()

function getFlagUrl(code: string): string {
    if (!code) return 'https://flagcdn.com/w320/un.png'
    const cleanCode = code.toUpperCase()
    const mapped = (flagsData as Record<string, { flag_url: string }>)[cleanCode]
    return mapped ? mapped.flag_url : 'https://flagcdn.com/w320/un.png'
}

function pointsColor(pts: number): string {
    if (pts >= 10) return 'text-pitch'
    if (pts >= 5) return 'text-gold'
    if (pts > 0) return 'text-foreground'
    return 'text-muted-foreground'
}
</script>

<template>
    <WorldCupLayout>
        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="font-display text-4xl font-black uppercase tracking-wide text-foreground">My Predictions</h1>
                    <p class="text-sm text-muted-foreground mt-1">{{ predictions.total }} total predictions</p>
                </div>
                <Link href="/predict" class="flex h-9 items-center rounded-lg bg-pitch px-4 text-sm font-bold text-pitch-foreground hover:bg-pitch/90 transition-colors">
                    + Predict More
                </Link>
            </div>

            <div v-if="predictions.data.length === 0" class="rounded-xl border border-dashed border-border p-16 text-center">
                <div class="text-5xl mb-4">🎯</div>
                <h3 class="font-display font-bold text-xl uppercase text-foreground mb-2">No Predictions Yet</h3>
                <p class="text-sm text-muted-foreground mb-6">Start predicting matches to earn points and climb the leaderboard.</p>
                <Link href="/predict" class="inline-flex h-10 items-center rounded-lg bg-pitch px-6 text-sm font-bold text-pitch-foreground">
                    Make Predictions
                </Link>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="prediction in predictions.data"
                    :key="prediction.id"
                    class="flex items-center gap-4 rounded-xl border border-border bg-card p-4 hover:bg-card/80 transition-colors"
                    :class="{
                        'border-pitch/30': prediction.is_exact_score,
                        'border-gold/30': prediction.is_correct_winner && !prediction.is_exact_score,
                    }"
                >
                    <!-- Teams -->
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <div class="text-center shrink-0">
                            <div v-if="prediction.fixture.home_team.country_code === 'TBD'" class="text-2xl leading-none mb-1">🏳️</div>
                            <div v-else class="flex justify-center items-center h-8 mb-1"><img :src="getFlagUrl(prediction.fixture.home_team.country_code)" :alt="prediction.fixture.home_team.name" class="h-6 w-9 object-cover rounded shadow-xs border border-border" /></div>
                            <div class="text-[10px] font-medium text-muted-foreground truncate max-w-16 hidden sm:block">{{ prediction.fixture.home_team.name }}</div>
                        </div>

                        <div class="text-center">
                            <!-- Your prediction -->
                            <div class="font-display font-black text-xl tabular-nums text-foreground">
                                {{ prediction.predicted_home_score }} – {{ prediction.predicted_away_score }}
                            </div>
                            <!-- Actual score -->
                            <div v-if="prediction.is_calculated" class="text-xs text-muted-foreground">
                                Result: {{ prediction.fixture.home_score }} – {{ prediction.fixture.away_score }}
                            </div>
                            <div v-else class="text-[10px] text-muted-foreground/50 uppercase tracking-wider">Upcoming</div>
                        </div>

                        <div class="text-center shrink-0">
                            <div v-if="prediction.fixture.away_team.country_code === 'TBD'" class="text-2xl leading-none mb-1">🏳️</div>
                            <div v-else class="flex justify-center items-center h-8 mb-1"><img :src="getFlagUrl(prediction.fixture.away_team.country_code)" :alt="prediction.fixture.away_team.name" class="h-6 w-9 object-cover rounded shadow-xs border border-border" /></div>
                            <div class="text-[10px] font-medium text-muted-foreground truncate max-w-16 hidden sm:block">{{ prediction.fixture.away_team.name }}</div>
                        </div>
                    </div>

                    <!-- Points badge -->
                    <div class="shrink-0 text-right">
                        <template v-if="prediction.is_calculated">
                            <div class="font-display font-black text-2xl tabular-nums" :class="pointsColor(prediction.points_earned)">
                                +{{ prediction.points_earned }}
                            </div>
                            <div class="text-[10px] text-muted-foreground">
                                <span v-if="prediction.is_exact_score" class="text-pitch font-semibold">Exact! 🎯</span>
                                <span v-else-if="prediction.is_correct_winner" class="text-gold font-semibold">Correct ✓</span>
                                <span v-else>Miss</span>
                            </div>
                        </template>
                        <template v-else>
                            <div class="rounded-full bg-muted px-2.5 py-1 text-[11px] font-semibold text-muted-foreground">Pending</div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="predictions.last_page > 1" class="mt-8 flex items-center justify-center gap-2">
                <Link v-if="predictions.prev_page_url" :href="predictions.prev_page_url" class="flex h-9 items-center rounded-lg border border-border px-4 text-sm font-medium text-foreground hover:bg-muted transition-colors">← Prev</Link>
                <span class="px-4 text-sm text-muted-foreground">{{ predictions.current_page }} / {{ predictions.last_page }}</span>
                <Link v-if="predictions.next_page_url" :href="predictions.next_page_url" class="flex h-9 items-center rounded-lg border border-border px-4 text-sm font-medium text-foreground hover:bg-muted transition-colors">Next →</Link>
            </div>
        </div>
    </WorldCupLayout>
</template>
