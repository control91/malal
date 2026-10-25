<script setup>
import Emphasis from '@/Components/Ui/Emphasis.vue';

defineProps({ section: { type: Object, required: true }, align: { type: String, default: 'center' } });
</script>

<template>
  <div v-if="section.eyebrow || section.title || section.subtitle" :class="align === 'left' ? 'max-w-2xl' : 'mx-auto max-w-3xl text-center'" data-reveal>
    <p v-if="section.eyebrow" class="eyebrow" :class="{ 'justify-center': align !== 'left' }">{{ section.eyebrow }}</p>
    <h2 v-if="section.title" class="h-section mt-4"><Emphasis :text="section.title" /></h2>
    <p v-if="section.subtitle" class="lead mt-5">{{ section.subtitle }}</p>
  </div>
</template>
