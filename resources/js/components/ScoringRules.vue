<script setup lang="ts">
import { computed } from 'vue'

interface ScoringRule {
    exact_score_points: number
    correct_winner_points: number
    correct_draw_points: number
    correct_goal_difference_points: number
    correct_one_team_score_points: number
    knockout_multiplier: number
}

const props = defineProps<{
    scoringRule?: ScoringRule | null
}>()

const scoringRows = computed(() => {
    const r = props.scoringRule
    if (!r) return []
    return [
        { label: 'Exact score', desc: 'Win, Right score', points: `${r.exact_score_points}` },
        { label: 'Correct winner', desc: 'Right team wins', points: `${r.correct_winner_points}` },
        { label: 'Goal-difference bonus', desc: 'Correct winner + exact margin', points: `+${r.correct_goal_difference_points}` },
        { label: 'Correct draw', desc: 'Predicted a draw correctly', points: `${r.correct_draw_points}` },
        { label: 'One team’s score', desc: 'Got one side’s goals right', points: `${r.correct_one_team_score_points}` },
    ]
})
</script>

<template>
    <div v-if="scoringRule" class="rounded-xl border border-border bg-card p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-display text-lg font-bold uppercase tracking-wide text-foreground">How Points Work</h2>
            <span class="rounded-full bg-pitch/15 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider text-pitch">
                Knockouts × {{ scoringRule.knockout_multiplier }}
            </span>
        </div>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="row in scoringRows"
                :key="row.label"
                class="flex items-center gap-3 rounded-lg border border-border bg-muted/30 px-3 py-2.5"
            >
                <span class="flex h-9 min-w-9 items-center justify-center rounded-lg bg-pitch/15 px-2 font-display text-base font-black tabular-nums text-pitch">
                    {{ row.points }}
                </span>
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-foreground">{{ row.label }}</div>
                    <div class="text-[11px] text-muted-foreground">{{ row.desc }}</div>
                </div>
            </div>
        </div>
        <div class="mt-4 border-t border-border/50 pt-3 text-[11px] text-muted-foreground leading-relaxed space-y-1.5">
            <p>Points are awarded once a match kicks off and the final result is confirmed. Knockout matches are worth double.</p>
            <p class="font-medium text-foreground/80">
                <span class="font-bold text-pitch uppercase tracking-wider text-[9px] mr-1">Knockout Draws:</span>
                If predicting a draw (e.g. 1–1) in a knockout match, you must select the team to advance. To earn exact score points (20 pts), both the score and the selected advancing team must match. Score matches but wrong team advances: 6 pts. Score mismatches but correct team advances: 14 pts.
            </p>
        </div>
    </div>
</template>
