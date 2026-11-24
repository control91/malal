<script setup>
import { computed } from 'vue';

/** Renders "*words*" written by editors as a gradient highlight — without v-html. */
const props = defineProps({ text: { type: String, default: '' } });
const parts = computed(() => (props.text || '').split(/(\*[^*]+\*)/g).filter(Boolean).map((part) => (
    part.startsWith('*') && part.endsWith('*') ? { em: true, text: part.slice(1, -1) } : { em: false, text: part }
)));
</script>

<template>
  <template v-for="(part, i) in parts" :key="i"><span v-if="part.em" class="text-gradient">{{ part.text }}</span><template v-else>{{ part.text }}</template></template>
</template>
