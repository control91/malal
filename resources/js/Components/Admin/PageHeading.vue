<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';

defineProps({ title: String, subtitle: String, icon: String, back: Object });
</script>

<template>
  <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
      <Link v-if="back" :href="back.url" class="mb-3 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-primary"><Icon name="arrow-left" size="size-4" />{{ back.label }}</Link>
      <h1 class="flex items-center gap-3 font-display text-2xl font-bold text-ink sm:text-3xl">
        <span v-if="icon" class="grid size-10 place-items-center rounded-xl bg-white text-primary shadow-sm ring-1 ring-line"><Icon :name="icon" /></span>
        <span class="truncate">{{ title }}</span>
      </h1>
      <p v-if="subtitle" class="mt-2 max-w-3xl text-sm text-slate-500">{{ subtitle }}</p>
    </div>
    <div v-if="$slots.default" class="flex shrink-0 flex-wrap gap-2"><slot /></div>
  </div>
</template>
