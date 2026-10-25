<script setup>
import { Link } from '@inertiajs/vue3';
import Emphasis from '@/Components/Ui/Emphasis.vue';
import RingVisual from './RingVisual.vue';

defineProps({
    title: { type: String, required: true },
    eyebrow: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    parent: { type: Object, default: null }, // { label, url }
});
</script>

<template>
  <section class="relative isolate overflow-hidden border-b border-white/10">
    <div class="grid-pattern absolute inset-0 -z-10 opacity-60" />
    <div class="glow -top-40 right-0 size-[30rem] bg-primary/20" />
    <div class="pointer-events-none absolute -right-16 top-1/2 -z-10 hidden w-[26rem] -translate-y-1/2 opacity-60 lg:block"><RingVisual /></div>
    <div class="container-x pb-16 pt-14 sm:pb-24 sm:pt-20">
      <div class="max-w-3xl">
        <p v-if="eyebrow" class="eyebrow">{{ eyebrow }}</p>
        <h1 class="mt-5 font-display text-[2.4rem] leading-[1.1] sm:text-6xl"><Emphasis :text="title" /></h1>
        <p v-if="subtitle" class="lead mt-6">{{ subtitle }}</p>
      </div>
      <nav aria-label="Fil d'Ariane" class="mt-10 text-sm text-slate-400">
        <ol class="inline-flex flex-wrap items-center gap-2 rounded-lg border border-white/10 bg-white/[0.03] px-4 py-2">
          <li><Link href="/" class="hover:text-white">Accueil</Link></li>
          <template v-if="parent"><li aria-hidden="true" class="text-primary-400">/</li><li><Link :href="parent.url" class="hover:text-white">{{ parent.label }}</Link></li></template>
          <li aria-hidden="true" class="text-primary-400">/</li><li aria-current="page" class="text-white">{{ title.replaceAll('*', '').slice(0, 60) }}</li>
        </ol>
      </nav>
    </div>
  </section>
</template>
