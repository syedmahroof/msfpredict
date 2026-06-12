<script setup lang="ts">
import { countryFlag as flagEmoji } from '@/lib/flag'
import WorldCupLayout from '@/layouts/WorldCupLayout.vue'
import FixtureCard from '@/components/FixtureCard.vue'
import ScoringRules from '@/components/ScoringRules.vue'
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const isAuthenticated = computed(() => Boolean((page.props.auth as { user?: unknown } | undefined)?.user))

interface Team {
    id: number
    name: string
    country_code: string
    flag?: string
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
}

interface Tournament {
    id: number
    name: string
    year: number
    start_date: string
    end_date: string
}

interface ScoringRule {
    exact_score_points: number
    correct_winner_points: number
    correct_draw_points: number
    correct_goal_difference_points: number
    correct_one_team_score_points: number
    knockout_multiplier: number
}

interface Advertisement {
    image_url: string
    link_url?: string | null
    alt?: string | null
}

const props = defineProps<{
    tournament?: Tournament
    liveFixtures: Fixture[]
    upcomingFixtures: Fixture[]
    scoringRule?: ScoringRule | null
    advertisement?: Advertisement | null
}>()

const countdownTarget = computed(() => {
    if (props.upcomingFixtures.length > 0) {
        return new Date(props.upcomingFixtures[0].scheduled_at)
    }
    if (props.tournament) {
        return new Date(props.tournament.start_date)
    }
    return null
})


</script>

