<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLogoIcon from '@/components/AppLogoIcon.vue'
import { logout } from '@/routes'
import { computed, ref } from 'vue'

const page = usePage()
const auth = computed(() => page.props.auth as { user?: { name: string } } | undefined)
const sidebarOpen = ref(false)

function handleLogout() {
    router.flushAll()
}

const navItems = [
    { label: 'Dashboard', href: '/admin', icon: 'grid' },
    { label: 'Fixtures', href: '/admin/fixtures', icon: 'calendar' },
    { label: 'Users', href: '/admin/users', icon: 'users' },
    { label: 'Teams', href: '/admin/teams', icon: 'shield' },
    { label: 'Advertisements', href: '/admin/advertisements', icon: 'megaphone' },
]

function isActive(href: string): boolean {
    if (href === '/admin') return page.url === '/admin'
    return page.url.startsWith(href)
}
</script>

<template>
    <div class="min-h-screen bg-background flex">
        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-60 flex-col border-r border-border bg-card transition-transform lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Logo -->
            <div class="flex h-16 shrink-0 items-center gap-3 border-b border-border px-5">
                <AppLogoIcon class="h-8 w-auto shrink-0 rounded ring-1 ring-border" />
                <div>
                    <div class="font-display font-bold text-sm leading-tight uppercase text-foreground tracking-wide">
                        Muslim Youth League
                    </div>
                    <div class="text-[9px] font-bold uppercase tracking-widest text-pitch">Admin Panel</div>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                    :class="isActive(item.href)
                        ? 'bg-pitch/10 text-pitch'
                        : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                    @click="sidebarOpen = false"
                >
                    <!-- Grid -->
                    <svg v-if="item.icon === 'grid'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    <!-- Calendar -->
                    <svg v-else-if="item.icon === 'calendar'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                    </svg>
                    <!-- Users -->
                    <svg v-else-if="item.icon === 'users'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <!-- Shield -->
                    <svg v-else-if="item.icon === 'shield'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <!-- Megaphone -->
                    <svg v-else-if="item.icon === 'megaphone'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>
                    </svg>
                    {{ item.label }}
                </Link>
            </nav>

            <!-- Footer: back to site + user -->
            <div class="border-t border-border p-3 space-y-1">
                <Link
                    href="/"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to site
                </Link>
                <div v-if="auth?.user" class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs text-muted-foreground">
                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-pitch/20 text-[10px] font-bold text-pitch">
                        {{ auth.user.name.charAt(0).toUpperCase() }}
                    </div>
                    <span class="truncate">{{ auth.user.name }}</span>
                </div>
                <Link
                    :href="logout()"
                    @click="handleLogout"
                    as="button"
                    data-test="logout-button"
                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>
                    </svg>
                    Log out
                </Link>
            </div>
        </aside>

        <!-- Mobile overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-black/40 lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Main area -->
        <div class="flex min-w-0 flex-1 flex-col lg:pl-60">
            <!-- Top bar (mobile only) -->
            <header class="flex h-14 shrink-0 items-center gap-3 border-b border-border bg-card px-4 lg:hidden">
                <button
                    class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted transition-colors"
                    @click="sidebarOpen = true"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <span class="font-display font-bold text-sm uppercase tracking-wide text-foreground">
                    WC<span class="text-pitch">2026</span> Admin
                </span>
            </header>

            <!-- Flash -->
            <div v-if="$page.props.flash?.success" class="mx-6 mt-4">
                <div class="flex items-center gap-2 rounded-lg border border-pitch/30 bg-pitch/10 px-4 py-2.5 text-sm font-medium text-pitch">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ ($page.props.flash as any).success }}
                </div>
            </div>

            <main class="flex-1 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
