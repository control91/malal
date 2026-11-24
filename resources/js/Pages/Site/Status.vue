<script setup>
import Icon from '@/Components/Ui/Icon.vue';
import PageHeader from '@/Components/Site/PageHeader.vue';
import { formatDateTime } from '@/format';

defineProps({ overall: String, updated_at: String, components: Array });
const tone = { operational: 'bg-emerald-400/10 text-emerald-300 ring-emerald-400/30', outage: 'bg-rose-400/10 text-rose-300 ring-rose-400/30', degraded: 'bg-amber-400/10 text-amber-300 ring-amber-400/30', maintenance: 'bg-amber-400/10 text-amber-300 ring-amber-400/30' };
const summary = { operational: ['circle-check', 'Tous les services sont opérationnels'], outage: ['octagon-alert', 'Incident en cours'], maintenance: ['settings', 'Maintenance en cours'], degraded: ['triangle-alert', 'Performances dégradées'] };
</script>

<template>
  <PageHeader eyebrow="Statut" title="Statut des services" subtitle="État des services en production, mis à jour manuellement par notre équipe." />
  <section class="section-sm">
    <div class="container-x max-w-3xl">
      <template v-if="components.length">
        <div class="alert text-base font-semibold" :class="overall === 'operational' ? 'alert-success' : overall === 'outage' ? 'alert-error' : 'alert-warning'"><Icon :name="summary[overall][0]" /> {{ summary[overall][1] }}</div>
        <ul class="mt-6 divide-y divide-white/10 rounded-[var(--radius-card)] border border-white/10 bg-white/[0.02]">
          <li v-for="c in components" :key="c.name" class="flex items-start justify-between gap-4 px-6 py-5"><span><span class="block font-semibold text-white">{{ c.name }}</span><span v-if="c.description" class="text-sm text-slate-500">{{ c.description }}</span><span v-if="c.message" class="mt-2 block text-sm text-slate-300">{{ c.message }}</span></span><span class="status-pill shrink-0" :class="tone[c.state]">{{ c.state_label }}</span></li>
        </ul>
        <p class="mt-4 text-xs text-slate-500">Dernière mise à jour : {{ formatDateTime(updated_at) }}. Cette page n'est pas une supervision automatique.</p>
      </template>
      <div v-else class="rounded-[var(--radius-card)] border border-dashed border-white/15 p-12 text-center text-slate-500">La page de statut sera disponible avec l'ouverture de nos services en production.</div>
    </div>
  </section>
</template>
