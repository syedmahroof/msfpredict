<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img
        src="/images/muslim-league-flag.png"
        alt="Muslim Youth League"
        :class="className"
        v-bind="$attrs"
    />
</template>
