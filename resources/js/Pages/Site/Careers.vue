<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import PageHeader from '@/Components/Site/PageHeader.vue';

defineProps({ jobs: Array });
</script>

<template>
  <PageHeader eyebrow="Carrières" title="Construisez avec nous le *numérique africain*" subtitle="Nous recherchons des personnes passionnées par le cloud, le logiciel et l'IA, qui veulent avoir un impact concret." />
  <section class="section-sm">
    <div class="container-x max-w-4xl">
      <h2 class="font-display text-2xl">Offres ouvertes</h2>
      <ul class="mt-6 space-y-3">
        <li v-for="job in jobs" :key="job.slug"><Link :href="route('careers.show', job.slug)" class="card card-hover flex flex-col gap-3 !p-6 sm:flex-row sm:items-center sm:justify-between">
          <span><span class="block font-display text-lg text-white">{{ job.title }}</span><span class="mt-1 flex flex-wrap gap-3 text-sm text-slate-500"><span class="flex items-center gap-1.5"><Icon name="map-pin" size="size-4" />{{ job.location }}<template v-if="job.remote_possible"> · télétravail possible</template></span><span class="flex items-center gap-1.5"><Icon name="briefcase" size="size-4" />{{ job.contract }}</span><span v-if="job.department">{{ job.department }}</span></span></span>
          <span class="link-arrow">Voir l'offre <Icon name="arrow-right" size="size-4" /></span>
        </Link></li>
        <li v-if="!jobs.length" class="rounded-[var(--radius-card)] border border-dashed border-white/15 p-10 text-center"><p class="font-semibold text-white">Aucune offre ouverte actuellement.</p><p class="mt-2 text-sm text-slate-500">Les candidatures spontanées sont les bienvenues.</p><Link :href="route('contact', { type: 'career' })" class="btn btn-primary mt-5">Candidature spontanée</Link></li>
      </ul>
    </div>
  </section>
</template>
