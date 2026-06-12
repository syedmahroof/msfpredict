<script setup lang="ts">
import { countryFlag as flagEmoji } from '@/lib/flag'
import { Link, useForm } from '@inertiajs/vue3'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import flagsData from '@/data/flags.json'

interface Team {
    id: number
    name: string
    country_code: string
    flag?: string
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

const props = defineProps<{
    fixture: Fixture
    showPredictForm?: boolean
}>()

const existingPrediction = computed(() => props.fixture.predictions?.[0] ?? null)

const form = useForm({
    predicted_home_score: existingPrediction.value?.predicted_home_score ?? 0,
    predicted_away_score: existingPrediction.value?.predicted_away_score ?? 0,
})

// Predictions open 24 hours before kick-off and close at kick-off.
const PREDICTION_WINDOW_HOURS = 24

const kickoffAt = computed(() => new Date(props.fixture.scheduled_at).getTime())
const predictionsOpenAt = computed(() => kickoffAt.value - PREDICTION_WINDOW_HOURS * 60 * 60 * 1000)

// A ticking clock so the countdown and lock states update live.
const nowMs = ref(Date.now())
let clock: ReturnType<typeof setInterval> | undefined
onMounted(() => {
    clock = setInterval(() => {
        nowMs.value = Date.now()
    }, 1000)
})
onUnmounted(() => {
    if (clock) {
        clearInterval(clock)
    }
})

// Closed: the match started (or is live/finished/locked).
const isLocked = computed(() => {
    if (props.fixture.status === 'live' || props.fixture.status === 'finished' || props.fixture.status === 'locked') {
        return true
    }
    return kickoffAt.value <= nowMs.value
})

// Not open yet: still more than 24 hours before kick-off.
const notOpenYet = computed(() => !isLocked.value && nowMs.value < predictionsOpenAt.value)

const isLive = computed(() => props.fixture.status === 'live')
const isFinished = computed(() => props.fixture.status === 'finished')

// Live "starts in …" countdown for upcoming matches.
function formatRemaining(diffMs: number): string {
    const totalSeconds = Math.floor(Math.max(0, diffMs) / 1000)
    const days = Math.floor(totalSeconds / 86400)
    const hours = Math.floor((totalSeconds % 86400) / 3600)
    const minutes = Math.floor((totalSeconds % 3600) / 60)
    const seconds = totalSeconds % 60

    if (days > 0) {
        return `${days}d ${hours}h`
    }
    if (hours > 0) {
        return `${hours}h ${minutes}m`
    }
    if (minutes > 0) {
        return `${minutes}m ${seconds}s`
    }
    return `${seconds}s`
}

// Time left until kick-off (= until predictions close).
const remainingToKickoff = computed(() => kickoffAt.value - nowMs.value)
const remainingLabel = computed(() => formatRemaining(remainingToKickoff.value))

const countdownLabel = computed(() => {
    if (isLive.value || isFinished.value || remainingToKickoff.value <= 0) {
        return ''
    }
    return `Starts in ${remainingLabel.value}`
})

// Within the final hour, highlight the countdown.
const countdownImminent = computed(
    () => remainingToKickoff.value > 0 && remainingToKickoff.value <= 60 * 60 * 1000,
)

// Live match: elapsed minute and an approximate finish time (90' + half-time + stoppage).
const APPROX_MATCH_MINUTES = 115
const liveMinute = computed(() => Math.max(0, Math.floor((nowMs.value - kickoffAt.value) / 60000)))
const liveMinuteLabel = computed(() => (liveMinute.value > 90 ? "90+'" : `${liveMinute.value}'`))
const approxEndLabel = computed(() => {
    const d = new Date(kickoffAt.value + APPROX_MATCH_MINUTES * 60000)
    return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
})

function submitPrediction() {
    form.post(`/predict/${props.fixture.id}`, { preserveScroll: true })
}

function getFlagUrl(code: string): string {
    if (!code) return 'https://flagcdn.com/w320/un.png'
    const cleanCode = code.toUpperCase()
    const mapped = (flagsData as Record<string, { flag_url: string }>)[cleanCode]
    return mapped ? mapped.flag_url : 'https://flagcdn.com/w320/un.png'
}

const scheduledDate = computed(() => {
    const d = new Date(props.fixture.scheduled_at)
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
})

const scheduledTime = computed(() => {
    const d = new Date(props.fixture.scheduled_at)
    return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })
})

