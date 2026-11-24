<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import StatusPill from '@/Components/Admin/StatusPill.vue';
import { formatDateTime, initials, timeAgo } from '@/format';

const props = defineProps({ content: Object, inquiries: Object, crm: Object, traffic: Object });
const page = usePage();
const can = (cap) => page.props.auth.can.includes(cap);
const today = new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date());
const peak = computed(() => Math.max(1, ...(props.inquiries?.series ?? []).map((d) => d.count)));
const pipelineMax = computed(() => Math.max(1, ...(props.crm?.pipeline ?? []).map((p) => p.count)));
</script>

<template>
  <Head title="Tableau de bord" />
  <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div><p class="text-sm capitalize text-slate-500">{{ today }}</p><h1 class="mt-1 font-display text-3xl font-bold text-ink">Bonjour {{ page.props.auth.user.first_name || page.props.auth.user.name }}</h1></div>
    <div class="flex flex-wrap gap-2">
      <Link v-if="can('blog.edit')" :href="route('admin.resources.create', 'articles')" class="btn btn-secondary"><Icon name="square-pen" size="size-4" />Nouvel article</Link>
      <Link v-if="can('content.edit')" :href="route('admin.resources.index', 'pages')" class="btn btn-secondary"><Icon name="panels-top-left" size="size-4" />Modifier le site</Link>
      <Link v-if="can('crm.view')" :href="route('admin.pipeline')" class="btn btn-primary"><Icon name="kanban" size="size-4" />Pipeline</Link>
    </div>
  </div>

  <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <template v-if="inquiries">
      <Link :href="route('admin.resources.index', { resource: 'demandes', status: 'new' })" class="stat-card hover:border-primary-200">
        <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-500">Demandes non traitées</p><span class="icon-tile size-10 rounded-xl"><Icon name="inbox" /></span></div>
        <p class="stat-value mt-3">{{ inquiries.new }}</p><p class="mt-1 text-xs text-slate-500">{{ inquiries.open }} ouverte(s) · {{ inquiries.total }} au total</p>
      </Link>
      <Link :href="route('admin.resources.index', { resource: 'demandes', kind: 'demo' })" class="stat-card hover:border-primary-200">
        <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-500">Démos &amp; devis en cours</p><span class="icon-tile size-10 rounded-xl"><Icon name="monitor" /></span></div>
        <p class="stat-value mt-3">{{ inquiries.demo + inquiries.quote }}</p><p class="mt-1 text-xs text-slate-500">{{ inquiries.demo }} démo(s) · {{ inquiries.quote }} devis</p>
      </Link>
    </template>
    <Link v-if="can('analytics.view')" :href="route('admin.analytics')" class="stat-card hover:border-primary-200">
      <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-500">Visiteurs (30 j)</p><span class="icon-tile size-10 rounded-xl"><Icon name="users" /></span></div>
      <template v-if="traffic"><p class="stat-value mt-3">{{ traffic.visitors }}</p><p class="mt-1 text-xs text-slate-500">{{ traffic.views }} pages vues · mesure interne sans cookie</p></template>
      <p v-else class="mt-3 text-sm text-slate-500">Pas encore de données mesurées.</p>
    </Link>
    <div class="stat-card">
      <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-500">Contenus publiés</p><span class="icon-tile size-10 rounded-xl"><Icon name="layers" /></span></div>
      <p class="stat-value mt-3">{{ content.products + content.articles }}</p>
      <p class="mt-1 text-xs text-slate-500">{{ content.products }} solution(s) · {{ content.services }} services · {{ content.articles }} article(s)<template v-if="content.scheduled"> · {{ content.scheduled }} programmé(s)</template></p>
    </div>
  </div>

  <div class="mt-6 grid gap-6 xl:grid-cols-3">
    <section v-if="inquiries" class="dash-card xl:col-span-2">
      <div class="dash-card-header"><h2 class="dash-card-title">Demandes reçues — 30 derniers jours</h2><span class="text-xs text-slate-400">hors indésirables</span></div>
      <div class="p-5">
        <div class="flex h-40 items-end gap-1" role="img" aria-label="Histogramme des demandes par jour">
          <div v-for="day in inquiries.series" :key="day.date" class="group relative flex h-full flex-1 items-end">
            <div class="bar" :class="{ '!bg-slate-100': !day.count }" :style="{ height: day.count ? `${(day.count * 100) / peak}%` : '3px' }" />
            <span class="pointer-events-none absolute -top-8 left-1/2 z-10 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-ink px-2 py-1 text-[11px] text-white group-hover:block">{{ day.date.slice(8, 10) }}/{{ day.date.slice(5, 7) }} · {{ day.count }}</span>
          </div>
        </div>
        <div class="mt-2 flex justify-between text-[11px] text-slate-400"><span>Il y a 30 jours</span><span>Aujourd'hui</span></div>
      </div>
    </section>
    <section v-if="crm" class="dash-card">
      <div class="dash-card-header"><h2 class="dash-card-title">Pipeline commercial</h2><Link :href="route('admin.pipeline')" class="text-xs font-semibold text-primary">Ouvrir</Link></div>
      <ul class="space-y-2.5 p-5">
        <li v-for="p in crm.pipeline" :key="p.status" class="flex items-center gap-3 text-sm"><span class="w-28 shrink-0 text-slate-600">{{ p.label }}</span><span class="h-2 flex-1 overflow-hidden rounded-full bg-surface"><span class="block h-full rounded-full" :class="p.status === 'won' ? 'bg-emerald-500' : p.status === 'lost' ? 'bg-slate-300' : 'bg-primary'" :style="{ width: `${(p.count * 100) / pipelineMax}%` }" /></span><span class="w-6 text-right font-semibold text-ink">{{ p.count }}</span></li>
      </ul>
    </section>
  </div>

  <div class="mt-6 grid gap-6 xl:grid-cols-2">
    <section v-if="inquiries" class="dash-card">
      <div class="dash-card-header"><h2 class="dash-card-title">Dernières demandes</h2><Link :href="route('admin.resources.index', 'demandes')" class="text-xs font-semibold text-primary">Tout voir</Link></div>
      <ul class="divide-y divide-line">
        <li v-for="i in inquiries.latest" :key="i.id"><Link :href="route('admin.inquiries.show', i.id)" class="flex items-center gap-3 px-5 py-3.5 hover:bg-surface">
          <span class="grid size-9 shrink-0 place-items-center rounded-full bg-primary-50 text-xs font-bold text-primary">{{ initials(i.name) }}</span>
          <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold text-ink">{{ i.name }}<span v-if="i.organization" class="font-normal text-slate-500"> · {{ i.organization }}</span></span><span class="block truncate text-xs text-slate-500">{{ i.kind }} — {{ i.subject }}</span></span>
          <span class="shrink-0 text-right"><StatusPill :status="i.status" :label="i.status_label" /><span class="mt-1 block text-[11px] text-slate-400">{{ timeAgo(i.created_at) }}</span></span>
        </Link></li>
        <li v-if="!inquiries.latest.length" class="px-5 py-10 text-center text-sm text-slate-400">Aucune demande pour le moment.</li>
      </ul>
    </section>
    <section v-if="crm" class="dash-card">
      <div class="dash-card-header"><h2 class="dash-card-title">Relances à faire</h2><Link :href="route('admin.followups')" class="text-xs font-semibold text-primary">Toutes les relances</Link></div>
      <ul class="divide-y divide-line">
        <li v-for="l in crm.followups" :key="l.id"><Link :href="route('admin.leads.show', l.id)" class="flex items-center gap-3 px-5 py-3.5 hover:bg-surface">
          <span class="grid size-9 shrink-0 place-items-center rounded-full" :class="l.overdue ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600'"><Icon name="calendar-check" size="size-4" /></span>
          <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold text-ink">{{ l.name }}</span><span class="block truncate text-xs text-slate-500">{{ l.note || 'Relance prévue' }}</span></span>
          <span class="shrink-0 text-xs" :class="l.overdue ? 'font-semibold text-rose-600' : 'text-slate-500'">{{ formatDateTime(l.at) }}</span>
        </Link></li>
        <li v-if="!crm.followups.length" class="px-5 py-10 text-center text-sm text-slate-400">Aucune relance dans les 48 h.</li>
      </ul>
      <div v-if="crm.recent.length" class="border-t border-line px-5 py-3"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Prospects récents</p>
        <div class="mt-2 flex flex-wrap gap-1.5"><Link v-for="l in crm.recent" :key="l.id" :href="route('admin.leads.show', l.id)" class="chip hover:bg-primary-50 hover:text-primary">{{ l.name }}</Link></div></div>
    </section>
  </div>
</template>
