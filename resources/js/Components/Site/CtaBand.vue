<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: 'Un projet ? Parlons-en.' },
    text: { type: String, default: 'Un expert revient vers vous pour cadrer votre besoin, sans engagement.' },
});
</script>

<template>
  <section class="section-sm">
    <div class="container-x">
      <div class="dark-zone flex flex-col items-start justify-between gap-8 rounded-[2rem] border border-white/10 px-6 py-12 sm:px-12 lg:flex-row lg:items-center" data-reveal>
        <div class="grid-pattern absolute inset-0 -z-10" /><div class="glow -right-20 -top-20 size-96 bg-primary/40" />
        <div class="max-w-2xl"><h2 class="font-display text-3xl">{{ title }}</h2><p class="mt-3 text-slate-300">{{ text }}</p></div>
        <div class="flex shrink-0 flex-col gap-3 sm:flex-row"><Link :href="route('quote')" class="btn btn-primary btn-lg">Demander un devis</Link><Link :href="route('contact')" class="btn btn-secondary btn-lg">Nous contacter</Link></div>
      </div>
    </div>
  </section>
</template>