const predictionsOpenLabel = computed(() => {
    const d = new Date(predictionsOpenAt.value)
    return d.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
})
</script>

<template>
    <component
        :is="showPredictForm ? 'div' : Link"
        :href="showPredictForm ? undefined : `/fixtures/${fixture.id}`"
        class="group relative block overflow-hidden rounded-xl border border-border bg-card transition-all duration-200"
        :class="{
            'border-pitch/40 ring-1 ring-pitch/20 shadow-lg shadow-pitch/5': isLive,
            'cursor-pointer hover:border-foreground/30 hover:shadow-md': !showPredictForm,
            'hover:border-border hover:shadow-md': !isLive && showPredictForm,
        }"
    >
        <!-- Live indicator bar -->
        <div v-if="isLive" class="absolute top-0 left-0 right-0 h-0.5 bg-pitch" />

        <div class="p-4 sm:p-5">
            <!-- Header row -->
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span v-if="isLive" class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-pitch">
                        <span class="live-dot h-2 w-2 rounded-full bg-pitch" />
                        Live <span class="tabular-nums">{{ liveMinuteLabel }}</span>
                    </span>
                    <span v-else-if="isFinished" class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground">
                        Full Time
                    </span>
                    <span v-else class="text-[11px] font-medium text-muted-foreground">
                        {{ scheduledDate }} · {{ scheduledTime }}
                    </span>
                    <span
                        v-if="countdownLabel"
                        class="flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold tabular-nums"
                        :class="countdownImminent ? 'bg-pitch/15 text-pitch' : 'bg-muted text-muted-foreground'"
                    >
                        <span v-if="countdownImminent" class="live-dot h-1.5 w-1.5 rounded-full bg-pitch" />
                        {{ countdownLabel }}
                    </span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span
                        v-if="fixture.round !== 'group_stage'"
                        class="rounded-full bg-gold/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-gold"
                    >
                        {{ fixture.round.replace(/_/g, ' ') }}
                    </span>
                    <span
                        v-if="fixture.group"
                        class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold uppercase text-muted-foreground"
                    >
                        Group {{ fixture.group }}
                    </span>
                </div>
            </div>

            <!-- Match row -->
            <div class="flex items-center justify-between gap-3">
                <!-- Home team -->
                <div class="flex min-w-0 flex-1 flex-col items-center gap-1.5 text-center">
                    <span v-if="fixture.home_team.country_code === 'TBD'" class="text-2xl leading-none">🏳️</span>
                    <img v-else :src="fixture.home_team.flag || getFlagUrl(fixture.home_team.country_code)" :alt="fixture.home_team.name" class="h-6 w-9 shrink-0 object-cover rounded shadow-xs border border-border" />
                    <span class="truncate text-sm font-semibold text-foreground">{{ fixture.home_team.name }}</span>
                </div>

                <!-- Score / VS -->
                <div class="shrink-0 text-center">
                    <template v-if="isLive || isFinished">
                        <div class="font-display font-black text-3xl tracking-tight text-foreground tabular-nums">
                            {{ fixture.home_score ?? 0 }} – {{ fixture.away_score ?? 0 }}
                        </div>
                    </template>
                    <template v-else>
                        <div class="font-display text-2xl font-black text-muted-foreground/30 tracking-widest">VS</div>
                    </template>
                </div>

                <!-- Away team -->
                <div class="flex min-w-0 flex-1 flex-col items-center gap-1.5 text-center">
                    <span v-if="fixture.away_team.country_code === 'TBD'" class="text-2xl leading-none">🏳️</span>
                    <img v-else :src="fixture.away_team.flag || getFlagUrl(fixture.away_team.country_code)" :alt="fixture.away_team.name" class="h-6 w-9 shrink-0 object-cover rounded shadow-xs border border-border" />
                    <span class="truncate text-sm font-semibold text-foreground">{{ fixture.away_team.name }}</span>
                </div>
            </div>

            <!-- Live: ongoing banner with elapsed minute and approximate finish -->
            <div
                v-if="isLive"
                class="mt-3 flex items-center justify-center gap-2 rounded-lg border border-pitch/30 bg-pitch/10 py-2 text-xs font-semibold text-pitch"
            >
                <span class="live-dot h-2 w-2 rounded-full bg-pitch" />
                Match ongoing · <span class="tabular-nums">{{ liveMinuteLabel }}</span> · approx. ends {{ approxEndLabel }}
            </div>

            <!-- Not open yet: more than 24h before kick-off -->
            <template v-if="showPredictForm && notOpenYet">
                <div class="mt-4 flex flex-col items-center justify-center gap-1 rounded-lg bg-muted/30 py-3 text-center">
                    <span class="text-xs font-semibold text-muted-foreground">Predictions open {{ predictionsOpenLabel }}</span>
                    <span class="text-[10px] text-muted-foreground">Opens 24h before kick-off</span>
                </div>
            </template>

            <!-- Prediction form -->
            <template v-else-if="showPredictForm && !isLocked">
                <div class="mt-5 border-t border-border pt-4">
                    <p class="text-center text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Your Prediction
                    </p>
                    <p
                        class="mb-3 text-center text-[10px] font-medium tabular-nums"
                        :class="countdownImminent ? 'text-pitch' : 'text-muted-foreground'"
                    >
                        Predictions close in {{ remainingLabel }}
                    </p>
                    <form @submit.prevent="submitPrediction" class="flex items-center justify-center gap-3">
                        <div class="flex items-center gap-2">
                            <!-- Home score stepper -->
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-muted text-foreground hover:bg-muted/80 font-bold text-sm transition-colors"
                                    @click="form.predicted_home_score = Math.max(0, form.predicted_home_score - 1)"
                                >−</button>
                                <span class="w-8 text-center font-display font-black text-xl tabular-nums text-foreground">{{ form.predicted_home_score }}</span>
                                <button
                                    type="button"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-muted text-foreground hover:bg-muted/80 font-bold text-sm transition-colors"
                                    @click="form.predicted_home_score++"
                                >+</button>
                            </div>

                            <span class="font-display font-black text-lg text-muted-foreground/40">–</span>

                            <!-- Away score stepper -->
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-muted text-foreground hover:bg-muted/80 font-bold text-sm transition-colors"
                                    @click="form.predicted_away_score = Math.max(0, form.predicted_away_score - 1)"
                                >−</button>
                                <span class="w-8 text-center font-display font-black text-xl tabular-nums text-foreground">{{ form.predicted_away_score }}</span>
                                <button
                                    type="button"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-muted text-foreground hover:bg-muted/80 font-bold text-sm transition-colors"
                                    @click="form.predicted_away_score++"
                                >+</button>
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex h-9 items-center rounded-lg bg-pitch px-4 text-sm font-bold text-pitch-foreground hover:bg-pitch/90 disabled:opacity-50 transition-colors"
                        >
                            {{ existingPrediction ? 'Update' : 'Predict' }}
                        </button>
                    </form>

                    <!-- Existing prediction badge -->
                    <p v-if="existingPrediction" class="mt-2 text-center text-[11px] text-muted-foreground">
                        Current pick:
                        <span class="font-semibold text-pitch">
                            {{ existingPrediction.predicted_home_score }} – {{ existingPrediction.predicted_away_score }}
                        </span>
                    </p>
                </div>
            </template>

            <!-- Locked state with existing prediction -->
            <template v-else-if="showPredictForm && isLocked && existingPrediction">
                <div class="mt-4 flex items-center justify-center gap-2 rounded-lg bg-muted/50 py-2.5">
                    <svg class="h-3.5 w-3.5 text-pitch" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-xs font-semibold text-muted-foreground">
                        Your pick: <span class="text-pitch">{{ existingPrediction.predicted_home_score }} – {{ existingPrediction.predicted_away_score }}</span>
                    </span>
                </div>
            </template>

            <!-- Closed and not live (the live banner already covers ongoing matches) -->
            <template v-else-if="showPredictForm && isLocked && !isLive">
                <div class="mt-4 flex items-center justify-center gap-2 rounded-lg bg-muted/30 py-2.5">
                    <svg class="h-3.5 w-3.5 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span class="text-xs font-medium text-muted-foreground">Predictions closed</span>
                </div>
            </template>

            <!-- In predict mode the card is not a link, so offer an explicit way in -->
            <Link
                v-if="showPredictForm"
                :href="`/fixtures/${fixture.id}`"
                class="mt-3 flex items-center justify-center gap-1 text-[11px] font-medium text-muted-foreground hover:text-foreground"
            >
                View match &amp; all predictions →
            </Link>
        </div>
    </component>
</template>
