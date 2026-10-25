<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PageHeading from '@/Components/Admin/PageHeading.vue';
import { formatDateTime } from '@/format';

defineProps({ overdue: Array, upcoming: Array, mine: Boolean });
</script>

<template>
  <Head title="Relances" />
  <PageHeading title="Relances" subtitle="Prospects à recontacter, du plus urgent au plus lointain." icon="calendar-check" />
  <div class="mb-6 flex gap-1"><Link :href="route('admin.followups')" class="tab" :class="{ 'is-active': !mine }">Toute l'équipe</Link><Link :href="route('admin.followups', { mine: 1 })" class="tab" :class="{ 'is-active': mine }">Mes relances</Link></div>
  <div class="grid gap-6 xl:grid-cols-2">
    <section v-for="[title, list, tone] in [['En retard', overdue, 'text-rose-600'], ['À venir', upcoming, '']]" :key="title" class="dash-card">
      <div class="dash-card-header"><h2 class="dash-card-title" :class="tone">{{ title }} ({{ list.length }})</h2></div>
      <ul class="divide-y divide-line">
        <li v-for="l in list" :key="l.id"><Link :href="route('admin.leads.show', l.id)" class="flex items-center gap-3 px-5 py-3.5 hover:bg-surface"><span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold text-ink">{{ l.name }}</span><span class="block truncate text-xs text-slate-500">{{ l.note || 'Relance' }} · {{ l.owner || 'non affecté' }}</span></span><span class="shrink-0 text-xs font-semibold" :class="l.overdue ? 'text-rose-600' : 'text-ink'">{{ formatDateTime(l.at) }}</span></Link></li>
        <li v-if="!list.length" class="px-5 py-8 text-center text-sm text-slate-400">Aucune relance.</li>
      </ul>
    </section>
  </div>
</template>
