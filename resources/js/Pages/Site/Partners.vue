<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import PageHeader from '@/Components/Site/PageHeader.vue';

defineProps({ partners: Array });
</script>

<template>
  <PageHeader eyebrow="Écosystème" title="Nos *partenaires*" subtitle="Nous ne mentionnons que des partenariats formalisés, avec l'accord de nos partenaires." />
  <section class="section-sm">
    <div class="container-x grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <article v-for="p in partners" :key="p.id" class="card"><img v-if="p.logo" :src="p.logo" :alt="p.name" class="h-10 w-auto" loading="lazy" height="40"><p v-else class="font-display text-xl text-white">{{ p.name }}</p><p class="mt-3 text-xs font-semibold uppercase tracking-wider text-primary-400">{{ p.kind }}</p><p v-if="p.description" class="mt-2 text-sm text-slate-400">{{ p.description }}</p><a v-if="p.url" :href="p.url" class="link-arrow mt-4" target="_blank" rel="noopener">Site web <Icon name="external-link" size="size-4" /></a></article>
      <div v-if="!partners.length" class="col-span-full rounded-[var(--radius-card)] border border-dashed border-white/15 p-12 text-center"><p class="font-semibold text-white">Notre programme de partenariats est en construction.</p><p class="mt-2 text-sm text-slate-500">Éditeur, intégrateur, opérateur, université ou institution : discutons de collaborations possibles.</p><Link :href="route('contact', { type: 'partnership' })" class="btn btn-primary mt-6">Proposer un partenariat</Link></div>
    </div>
  </section>
</template>
