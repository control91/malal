<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import PageHeading from '@/Components/Admin/PageHeading.vue';

const props = defineProps({ days: Number, series: Array, totals: Object, topPages: Array, inquiries: Number, byKind: Array, won: Number });
const peak = computed(() => Math.max(1, ...(props.series ?? []).map((d) => d.views)));
</script>

<template>
  <Head title="Statistiques" />
  <PageHeading title="Statistiques" subtitle="Mesure d'audience interne, sans cookie ni service tiers : compteurs agrégés par jour, visiteurs pseudonymisés (empreinte quotidienne non réversible). Les robots, l'équipe connectée et les visiteurs refusant le suivi (DNT/GPC) sont exclus." icon="chart-line" />
  <div class="mb-6 flex gap-1"><Link :href="route('admin.analytics', { periode: 30 })" class="tab" :class="{ 'is-active': days === 30 }">30 jours</Link><Link :href="route('admin.analytics', { periode: 90 })" class="tab" :class="{ 'is-active': days === 90 }">90 jours</Link></div>
  <template v-if="series">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div class="stat-card"><p class="text-sm text-slate-500">Visiteurs uniques / jour (cumul)</p><p class="stat-value mt-2">{{ totals.visitors }}</p></div>
      <div class="stat-card"><p class="text-sm text-slate-500">Pages vues</p><p class="stat-value mt-2">{{ totals.views }}</p></div>
      <div class="stat-card"><p class="text-sm text-slate-500">Demandes reçues</p><p class="stat-value mt-2">{{ inquiries }}</p></div>
      <div class="stat-card"><p class="text-sm text-slate-500">Clients convertis</p><p class="stat-value mt-2">{{ won }}</p></div>
    </div>
    <section class="dash-card mt-6">
      <div class="dash-card-header"><h2 class="dash-card-title">Audience quotidienne</h2><div class="flex items-center gap-4 text-xs text-slate-500"><span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-primary/80" />Pages vues</span><span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-accent" />Visiteurs</span></div></div>
      <div class="p-5">
        <div class="flex h-56 items-end gap-[3px]" role="img" aria-label="Pages vues et visiteurs par jour">
          <div v-for="d in series" :key="d.date" class="group relative flex h-full flex-1 items-end gap-px">
            <div class="bar" :style="{ height: d.views ? `${(d.views * 100) / peak}%` : '2px' }" /><div class="w-full rounded-t-md bg-accent" :style="{ height: d.visitors ? `${(d.visitors * 100) / peak}%` : '2px' }" />
            <span class="pointer-events-none absolute -top-10 left-1/2 z-10 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-ink px-2 py-1 text-[11px] text-white group-hover:block">{{ d.date }} · {{ d.views }} vues · {{ d.visitors }} visiteurs</span>
          </div>
        </div>
      </div>
    </section>
    <div class="mt-6 grid gap-6 xl:grid-cols-2">
      <section class="dash-card"><div class="dash-card-header"><h2 class="dash-card-title">Pages les plus vues</h2></div><table class="table"><tbody><tr v-for="row in topPages" :key="row.path"><td class="max-w-0 truncate"><a :href="row.path" target="_blank" class="text-ink hover:text-primary">{{ row.path }}</a></td><td class="w-24 text-right font-semibold">{{ row.total }}</td></tr></tbody></table></section>
      <section class="dash-card"><div class="dash-card-header"><h2 class="dash-card-title">Demandes par type</h2></div><ul class="space-y-3 p-5"><li v-for="k in byKind" :key="k.label" class="flex items-center justify-between text-sm"><span>{{ k.label }}</span><span class="font-semibold text-ink">{{ k.count }}</span></li><li v-if="!byKind.length" class="text-sm text-slate-400">Aucune demande sur la période.</li></ul></section>
    </div>
  </template>
  <div v-else class="dash-card px-6 py-16 text-center"><span class="mx-auto grid size-14 place-items-center rounded-2xl bg-surface text-slate-400"><Icon name="chart-line" size="size-6" /></span><p class="mt-4 font-semibold text-ink">Aucune donnée d'audience pour le moment</p><p class="mt-1 text-sm text-slate-500">Les statistiques apparaîtront dès les premières visites du site public (les visites de l'équipe connectée ne sont pas comptées).</p></div>
</template>
