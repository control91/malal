<script setup>
import { computed } from 'vue';
import icons from '@/icons';

const props = defineProps({
    name: { type: String, default: '' },
    size: { type: String, default: 'size-5' },
    stroke: { type: [String, Number], default: 1.75 },
    label: { type: String, default: '' },
});
const icon = computed(() => icons[props.name] || icons['circle-dot']);
</script>

<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    :class="size"
    :fill="icon.filled ? 'currentColor' : 'none'"
    :stroke="icon.filled ? 'none' : 'currentColor'"
    :stroke-width="icon.filled ? undefined : stroke"
    stroke-linecap="round"
    stroke-linejoin="round"
    :role="label ? 'img' : undefined"
    :aria-label="label || undefined"
    :aria-hidden="label ? undefined : 'true'"
    focusable="false"
    v-html="icon.body"
  />
</template>