<template>
    <WorldCupLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden mesh-bg">
            <div class="absolute inset-0 bg-gradient-to-b from-background via-background/80 to-background pointer-events-none" />

            <!-- Grid pattern overlay -->
            <div class="absolute inset-0 opacity-[0.04]" style="background-image: linear-gradient(hsl(150 72% 36%) 1px, transparent 1px), linear-gradient(90deg, hsl(150 72% 36%) 1px, transparent 1px); background-size: 40px 40px;" />

            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24">
                <div class="mx-auto max-w-3xl text-center">
                    <img src="/images/muslim-league-flag.png" alt="Muslim Youth League" class="mx-auto mb-6 h-24 w-auto rounded-xl shadow-lg ring-1 ring-border sm:h-28" />
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-pitch/30 bg-pitch/10 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-pitch">
                        <span class="live-dot h-2 w-2 rounded-full bg-pitch" />
                        FIFA World Cup 2026
                    </div>
                    <h1 class="font-display text-5xl font-black uppercase leading-none tracking-tight text-foreground sm:text-7xl">
                        PREDICT.<br/>
                        <span class="text-pitch">COMPETE.</span><br/>
                        WIN.
                    </h1>
                    <p class="mt-6 text-base text-muted-foreground sm:text-lg max-w-xl mx-auto leading-relaxed">
                        Score predict every World Cup 2026 match and climb the Muslim Youth League leaderboard.
                    </p>
                    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <Link v-if="isAuthenticated" href="/predict" class="flex h-12 w-full sm:w-auto items-center justify-center rounded-xl bg-pitch px-8 text-base font-bold text-pitch-foreground hover:bg-pitch/90 transition-all hover:scale-[1.02] active:scale-[0.98]">
                            Make Predictions
                        </Link>
                        <Link v-else href="/register" class="flex h-12 w-full sm:w-auto items-center justify-center rounded-xl bg-pitch px-8 text-base font-bold text-pitch-foreground hover:bg-pitch/90 transition-all hover:scale-[1.02] active:scale-[0.98]">
                            Sign Up
                        </Link>
                        <Link href="/leaderboard" class="flex h-12 w-full sm:w-auto items-center justify-center rounded-xl border border-border bg-card px-8 text-base font-medium text-foreground hover:bg-muted transition-colors">
                            View Leaderboard
                        </Link>
                    </div>

                    <!-- Stats row -->
                    <div class="mt-12 grid grid-cols-3 gap-4 sm:gap-8 border-t border-border pt-8">
                        <div>
                            <div class="font-display font-black text-3xl sm:text-4xl text-foreground">72</div>
                            <div class="text-xs text-muted-foreground mt-0.5">Group matches</div>
                        </div>
                        <div>
                            <div class="font-display font-black text-3xl sm:text-4xl text-pitch">10pts</div>
                            <div class="text-xs text-muted-foreground mt-0.5">Exact score</div>
                        </div>
                        <div>
                            <div class="font-display font-black text-3xl sm:text-4xl text-foreground">48</div>
                            <div class="text-xs text-muted-foreground mt-0.5">Nations</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Home page advertisement slot -->
        <section class="mx-auto max-w-7xl px-4 pt-8 sm:px-6">
            <!-- When an `advertisement` prop is provided, render the banner; otherwise show a placeholder slot. -->
            <a
                v-if="props.advertisement"
                :href="props.advertisement.link_url ?? '#'"
                target="_blank"
                rel="noopener sponsored"
                class="group block overflow-hidden rounded-2xl border border-border"
            >
                <img
                    :src="props.advertisement.image_url"
                    :alt="props.advertisement.alt ?? 'Advertisement'"
                    class="h-auto w-full transition-transform group-hover:scale-[1.01]"
                />
            </a>
            <div
                v-else
                class="flex min-h-[120px] items-center justify-center rounded-2xl border border-dashed border-border bg-muted/30 px-6 py-8 text-center"
            >
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Advertisement</p>
                    <p class="mt-1 text-sm text-muted-foreground">Your ad here — pass an <code class="text-xs">advertisement</code> prop or drop a banner into this slot.</p>
                </div>
            </div>
        </section>

        <!-- Live Fixtures -->
        <section v-if="liveFixtures.length > 0" class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            <div class="mb-6 flex items-center gap-3">
                <span class="live-dot h-3 w-3 rounded-full bg-pitch" />
                <h2 class="font-display text-2xl font-black uppercase tracking-wide text-foreground">Live Now</h2>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <FixtureCard v-for="fixture in liveFixtures" :key="fixture.id" :fixture="fixture" />
            </div>
        </section>

        <!-- Upcoming Matches -->
        <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="font-display text-2xl font-black uppercase tracking-wide text-foreground">Upcoming Matches</h2>
                <Link href="/fixtures" class="text-sm font-medium text-pitch hover:underline">All matches →</Link>
            </div>

            <div v-if="upcomingFixtures.length === 0" class="rounded-xl border border-dashed border-border p-10 text-center">
                <div class="text-4xl mb-3">⚽</div>
                <p class="text-sm text-muted-foreground">No upcoming fixtures scheduled yet.</p>
            </div>

            <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <FixtureCard
                    v-for="fixture in upcomingFixtures"
                    :key="fixture.id"
                    :fixture="fixture"
                />
            </div>

            <div v-if="upcomingFixtures.length >= 6" class="mt-6 text-center">
                <Link href="/predict" class="inline-flex h-10 items-center rounded-lg border border-border px-6 text-sm font-medium text-foreground hover:bg-muted transition-colors">
                    Predict All Matches
                </Link>
            </div>
        </section>

        <!-- How points work -->
        <section v-if="scoringRule" class="mx-auto max-w-7xl px-4 pb-8 sm:px-6">
            <ScoringRules :scoring-rule="scoringRule" />
        </section>

        <!-- How it works -->
        <section class="border-t border-border bg-muted/20 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <h2 class="mb-10 text-center font-display text-3xl font-black uppercase tracking-wide text-foreground sm:text-4xl">
                    How It Works
                </h2>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div class="rounded-xl border border-border bg-card p-6 text-center">
                        <div class="mb-4 text-4xl">⚽</div>
                        <h3 class="mb-2 font-display font-bold text-lg uppercase tracking-wide text-foreground">1. Pick Scores</h3>
                        <p class="text-sm text-muted-foreground leading-relaxed">Predict the exact score for every World Cup match before kick-off.</p>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-6 text-center">
                        <div class="mb-4 text-4xl">🏆</div>
                        <h3 class="mb-2 font-display font-bold text-lg uppercase tracking-wide text-foreground">2. Earn Points</h3>
                        <p class="text-sm text-muted-foreground leading-relaxed"><span class="font-semibold text-pitch">10pts</span> exact score, <span class="font-semibold text-foreground">5pts</span> correct winner, <span class="font-semibold text-foreground">3pts</span> draw, <span class="font-semibold text-foreground">+2</span> exact margin, <span class="font-semibold text-foreground">1pt</span> one team’s score. Knockouts ×2.</p>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-6 text-center">
                        <div class="mb-4 text-4xl">🌍</div>
                        <h3 class="mb-2 font-display font-bold text-lg uppercase tracking-wide text-foreground">3. Climb Ranks</h3>
                        <p class="text-sm text-muted-foreground leading-relaxed">Compete with the whole company on a single live leaderboard, updated in real-time.</p>
                    </div>
                </div>
            </div>
        </section>
    </WorldCupLayout>
</template>
