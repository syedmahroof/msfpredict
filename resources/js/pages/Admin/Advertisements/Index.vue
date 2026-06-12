<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Link, router } from '@inertiajs/vue3'

interface Advertisement {
    id: number
    title: string
    image_url: string
    link_url?: string | null
    placement: string
    is_active: boolean
    sort_order: number
}

defineProps<{
    advertisements: Advertisement[]
}>()

function placementLabel(placement: string): string {
    if (placement === 'home_hero') return 'Home — below hero'
    return placement
}

function destroy(advertisement: Advertisement) {
    if (!confirm(`Delete “${advertisement.title}”?`)) return
    router.delete(`/admin/advertisements/${advertisement.id}`, { preserveScroll: true })
}
</script>

<template>
    <AdminLayout>
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <h1 class="font-display text-3xl font-black uppercase tracking-wide text-foreground">Advertisements</h1>
                <Link href="/admin/advertisements/create" class="flex h-9 items-center rounded-lg bg-pitch px-4 text-sm font-bold text-pitch-foreground hover:bg-pitch/90 transition-colors">
                    + Add Advertisement
                </Link>
            </div>

            <!-- Empty state -->
            <div v-if="advertisements.length === 0" class="rounded-xl border border-dashed border-border p-12 text-center">
                <div class="mb-3 text-4xl">📢</div>
                <p class="text-sm text-muted-foreground">No advertisements yet. Add one to fill the home page slot.</p>
            </div>

            <!-- Table -->
            <div v-else class="rounded-xl border border-border bg-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/20">
                                <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Banner</th>
                                <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Title</th>
                                <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted-foreground hidden sm:table-cell">Placement</th>
                                <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted-foreground hidden sm:table-cell">Order</th>
                                <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Status</th>
                                <th class="px-5 py-3" />
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="ad in advertisements" :key="ad.id" class="hover:bg-muted/10 transition-colors">
                                <td class="px-5 py-3">
                                    <img :src="ad.image_url" :alt="ad.title" class="h-10 w-20 rounded border border-border object-cover" />
                                </td>
                                <td class="px-5 py-3">
                                    <div class="font-medium text-foreground">{{ ad.title }}</div>
                                    <a v-if="ad.link_url" :href="ad.link_url" target="_blank" rel="noopener" class="text-xs text-muted-foreground hover:text-pitch hover:underline">{{ ad.link_url }}</a>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground hidden sm:table-cell text-xs">{{ placementLabel(ad.placement) }}</td>
                                <td class="px-5 py-3 text-muted-foreground hidden sm:table-cell tabular-nums">{{ ad.sort_order }}</td>
                                <td class="px-5 py-3">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider"
                                        :class="ad.is_active ? 'bg-pitch/15 text-pitch' : 'bg-muted text-muted-foreground'"
                                    >
                                        {{ ad.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    <Link :href="`/admin/advertisements/${ad.id}/edit`" class="text-xs font-medium text-muted-foreground hover:text-foreground hover:underline mr-3">
                                        Edit
                                    </Link>
                                    <button class="text-xs font-medium text-destructive hover:underline" @click="destroy(ad)">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="mt-4 text-xs text-muted-foreground">
                The active advertisement with the lowest order is shown in the home page slot below the hero banner.
            </p>
        </div>
    </AdminLayout>
</template>
