<script setup lang="ts">
import WorldCupLayout from '@/layouts/WorldCupLayout.vue'
import FixtureCard from '@/components/FixtureCard.vue'
import { ref, computed } from 'vue'

interface Team {
    id: number
    name: string
    country_code: string
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

const props = defineProps<{
    tournament?: { name: string; year: number }
    fixturesByRound: Record<string, Fixture[]>
}>()

const selectedRound = ref<string | null>(null)
const rounds = computed(() => Object.keys(props.fixturesByRound))

const roundOrder = [
    'group_stage', 'round_of_32', 'round_of_16', 'quarter_final', 'semi_final', 'third_place', 'final'
]

const sortedRounds = computed(() =>
    rounds.value.sort((a, b) => roundOrder.indexOf(a) - roundOrder.indexOf(b))
)

const displayRounds = computed(() =>
    selectedRound.value ? [selectedRound.value] : sortedRounds.value
)

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
</script>

<template>
    <WorldCupLayout>
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="font-display text-4xl font-black uppercase tracking-wide text-foreground sm:text-5xl">
                    All <span class="text-pitch">Fixtures</span>
                </h1>
                <p v-if="tournament" class="mt-1 text-muted-foreground">{{ tournament.name }}</p>
            </div>

            <!-- Round filter pills -->
            <div class="mb-8 flex flex-wrap gap-2">
                <button
                    class="flex h-8 items-center rounded-full px-4 text-sm font-semibold transition-all"
                    :class="selectedRound === null ? 'bg-pitch text-pitch-foreground' : 'border border-border text-muted-foreground hover:text-foreground hover:border-foreground/30'"
                    @click="selectedRound = null"
                >
                    All
                </button>
                <button
                    v-for="round in sortedRounds"
                    :key="round"
                    class="flex h-8 items-center rounded-full px-4 text-sm font-semibold transition-all"
                    :class="selectedRound === round ? 'bg-pitch text-pitch-foreground' : 'border border-border text-muted-foreground hover:text-foreground hover:border-foreground/30'"
                    @click="selectedRound = round"
                >
                    {{ formatRound(round) }}
                </button>
            </div>

            <!-- No fixtures -->
            <div v-if="rounds.length === 0" class="rounded-xl border border-dashed border-border p-16 text-center">
                <div class="text-5xl mb-4">📅</div>
                <h3 class="font-display font-bold text-xl uppercase text-foreground mb-2">No Fixtures Yet</h3>
                <p class="text-sm text-muted-foreground">Fixtures will be published when the tournament schedule is confirmed.</p>
            </div>

            <!-- Fixtures by round -->
            <div v-else class="space-y-10">
                <div v-for="round in displayRounds" :key="round">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="h-px flex-1 bg-border" />
                        <h2 class="font-display font-black text-sm uppercase tracking-widest text-muted-foreground px-3">
                            {{ formatRound(round) }}
                        </h2>
                        <div class="h-px flex-1 bg-border" />
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <FixtureCard
                            v-for="fixture in fixturesByRound[round]"
                            :key="fixture.id"
                            :fixture="fixture"
                        />
                    </div>
                </div>
            </div>
        </div>
    </WorldCupLayout>
</template>
