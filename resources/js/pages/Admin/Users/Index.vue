<script setup lang="ts">
import { countryFlag as flagEmoji } from '@/lib/flag'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Link } from '@inertiajs/vue3'

interface User {
    id: number
    name: string
    email: string
    username?: string
    country_code?: string
    is_admin: boolean
    predictions_count: number
    created_at: string
}

interface Paginated<T> {
    data: T[]
    current_page: number
    last_page: number
    next_page_url?: string
    prev_page_url?: string
    total: number
}

defineProps<{
    users: Paginated<User>
}>()

</script>

<template>
    <AdminLayout>
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="font-display text-3xl font-black uppercase tracking-wide text-foreground">Users</h1>
                <span class="text-sm text-muted-foreground">{{ users.total }} total</span>
            </div>

            <div class="rounded-xl border border-border bg-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/20">
                                <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted-foreground">User</th>
                                <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted-foreground hidden sm:table-cell">Email</th>
                                <th class="px-5 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Country</th>
                                <th class="px-5 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Picks</th>
                                <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted-foreground hidden sm:table-cell">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-muted/10 transition-colors">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-bold text-foreground ring-1 ring-border">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <Link :href="`/admin/users/${user.id}`" class="truncate font-medium text-foreground hover:text-pitch hover:underline">{{ user.name }}</Link>
                                                <span v-if="user.is_admin" class="shrink-0 rounded bg-pitch/15 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-pitch">Admin</span>
                                            </div>
                                            <div v-if="user.username" class="text-[11px] text-muted-foreground truncate">@{{ user.username }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground hidden sm:table-cell">{{ user.email }}</td>
                                <td class="px-5 py-3 text-center text-base">{{ user.country_code ? flagEmoji(user.country_code) : '—' }}</td>
                                <td class="px-5 py-3 text-center font-semibold text-foreground tabular-nums">{{ user.predictions_count }}</td>
                                <td class="px-5 py-3 text-muted-foreground hidden sm:table-cell text-xs">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="users.last_page > 1" class="mt-6 flex items-center justify-center gap-2">
                <Link v-if="users.prev_page_url" :href="users.prev_page_url" class="flex h-9 items-center rounded-lg border border-border px-4 text-sm font-medium text-foreground hover:bg-muted transition-colors">← Prev</Link>
                <span class="px-4 text-sm text-muted-foreground">{{ users.current_page }} / {{ users.last_page }}</span>
                <Link v-if="users.next_page_url" :href="users.next_page_url" class="flex h-9 items-center rounded-lg border border-border px-4 text-sm font-medium text-foreground hover:bg-muted transition-colors">Next →</Link>
            </div>
        </div>
    </AdminLayout>
</template>
