<script setup>
/** Displays HTML that was sanitised server-side (HTMLPurifier) when it was saved. */
defineProps({ html: { type: String, default: '' }, tag: { type: String, default: 'div' } });
</script>

<template>
  <component :is="tag" v-if="html" class="prose-brand" v-html="html" />
</template>
