<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';

defineProps({ item: { type: Object, required: true } });
</script>

<template>
  <article class="group relative flex h-full flex-col overflow-hidden rounded-[var(--radius-card)] border border-white/10 bg-white/[0.03] transition duration-300 hover:-translate-y-1 hover:border-primary/50" data-reveal>
    <div class="relative aspect-[16/10] overflow-hidden bg-gradient-to-br from-ink to-primary-700">
      <img v-if="item.cover" :src="item.cover" alt="" class="size-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" width="640" height="400">
      <template v-else><div class="grid-pattern absolute inset-0" /><div class="absolute inset-0 grid place-items-center text-white/40"><Icon name="award" size="size-10" /></div></template>
      <div class="absolute left-4 top-4 flex gap-2"><span class="badge bg-white/90 text-ink">{{ item.sector }}</span><span v-if="item.is_example" class="badge badge-example">Exemple illustratif</span></div>
    </div>
    <div class="flex flex-1 flex-col p-6">
      <h3 class="font-display text-lg"><Link :href="item.url" class="after:absolute after:inset-0">{{ item.title }}</Link></h3>
      <p class="mt-2 flex-1 text-sm leading-relaxed text-slate-400">{{ item.summary }}</p>
      <div v-if="item.technologies.length" class="mt-4 flex flex-wrap gap-1.5"><span v-for="tech in item.technologies.slice(0, 4)" :key="tech" class="chip">{{ tech }}</span></div>
    </div>
  </article>
</template>
