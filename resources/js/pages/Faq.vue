<script setup lang="ts">
import WorldCupLayout from '@/layouts/WorldCupLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'

interface Faq {
    q: string
    a: string
}

const faqs: Faq[] = [
    {
        q: 'How do predictions work?',
        a: 'For every match you predict the final score (e.g. 2–1). You earn points based on how close your prediction is to the actual result once the match is played.',
    },
    {
        q: 'When can I make a prediction?',
        a: 'Predictions open 24 hours before kick-off and close the moment the match starts. On the Predict page you’ll only see matches that are inside that 24-hour window.',
    },
    {
        q: 'Can I change my prediction?',
        a: 'Yes — you can update your pick as many times as you like until the match kicks off. Once it starts, your prediction is locked.',
    },
    {
        q: 'How are points calculated?',
        a: 'Exact score = 10 points · Correct winner = 5 · Correct winner with the exact goal margin = 5 + 2 bonus · Correct draw = 3 · Getting one team’s score right = 1. Knockout matches are worth double.',
    },
    {
        q: 'When are points added to the leaderboard?',
        a: 'Points are awarded once a match finishes and an admin confirms the final result. Until then your prediction shows as pending.',
    },
    {
        q: 'Can I see other people’s predictions?',
        a: 'Everyone’s predictions for a match are revealed after kick-off (so picks can’t be copied beforehand). Open any match to see all predictions and points earned.',
    },
    {
        q: 'How does the leaderboard work?',
        a: 'Everyone competes on a single company-wide leaderboard, ranked by total points. Tap any player to see their predictions and points.',
    },
    {
        q: 'What happens with knockout matches?',
        a: 'All points are doubled in the knockout rounds. If a match is decided on penalties, the admin records the team that advanced as the winner when scoring predictions.',
    },
    {
        q: 'What times are shown for matches?',
        a: 'Match times are shown in your device’s local time zone, so the kick-off you see is when the match starts for you.',
    },
]

const open = ref<number | null>(0)

function toggle(index: number) {
    open.value = open.value === index ? null : index
}
</script>

<template>
    <Head title="FAQ" />
    <WorldCupLayout>
        <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
            <div class="mb-10 text-center">
                <h1 class="font-display text-4xl font-black uppercase tracking-wide text-foreground sm:text-5xl">
                    Frequently Asked <span class="text-pitch">Questions</span>
                </h1>
                <p class="mt-3 text-muted-foreground">Everything you need to know about the prediction game.</p>
            </div>

            <div class="space-y-3">
                <div
                    v-for="(faq, index) in faqs"
                    :key="index"
                    class="overflow-hidden rounded-xl border border-border bg-card"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-muted/30"
                        @click="toggle(index)"
                    >
                        <span class="font-semibold text-foreground">{{ faq.q }}</span>
                        <svg
                            class="h-4 w-4 shrink-0 text-muted-foreground transition-transform"
                            :class="open === index ? 'rotate-180' : ''"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div v-if="open === index" class="border-t border-border px-5 py-4 text-sm leading-relaxed text-muted-foreground">
                        {{ faq.a }}
                    </div>
                </div>
            </div>
        </div>
    </WorldCupLayout>
</template>
