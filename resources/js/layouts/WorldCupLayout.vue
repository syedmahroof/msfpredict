<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLogoIcon from '@/components/AppLogoIcon.vue'
import { logout } from '@/routes'
import { useAppearance } from '@/composables/useAppearance'
import { computed, ref } from 'vue'

const page = usePage()
const auth = computed(() => page.props.auth as { user?: { name: string; is_admin: boolean } } | undefined)
const mobileMenuOpen = ref(false)
const userMenuOpen = ref(false)

const { resolvedAppearance, updateAppearance } = useAppearance()

function toggleTheme() {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark')
}

function handleLogout() {
    userMenuOpen.value = false
    router.flushAll()
}

const navLinks = [
    { label: 'Matches', href: '/fixtures' },
    { label: 'Predict', href: '/predict' },
    { label: 'Leaderboard', href: '/leaderboard' },
]
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background">
        <!-- Navigation -->
        <header class="sticky top-0 z-50 border-b border-border/50 bg-background/90 backdrop-blur-md">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center gap-2.5 group">
                        <AppLogoIcon class="h-9 w-auto shrink-0 rounded shadow-sm ring-1 ring-border transition-transform group-hover:scale-105" />
                        <span class="font-display text-sm font-black uppercase leading-none tracking-tight text-foreground">
                            Muslim Youth<br />League
                        </span>
                        <span class="hidden sm:block border-l border-border pl-2.5 text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">
                            World Cup<br />Predictions
                        </span>
                    </Link>

                    <!-- Desktop Nav -->
                    <nav class="hidden md:flex items-center gap-1">
                        <Link
                            v-for="link in navLinks"
                            :key="link.href"
                            :href="link.href"
                            class="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors rounded-lg hover:bg-muted"
                            :class="{ 'text-foreground bg-muted': $page.url.startsWith(link.href) }"
                        >
                            {{ link.label }}
                        </Link>
                    </nav>

                    <!-- Actions -->
                    <div class="flex items-center gap-3">
                        <!-- Theme toggle -->
                        <button
                            type="button"
                            @click="toggleTheme"
                            class="flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                            :aria-label="resolvedAppearance === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
                            :title="resolvedAppearance === 'dark' ? 'Light mode' : 'Dark mode'"
                        >
                            <!-- Sun (shown in dark mode) -->
                            <svg v-if="resolvedAppearance === 'dark'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="4" />
                                <path stroke-linecap="round" d="M12 2v2m0 16v2M2 12h2m16 0h2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41" />
                            </svg>
                            <!-- Moon (shown in light mode) -->
                            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                            </svg>
                        </button>

                        <template v-if="auth?.user">
                            <Link
                                v-if="auth.user.is_admin"
                                href="/admin"
                                class="hidden sm:flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground transition-colors"
                            >
                                <span class="rounded bg-gold/20 px-1.5 py-0.5 text-[10px] font-bold uppercase text-gold tracking-widest">Admin</span>
                            </Link>
                            <Link
                                href="/my-predictions"
                                class="hidden sm:flex h-9 items-center rounded-lg border border-border px-4 text-sm font-medium text-foreground hover:bg-muted transition-colors"
                            >
                                My Picks
                            </Link>
                            <div class="relative">
                                <button
                                    type="button"
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-muted text-sm font-semibold text-foreground ring-2 ring-pitch/30 transition-shadow hover:ring-pitch/60"
                                    @click="userMenuOpen = !userMenuOpen"
                                >
                                    {{ auth.user.name.charAt(0).toUpperCase() }}
                                </button>

                                <!-- Dropdown -->
                                <div v-if="userMenuOpen">
                                    <div class="fixed inset-0 z-40" @click="userMenuOpen = false" />
                                    <div class="absolute right-0 z-50 mt-2 w-52 overflow-hidden rounded-xl border border-border bg-card shadow-lg">
                                        <div class="border-b border-border px-4 py-3">
                                            <p class="truncate text-sm font-semibold text-foreground">{{ auth.user.name }}</p>
                                        </div>
                                        <div class="py-1">
                                            <Link href="/dashboard" class="block px-4 py-2 text-sm text-foreground hover:bg-muted transition-colors" @click="userMenuOpen = false">Dashboard</Link>
                                            <Link href="/my-predictions" class="block px-4 py-2 text-sm text-foreground hover:bg-muted transition-colors" @click="userMenuOpen = false">My Picks</Link>
                                            <Link v-if="auth.user.is_admin" href="/admin" class="block px-4 py-2 text-sm text-foreground hover:bg-muted transition-colors" @click="userMenuOpen = false">Admin Panel</Link>
                                        </div>
                                        <div class="border-t border-border py-1">
                                            <Link
                                                :href="logout()"
                                                @click="handleLogout"
                                                as="button"
                                                data-test="logout-button"
                                                class="block w-full px-4 py-2 text-left text-sm font-medium text-destructive hover:bg-destructive/10 transition-colors"
                                            >
                                                Log out
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <Link
                                href="/login"
                                class="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
                            >
                                Sign in
                            </Link>
                            <Link
                                href="/register"
                                class="flex h-9 items-center rounded-lg bg-pitch px-4 text-sm font-bold text-pitch-foreground hover:bg-pitch/90 transition-colors"
                            >
                                Sign Up
                            </Link>
                        </template>

                        <!-- Mobile menu button -->
                        <button
                            class="md:hidden flex h-9 w-9 items-center justify-center rounded-lg hover:bg-muted transition-colors"
                            @click="mobileMenuOpen = !mobileMenuOpen"
                        >
                            <svg v-if="!mobileMenuOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div v-if="mobileMenuOpen" class="border-t border-border md:hidden">
                <div class="px-4 py-3 space-y-1">
                    <Link
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                        @click="mobileMenuOpen = false"
                    >
                        {{ link.label }}
                    </Link>
                    <template v-if="auth?.user">
                        <Link href="/dashboard" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition-colors" @click="mobileMenuOpen = false">
                            Dashboard
                        </Link>
                        <Link href="/my-predictions" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition-colors" @click="mobileMenuOpen = false">
                            My Picks
                        </Link>
                        <Link v-if="auth.user.is_admin" href="/admin" class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition-colors" @click="mobileMenuOpen = false">
                            Admin Panel
                        </Link>
                        <Link
                            :href="logout()"
                            @click="handleLogout"
                            as="button"
                            class="flex w-full items-center px-3 py-2.5 rounded-lg text-sm font-medium text-destructive hover:bg-destructive/10 transition-colors"
                        >
                            Log out
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Flash messages -->
        <div v-if="$page.props.flash?.success" class="mx-auto max-w-7xl px-4 pt-4 sm:px-6">
            <div class="flex items-center gap-3 rounded-lg border border-pitch/30 bg-pitch/10 px-4 py-3 text-sm font-medium text-pitch">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ ($page.props.flash as any).success }}
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t border-border bg-muted/30">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
                <div class="flex flex-col items-center gap-4 sm:flex-row sm:justify-between">
                    <div class="flex items-center gap-2 order-1">
                        <AppLogoIcon class="h-7 w-auto shrink-0 rounded ring-1 ring-border" />
                        <span class="font-display text-sm font-black uppercase tracking-tight text-foreground">Muslim Youth Leage Naduvannur Committee</span>
                    </div>
                    <p class="order-3 text-center text-xs text-muted-foreground sm:order-2">© 2026 Muslim Youth Leage Naduvannur Committee · World Cup predictions </p>
                    <div class="order-2 flex gap-4 text-xs text-muted-foreground sm:order-3">
                        <Link href="/faq" class="hover:text-foreground transition-colors">FAQ</Link>
                        <a href="#" class="hover:text-foreground transition-colors">Privacy</a>
                        <a href="#" class="hover:text-foreground transition-colors">Terms</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
