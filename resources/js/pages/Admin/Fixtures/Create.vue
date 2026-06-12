<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'

interface Option {
    id: number
    name: string
    country_code?: string
    city?: string
}

const props = defineProps<{
    tournaments: Option[]
    teams: Option[]
    stadiums: Option[]
    rounds: string[]
}>()

const form = useForm<{
    tournament_id: number | null
    home_team_id: number | null
    away_team_id: number | null
    stadium_id: number | null
    round: string
    group: string
    match_day: number | null
    scheduled_at: string
}>({
    tournament_id: props.tournaments[0]?.id ?? null,
    home_team_id: null,
    away_team_id: null,
    stadium_id: null,
    round: props.rounds[0] ?? 'group_stage',
    group: '',
    match_day: null,
    scheduled_at: '',
})

function formatRound(round: string): string {
    return round.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function submit() {
    form.transform((data) => ({
        ...data,
        group: data.group || null,
    })).post('/admin/fixtures')
}
</script>

<template>
    <AdminLayout>
        <div class="mx-auto max-w-2xl p-6">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="font-display text-3xl font-black uppercase tracking-wide text-foreground">Add Fixture</h1>
                <Link href="/admin/fixtures" class="text-sm font-medium text-muted-foreground hover:text-foreground hover:underline">← Back</Link>
            </div>

            <form @submit.prevent="submit" class="space-y-5 rounded-xl border border-border bg-card p-6">
                <!-- Tournament -->
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-muted-foreground">Tournament</label>
                    <select v-model.number="form.tournament_id" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm text-foreground focus:border-pitch focus:outline-none">
                        <option v-for="t in tournaments" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                    <p v-if="form.errors.tournament_id" class="mt-1 text-xs text-destructive">{{ form.errors.tournament_id }}</p>
                </div>

                <!-- Round -->
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-muted-foreground">Round</label>
                    <select v-model="form.round" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm text-foreground focus:border-pitch focus:outline-none">
                        <option v-for="r in rounds" :key="r" :value="r">{{ formatRound(r) }}</option>
                    </select>
                    <p v-if="form.errors.round" class="mt-1 text-xs text-destructive">{{ form.errors.round }}</p>
                </div>

                <!-- Teams -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-muted-foreground">Home team</label>
                        <select v-model.number="form.home_team_id" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm text-foreground focus:border-pitch focus:outline-none">
                            <option :value="null" disabled>Select…</option>
                            <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                        </select>
                        <p v-if="form.errors.home_team_id" class="mt-1 text-xs text-destructive">{{ form.errors.home_team_id }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-muted-foreground">Away team</label>
                        <select v-model.number="form.away_team_id" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm text-foreground focus:border-pitch focus:outline-none">
                            <option :value="null" disabled>Select…</option>
                            <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                        </select>
                        <p v-if="form.errors.away_team_id" class="mt-1 text-xs text-destructive">{{ form.errors.away_team_id }}</p>
                    </div>
                </div>

                <!-- Stadium -->
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-muted-foreground">Stadium <span class="text-muted-foreground/60">(optional)</span></label>
                    <select v-model.number="form.stadium_id" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm text-foreground focus:border-pitch focus:outline-none">
                        <option :value="null">— None —</option>
                        <option v-for="s in stadiums" :key="s.id" :value="s.id">{{ s.name }}{{ s.city ? ` · ${s.city}` : '' }}</option>
                    </select>
                    <p v-if="form.errors.stadium_id" class="mt-1 text-xs text-destructive">{{ form.errors.stadium_id }}</p>
                </div>

                <!-- Group + Match day -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-muted-foreground">Group <span class="text-muted-foreground/60">(optional)</span></label>
                        <input v-model="form.group" type="text" maxlength="1" placeholder="A" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm uppercase text-foreground focus:border-pitch focus:outline-none" />
                        <p v-if="form.errors.group" class="mt-1 text-xs text-destructive">{{ form.errors.group }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-muted-foreground">Match day <span class="text-muted-foreground/60">(optional)</span></label>
                        <input v-model.number="form.match_day" type="number" min="1" placeholder="1" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm text-foreground focus:border-pitch focus:outline-none" />
                        <p v-if="form.errors.match_day" class="mt-1 text-xs text-destructive">{{ form.errors.match_day }}</p>
                    </div>
                </div>

                <!-- Kick-off -->
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-muted-foreground">Kick-off (UTC)</label>
                    <input v-model="form.scheduled_at" type="datetime-local" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm text-foreground focus:border-pitch focus:outline-none" />
                    <p v-if="form.errors.scheduled_at" class="mt-1 text-xs text-destructive">{{ form.errors.scheduled_at }}</p>
                </div>

                <div class="flex gap-3 pt-2">
                    <Link href="/admin/fixtures" class="flex h-11 flex-1 items-center justify-center rounded-lg border border-border text-sm font-medium text-foreground hover:bg-muted transition-colors">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="flex h-11 flex-1 items-center justify-center rounded-lg bg-pitch text-sm font-bold text-pitch-foreground hover:bg-pitch/90 disabled:opacity-50 transition-colors">
                        Create Fixture
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
