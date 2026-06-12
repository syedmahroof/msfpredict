<script setup lang="ts">
import WorldCupLayout from '@/layouts/WorldCupLayout.vue'
import FixtureCard from '@/components/FixtureCard.vue'
import ScoringRules from '@/components/ScoringRules.vue'
import { Link } from '@inertiajs/vue3'

interface Team {
    id: number
    name: string
    country_code: string
}

interface Prediction {
    predicted_home_score: number | null
    predicted_away_score: number | null
}

interface Fixture {
    id: number
    round: string
    group?: string
    scheduled_at: string
    status: string
    home_score?: number | null
    away_score?: number | null
    home_team: Team
    away_team: Team
    predictions?: Prediction[]
}

interface Paginated<T> {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    next_page_url?: string
    prev_page_url?: string
}

interface ScoringRule {
    exact_score_points: number
    correct_winner_points: number
    correct_draw_points: number
    correct_goal_difference_points: number
    correct_one_team_score_points: number
    knockout_multiplier: number
}

defineProps<{
    tournament?: { id: number; name: string; year: number }
    fixtures: Paginated<Fixture>
    scoringRule?: ScoringRule | null
}>()
</script>

<template>
    <WorldCupLayout>
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-2">
                    <Link href="/" class="text-sm text-muted-foreground hover:text-foreground transition-colors">Home</Link>
                    <span class="text-muted-foreground/50">/</span>
                    <span class="text-sm text-foreground font-medium">Predict</span>
                </div>
                <h1 class="font-display text-4xl font-black uppercase tracking-wide text-foreground sm:text-5xl">
                    Make Your <span class="text-pitch">Predictions</span>
                </h1>
                <p class="mt-2 text-muted-foreground">Predict the score for every match. Earn points for correct results.</p>
            </div>

            <!-- Points legend -->
            <div class="mb-8">
                <ScoringRules :scoring-rule="scoringRule" />
            </div>

            <!-- Fixtures -->
            <div v-if="fixtures.data.length === 0" class="rounded-xl border border-dashed border-border p-16 text-center">
                <div class="text-5xl mb-4">⚽</div>
                <h3 class="font-display font-bold text-xl uppercase text-foreground mb-2">All Caught Up!</h3>
                <p class="text-sm text-muted-foreground">No upcoming fixtures to predict right now.</p>
            </div>

            <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <FixtureCard
                    v-for="fixture in fixtures.data"
                    :key="fixture.id"
                    :fixture="fixture"
                    :show-predict-form="true"
                />
            </div>

            <!-- Pagination -->
            <div v-if="fixtures.last_page > 1" class="mt-8 flex items-center justify-center gap-2">
                <Link
                    v-if="fixtures.prev_page_url"
                    :href="fixtures.prev_page_url"
                    class="flex h-9 items-center rounded-lg border border-border px-4 text-sm font-medium text-foreground hover:bg-muted transition-colors"
                >
                    ← Previous
                </Link>
                <span class="px-4 text-sm text-muted-foreground">
                    Page {{ fixtures.current_page }} of {{ fixtures.last_page }}
                </span>
                <Link
                    v-if="fixtures.next_page_url"
                    :href="fixtures.next_page_url"
                    class="flex h-9 items-center rounded-lg border border-border px-4 text-sm font-medium text-foreground hover:bg-muted transition-colors"
                >
                    Next →
                </Link>
            </div>
        </div>
    </WorldCupLayout>
</template>
