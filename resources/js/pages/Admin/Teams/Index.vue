<script setup lang="ts">
import { countryFlag as flagEmoji } from '@/lib/flag'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { computed } from 'vue'
import flagsData from '@/data/flags.json'

interface Team {
    id: number
    name: string
    country_code: string
    group?: string | null
    group_position?: number | null
}

const props = defineProps<{
    teams: Team[]
}>()

function getFlagUrl(code: string): string {
    if (!code) return 'https://flagcdn.com/w320/un.png'
    const cleanCode = code.toUpperCase()
    const mapped = (flagsData as Record<string, { flag_url: string }>)[cleanCode]
    return mapped ? mapped.flag_url : 'https://flagcdn.com/w320/un.png'
}

// Group teams by their group letter; teams without a group go under "Other".
const grouped = computed(() => {
    const map: Record<string, Team[]> = {}
    for (const team of props.teams) {
        const key = team.group || 'Other'
        ;(map[key] ??= []).push(team)
    }
    for (const key of Object.keys(map)) {
        map[key].sort((a, b) => (a.group_position ?? 99) - (b.group_position ?? 99) || a.name.localeCompare(b.name))
    }
    return Object.keys(map)
        .sort((a, b) => (a === 'Other' ? 1 : b === 'Other' ? -1 : a.localeCompare(b)))
        .map(key => ({ key, teams: map[key] }))
})
</script>

<template>
    <AdminLayout>
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="font-display text-3xl font-black uppercase tracking-wide text-foreground">Teams</h1>
                <span class="text-sm text-muted-foreground">{{ teams.length }} total</span>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="block in grouped" :key="block.key" class="rounded-xl border border-border bg-card overflow-hidden">
                    <div class="border-b border-border bg-muted/20 px-4 py-2.5">
                        <h2 class="font-display font-bold text-sm uppercase tracking-widest text-muted-foreground">
                            {{ block.key === 'Other' ? 'Unassigned' : `Group ${block.key}` }}
                        </h2>
                    </div>
                    <div class="divide-y divide-border">
                        <div v-for="team in block.teams" :key="team.id" class="flex items-center gap-3 px-4 py-2.5">
                            <span v-if="team.country_code === 'TBD'" class="text-xl leading-none">🏳️</span>
                            <img v-else :src="getFlagUrl(team.country_code)" :alt="team.name" class="h-5 w-7 object-cover rounded shadow-xs border border-border inline-block" />
                            <span class="min-w-0 flex-1 truncate text-sm font-medium text-foreground">{{ team.name }}</span>
                            <span class="shrink-0 rounded bg-muted px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-muted-foreground">{{ team.country_code }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
