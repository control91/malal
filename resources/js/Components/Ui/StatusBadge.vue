<script setup>
defineProps({ status: { type: String, required: true } });
const labels = { available: 'Disponible', beta: 'Bêta', coming_soon: 'À venir' };
</script>

<template>
  <span class="badge badge-dot" :class="`badge-${status}`">{{ labels[status] ?? status }}</span>
</template>
