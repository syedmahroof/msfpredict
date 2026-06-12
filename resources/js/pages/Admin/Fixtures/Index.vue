<script setup lang="ts">
import { countryFlag as flagEmoji } from '@/lib/flag'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import flagsData from '@/data/flags.json'

interface Fixture {
    id: number
    scheduled_at: string
    status: string
    round: string
    group?: string
    home_score?: number | null
    away_score?: number | null
    winner?: string | null
    tournament: { name: string }
    home_team: { id: number; name: string; country_code: string }
    away_team: { id: number; name: string; country_code: string }
    points_calculated: boolean
}

interface Paginated<T> {
    data: T[]
    current_page: number
    last_page: number
    next_page_url?: string
    prev_page_url?: string
}

interface TeamOption {
    id: number
    name: string
    country_code: string
}

defineProps<{
    fixtures: Paginated<Fixture>
    teams: TeamOption[]
}>()

const editingFixture = ref<Fixture | null>(null)
const resultForm = useForm({ home_score: 0, away_score: 0, status: 'finished', winner: 'home' })

function deriveWinner(home: number, away: number): string {
    if (home > away) return 'home'
    if (away > home) return 'away'
    return 'draw'
}

const teamsFixture = ref<Fixture | null>(null)
const teamsForm = useForm<{ home_team_id: number | null; away_team_id: number | null }>({ home_team_id: null, away_team_id: null })

function openEdit(fixture: Fixture) {
    editingFixture.value = fixture
    resultForm.home_score = fixture.home_score ?? 0
    resultForm.away_score = fixture.away_score ?? 0
    resultForm.status = fixture.status === 'live' ? 'live' : 'finished'
    resultForm.winner = fixture.winner ?? deriveWinner(resultForm.home_score, resultForm.away_score)
}

// Keep the winner suggestion in sync with the score, unless it's a level score
// (knockout decided on penalties) where the admin must choose.
function onScoreChange() {
    if (resultForm.home_score !== resultForm.away_score) {
        resultForm.winner = deriveWinner(resultForm.home_score, resultForm.away_score)
    }
}

function submitResult() {
    if (!editingFixture.value) return
    resultForm.patch(`/admin/fixtures/${editingFixture.value.id}/result`, {
        onSuccess: () => { editingFixture.value = null },
    })
}

function openTeams(fixture: Fixture) {
    teamsFixture.value = fixture
    teamsForm.home_team_id = fixture.home_team.id
    teamsForm.away_team_id = fixture.away_team.id
}

function submitTeams() {
    if (!teamsFixture.value) return
    teamsForm.patch(`/admin/fixtures/${teamsFixture.value.id}/teams`, {
        onSuccess: () => { teamsFixture.value = null },
    })
}

function getFlagUrl(code: string): string {
    if (!code) return 'https://flagcdn.com/w320/un.png'
    const cleanCode = code.toUpperCase()
    const mapped = (flagsData as Record<string, { flag_url: string }>)[cleanCode]
    return mapped ? mapped.flag_url : 'https://flagcdn.com/w320/un.png'
}

function statusColor(status: string): string {
    const map: Record<string, string> = {
        upcoming: 'text-muted-foreground bg-muted',
        live: 'text-pitch bg-pitch/15',
        finished: 'text-foreground bg-muted',
        locked: 'text-gold bg-gold/15',
    }
    return map[status] ?? 'text-muted-foreground bg-muted'
}
</script>

