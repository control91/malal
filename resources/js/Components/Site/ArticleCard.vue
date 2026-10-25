<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import { formatDate } from '@/format';

defineProps({ article: { type: Object, required: true } });
</script>

<template>
  <article class="group relative flex h-full flex-col overflow-hidden rounded-[var(--radius-card)] border border-white/10 bg-white/[0.03] transition duration-300 hover:-translate-y-1 hover:border-primary/50" data-reveal>
    <div class="relative aspect-[16/9] overflow-hidden bg-gradient-to-br from-ink via-ink-800 to-primary-700">
      <img v-if="article.cover" :src="article.cover" :alt="article.cover_alt" class="size-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" width="640" height="360">
      <template v-else><div class="grid-pattern absolute inset-0" /><div class="absolute inset-0 grid place-items-center text-white/40"><Icon name="newspaper" size="size-10" /></div></template>
    </div>
    <div class="flex flex-1 flex-col p-6">
      <div class="flex items-center gap-3 text-xs text-slate-500">
        <template v-if="article.category"><span class="font-semibold text-primary-400">{{ article.category.name }}</span><span aria-hidden="true">·</span></template>
        <time :datetime="article.published_at">{{ formatDate(article.published_at) }}</time><span aria-hidden="true">·</span><span>{{ article.reading_time }} min</span>
      </div>
      <h3 class="mt-3 font-display text-lg leading-snug"><Link :href="article.url" class="after:absolute after:inset-0">{{ article.title }}</Link></h3>
      <p v-if="article.excerpt" class="mt-2.5 line-clamp-3 flex-1 text-sm leading-relaxed text-slate-400">{{ article.excerpt }}</p>
    </div>
  </article>
</template>
