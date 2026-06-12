<script setup lang="ts">
import { countryFlag as flagEmoji } from '@/lib/flag'
import WorldCupLayout from '@/layouts/WorldCupLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

interface Entry {
    id: number
    name: string
    username?: string
    avatar?: string
    country_code?: string
    level: number
    total_points: number
    prediction_count: number
    correct_predictions: number
    rank: number
}


const props = defineProps<{
    tournament?: { id: number; name: string; year: number } | null
    globalLeaderboard?: Entry[]
    dailyLeaderboard?: Entry[]
    userRank?: number
    activeTab: string
}>()

const activeTab = ref(props.activeTab || 'global')

function setTab(tab: string) {
    activeTab.value = tab
    router.get('/leaderboard', { tab }, { preserveScroll: true, replace: true })
}


function rankDisplay(rank: number): string {
    if (rank === 1) return '🥇'
    if (rank === 2) return '🥈'
    if (rank === 3) return '🥉'
    return `${rank}`
}

function accuracy(entry: Entry): string {
    if (entry.prediction_count === 0) return '0%'
    return `${Math.round((entry.correct_predictions / entry.prediction_count) * 100)}%`
}

const currentLeaderboard = () => {
    if (activeTab.value === 'daily') return props.dailyLeaderboard
    return props.globalLeaderboard
}
</script>

<template>
    <WorldCupLayout>
        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="font-display text-4xl font-black uppercase tracking-wide text-foreground sm:text-5xl">
                    <span class="text-pitch">Leaderboard</span>
                </h1>
                <p class="mt-2 text-muted-foreground">
                    {{ tournament?.name ?? 'Season' }} · Updated every 5 minutes
                </p>
            </div>

            <!-- User's rank card -->
            <div v-if="userRank" class="mb-6 flex items-center gap-4 rounded-xl border border-pitch/30 bg-pitch/5 px-5 py-4">
                <div class="font-display font-black text-3xl text-pitch">#{{ userRank }}</div>
                <div>
                    <p class="font-semibold text-foreground">Your current rank</p>
                    <p class="text-xs text-muted-foreground">Keep predicting to climb higher!</p>
                </div>
                <Link href="/predict" class="ml-auto flex h-9 shrink-0 items-center rounded-lg bg-pitch px-4 text-sm font-bold text-pitch-foreground hover:bg-pitch/90 transition-colors">
                    Predict Now
                </Link>
            </div>

            <!-- Tab switcher -->
            <div class="mb-6 flex gap-1 rounded-xl border border-border bg-muted/40 p-1">
                <button
                    v-for="tab in [{ id: 'global', label: '🌍 All' }, { id: 'daily', label: '📅 Today' }]"
                    :key="tab.id"
                    class="flex-1 rounded-lg py-2 text-sm font-semibold transition-all"
                    :class="activeTab === tab.id
                        ? 'bg-card text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground'"
                    @click="setTab(tab.id)"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- Leaderboard table -->
            <div class="rounded-xl border border-border bg-card overflow-hidden">
                <!-- Skeleton -->
                <div v-if="!currentLeaderboard()" class="divide-y divide-border">
                    <div v-for="i in 10" :key="i" class="flex items-center gap-4 px-5 py-4">
                        <div class="h-6 w-8 rounded bg-muted animate-pulse" />
                        <div class="h-9 w-9 rounded-full bg-muted animate-pulse" />
                        <div class="flex-1 space-y-2">
                            <div class="h-4 w-32 rounded bg-muted animate-pulse" />
                            <div class="h-3 w-20 rounded bg-muted animate-pulse" />
                        </div>
                        <div class="h-6 w-12 rounded bg-muted animate-pulse" />
                    </div>
                </div>

                <div v-else-if="currentLeaderboard()!.length === 0" class="p-16 text-center">
                    <div class="text-5xl mb-4">📊</div>
                    <p class="text-sm text-muted-foreground">No data available yet. Make predictions to appear here!</p>
                </div>

                <div v-else>
                    <!-- Table header -->
                    <div class="flex items-center gap-4 border-b border-border bg-muted/30 px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                        <span class="w-8 shrink-0 text-center">Rank</span>
                        <span class="flex-1">Player</span>
                        <span class="hidden sm:block w-16 text-center">Picks</span>
                        <span class="hidden sm:block w-16 text-center">Acc.</span>
                        <span class="w-16 text-right">Pts</span>
                    </div>

                    <!-- Rows -->
                    <div class="divide-y divide-border">
                        <Link
                            v-for="entry in currentLeaderboard()"
                            :key="entry.id"
                            :href="`/players/${entry.id}`"
                            class="flex items-center gap-4 px-5 py-3.5 hover:bg-muted/20 transition-colors"
                            :class="{
                                'bg-pitch/5': entry.rank <= 3,
                            }"
                        >
                            <span
                                class="w-8 shrink-0 text-center font-display font-black"
                                :class="entry.rank <= 3 ? 'text-lg' : 'text-sm text-muted-foreground'"
                            >
                                {{ rankDisplay(entry.rank) }}
                            </span>

                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-bold text-foreground ring-1 ring-border">
                                {{ entry.name.charAt(0).toUpperCase() }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="truncate font-semibold text-foreground text-sm">{{ entry.name }}</span>
                                    <span v-if="entry.level > 1" class="shrink-0 rounded-full bg-gold/20 px-1.5 py-0.5 text-[10px] font-bold text-gold">
                                        Lv{{ entry.level }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-muted-foreground">
                                    {{ entry.country_code ? flagEmoji(entry.country_code) : '' }}
                                    {{ entry.username ? `@${entry.username}` : '' }}
                                </div>
                            </div>

                            <span class="hidden sm:block w-16 text-center text-sm text-muted-foreground tabular-nums">
                                {{ entry.prediction_count }}
                            </span>
                            <span class="hidden sm:block w-16 text-center text-sm text-muted-foreground tabular-nums">
                                {{ accuracy(entry) }}
                            </span>
                            <span class="w-16 text-right font-display font-black text-xl tabular-nums" :class="entry.rank <= 3 ? 'text-pitch' : 'text-foreground'">
                                {{ entry.total_points }}
                            </span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </WorldCupLayout>
</template>