<template>
    <AdminLayout>
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="font-display text-3xl font-black uppercase tracking-wide text-foreground">Fixtures</h1>
                <Link href="/admin/fixtures/create" class="flex h-9 items-center rounded-lg bg-pitch px-4 text-sm font-bold text-pitch-foreground hover:bg-pitch/90 transition-colors">
                    + Add Fixture
                </Link>
            </div>

            <div class="rounded-xl border border-border bg-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/20">
                                <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Match</th>
                                <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted-foreground hidden sm:table-cell">Date</th>
                                <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Score</th>
                                <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Status</th>
                                <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Pts Calc</th>
                                <th class="px-5 py-3" />
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="fixture in fixtures.data" :key="fixture.id" class="hover:bg-muted/10 transition-colors">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <span v-if="fixture.home_team.country_code === 'TBD'">🏳️</span>
                                        <img v-else :src="getFlagUrl(fixture.home_team.country_code)" :alt="fixture.home_team.name" class="h-4 w-6 object-cover rounded shadow-xs border border-border inline-block" />
                                        <span class="font-medium text-foreground">{{ fixture.home_team.name }}</span>
                                        <span class="text-muted-foreground/50 text-xs">vs</span>
                                        <span class="font-medium text-foreground">{{ fixture.away_team.name }}</span>
                                        <span v-if="fixture.away_team.country_code === 'TBD'">🏳️</span>
                                        <img v-else :src="getFlagUrl(fixture.away_team.country_code)" :alt="fixture.away_team.name" class="h-4 w-6 object-cover rounded shadow-xs border border-border inline-block" />
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground hidden sm:table-cell text-xs">
                                    {{ new Date(fixture.scheduled_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                </td>
                                <td class="px-5 py-3">
                                    <span v-if="fixture.home_score !== null" class="font-display font-black text-base tabular-nums text-foreground">
                                        {{ fixture.home_score }} – {{ fixture.away_score }}
                                    </span>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider" :class="statusColor(fixture.status)">
                                        {{ fixture.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <span v-if="fixture.points_calculated" class="text-pitch text-xs font-semibold">✓ Done</span>
                                    <span v-else class="text-muted-foreground text-xs">Pending</span>
                                </td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    <Link
                                        :href="`/admin/fixtures/${fixture.id}/predictions`"
                                        class="text-xs font-medium text-pitch hover:underline mr-3"
                                    >
                                        Predictions
                                    </Link>
                                    <Link
                                        :href="`/admin/fixtures/${fixture.id}/edit`"
                                        class="text-xs font-medium text-muted-foreground hover:text-foreground hover:underline mr-3"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        class="text-xs font-medium text-muted-foreground hover:text-foreground hover:underline mr-3"
                                        @click="openTeams(fixture)"
                                    >
                                        Set Teams
                                    </button>
                                    <button
                                        class="text-xs font-medium text-pitch hover:underline"
                                        @click="openEdit(fixture)"
                                    >
                                        Update Result
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="fixtures.last_page > 1" class="mt-6 flex items-center justify-center gap-2">
                <Link v-if="fixtures.prev_page_url" :href="fixtures.prev_page_url" class="flex h-9 items-center rounded-lg border border-border px-4 text-sm font-medium text-foreground hover:bg-muted transition-colors">← Prev</Link>
                <span class="px-4 text-sm text-muted-foreground">{{ fixtures.current_page }} / {{ fixtures.last_page }}</span>
                <Link v-if="fixtures.next_page_url" :href="fixtures.next_page_url" class="flex h-9 items-center rounded-lg border border-border px-4 text-sm font-medium text-foreground hover:bg-muted transition-colors">Next →</Link>
            </div>
        </div>

        <!-- Update Result Modal -->
        <Teleport to="body">
            <div v-if="editingFixture" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="editingFixture = null" />
                <div class="relative w-full max-w-sm rounded-2xl border border-border bg-card p-6 shadow-2xl">
                    <h3 class="font-display font-bold text-xl uppercase tracking-wide text-foreground mb-1">Update Result</h3>
                    <p class="text-sm text-muted-foreground mb-5">
                        {{ editingFixture.home_team.name }} vs {{ editingFixture.away_team.name }}
                    </p>

                    <form @submit.prevent="submitResult" class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-muted-foreground mb-1">{{ editingFixture.home_team.name }}</label>
                                <input v-model.number="resultForm.home_score" @input="onScoreChange" type="number" min="0" max="20" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-center font-display font-black text-xl text-foreground focus:border-pitch focus:outline-none" />
                            </div>
                            <span class="font-display font-black text-2xl text-muted-foreground/30 mt-5">–</span>
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-muted-foreground mb-1">{{ editingFixture.away_team.name }}</label>
                                <input v-model.number="resultForm.away_score" @input="onScoreChange" type="number" min="0" max="20" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-center font-display font-black text-xl text-foreground focus:border-pitch focus:outline-none" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-muted-foreground mb-1">Winner</label>
                            <select v-model="resultForm.winner" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm text-foreground focus:border-pitch focus:outline-none">
                                <option value="home">{{ editingFixture.home_team.name }} win</option>
                                <option value="draw">Draw</option>
                                <option value="away">{{ editingFixture.away_team.name }} win</option>
                            </select>
                            <p v-if="resultForm.home_score === resultForm.away_score" class="mt-1 text-[11px] text-muted-foreground">Level score — pick the winner if it was decided on penalties.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-muted-foreground mb-1">Status</label>
                            <select v-model="resultForm.status" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm text-foreground focus:border-pitch focus:outline-none">
                                <option value="live">Live</option>
                                <option value="finished">Finished</option>
                            </select>
                        </div>

                        <div class="flex gap-2">
                            <button type="button" class="flex-1 h-10 rounded-lg border border-border text-sm font-medium text-foreground hover:bg-muted transition-colors" @click="editingFixture = null">Cancel</button>
                            <button type="submit" :disabled="resultForm.processing" class="flex-1 h-10 rounded-lg bg-pitch text-sm font-bold text-pitch-foreground hover:bg-pitch/90 disabled:opacity-50 transition-colors">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Set Teams Modal (fill in TBD / knockout slots) -->
        <Teleport to="body">
            <div v-if="teamsFixture" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="teamsFixture = null" />
                <div class="relative w-full max-w-sm rounded-2xl border border-border bg-card p-6 shadow-2xl">
                    <h3 class="font-display font-bold text-xl uppercase tracking-wide text-foreground mb-1">Set Teams</h3>
                    <p class="text-sm text-muted-foreground mb-5">Assign the two teams for this fixture.</p>

                    <form @submit.prevent="submitTeams" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-muted-foreground mb-1">Home team</label>
                            <select v-model.number="teamsForm.home_team_id" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm text-foreground focus:border-pitch focus:outline-none">
                                <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-muted-foreground mb-1">Away team</label>
                            <select v-model.number="teamsForm.away_team_id" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm text-foreground focus:border-pitch focus:outline-none">
                                <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                            </select>
                        </div>
                        <p v-if="teamsForm.errors.away_team_id" class="text-xs text-destructive">{{ teamsForm.errors.away_team_id }}</p>

                        <div class="flex gap-2">
                            <button type="button" class="flex-1 h-10 rounded-lg border border-border text-sm font-medium text-foreground hover:bg-muted transition-colors" @click="teamsFixture = null">Cancel</button>
                            <button type="submit" :disabled="teamsForm.processing" class="flex-1 h-10 rounded-lg bg-pitch text-sm font-bold text-pitch-foreground hover:bg-pitch/90 disabled:opacity-50 transition-colors">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
