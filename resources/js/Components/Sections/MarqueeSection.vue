<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ section: { type: Object, required: true } });
</script>

<template>
  <section v-if="section.data?.length" class="overflow-hidden border-y border-white/10 bg-night-800/60 py-7" :aria-label="section.title || 'Nos domaines'">
    <div class="marquee" aria-hidden="true">
      <template v-for="copy in 2" :key="copy">
        <Link v-for="(domain, i) in section.data" :key="`${copy}-${domain.id}`" :href="domain.url" tabindex="-1" class="flex items-center gap-12 font-display text-3xl transition hover:text-primary-400 sm:text-5xl" :class="i % 2 ? 'text-outline' : 'text-white'">
          <span>{{ domain.name }}</span>
          <svg viewBox="0 0 24 24" class="size-6 shrink-0 text-primary-400 sm:size-8" fill="currentColor"><path d="M12 0l2.6 9.4L24 12l-9.4 2.6L12 24l-2.6-9.4L0 12l9.4-2.6z" /></svg>
        </Link>
      </template>
    </div>
    <ul class="sr-only"><li v-for="domain in section.data" :key="domain.id"><Link :href="domain.url">{{ domain.name }}</Link></li></ul>
  </section>
</template>
