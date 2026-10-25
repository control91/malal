<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import PageHeading from '@/Components/Admin/PageHeading.vue';
import { formatDate } from '@/format';

const props = defineProps({ columns: Array, owners: Array, currentOwner: String, canEdit: Boolean });
const owner = ref(props.currentOwner);
const filter = () => router.get(route('admin.pipeline'), owner.value ? { responsable: owner.value } : {}, { preserveState: true });
const menu = ref(null);
const dragged = ref(null);
const move = (lead, status) => { menu.value = null; router.post(route('admin.leads.status', lead.id), { status }, { preserveScroll: true }); };
const drop = (status) => { if (dragged.value && dragged.value.status !== status) move(dragged.value.lead, status); dragged.value = null; };
</script>

<template>
  <Head title="Pipeline" />
  <PageHeading title="Pipeline commercial" subtitle="Vue d'ensemble des prospects par étape. Glissez une carte (ou utilisez « Déplacer vers… ») pour changer son statut : l'action est tracée." icon="kanban">
    <select v-model="owner" class="field-select" @change="filter"><option value="">Tous les responsables</option><option value="none">Non affectés</option><option v-for="o in owners" :key="o.value" :value="String(o.value)">{{ o.label }}</option></select>
    <Link v-if="canEdit" :href="route('admin.resources.create', 'prospects')" class="btn btn-primary"><Icon name="user-plus" size="size-4" />Nouveau prospect</Link>
  </PageHeading>
  <div class="-mx-4 overflow-x-auto px-4 pb-4 sm:-mx-6 sm:px-6 lg:-mx-10 lg:px-10">
    <div class="flex min-w-max gap-4">
      <section v-for="col in columns" :key="col.status" class="flex w-72 shrink-0 flex-col rounded-2xl bg-slate-100/70 p-3" :aria-label="col.label" @dragover.prevent @drop="drop(col.status)">
        <header class="mb-3 flex items-center justify-between px-1"><h2 class="flex items-center gap-2 text-sm font-semibold text-ink"><span class="size-2 rounded-full" :class="col.status === 'won' ? 'bg-emerald-500' : col.status === 'lost' ? 'bg-slate-400' : 'bg-primary'" />{{ col.label }}</h2><span class="rounded-full bg-white px-2 text-xs font-semibold text-slate-500">{{ col.leads.length }}</span></header>
        <div class="flex flex-1 flex-col gap-2.5">
          <div v-for="lead in col.leads" :key="lead.id" :draggable="canEdit" class="rounded-xl border border-line bg-white p-3.5 shadow-sm transition hover:border-primary-200 hover:shadow-md" @dragstart="dragged = { lead, status: col.status }">
            <div class="flex items-start justify-between gap-2">
              <Link :href="route('admin.leads.show', lead.id)" class="min-w-0"><span class="block truncate text-sm font-semibold text-ink hover:text-primary">{{ lead.name }}</span><span class="block truncate text-xs text-slate-500">{{ lead.organization }}</span></Link>
              <span v-if="lead.priority === 'high'" class="status-pill shrink-0 bg-rose-50 text-rose-700 ring-rose-200">Haute</span>
            </div>
            <div class="mt-3 flex items-center justify-between gap-2 text-[11px] text-slate-500"><span class="truncate">{{ lead.interest || '—' }}</span><span v-if="lead.follow_up" class="inline-flex shrink-0 items-center gap-1" :class="{ 'font-semibold text-rose-600': lead.overdue }"><Icon name="clock" size="size-3" />{{ formatDate(lead.follow_up) }}</span></div>
            <div v-if="canEdit" class="relative mt-3 border-t border-line pt-2">
              <button type="button" class="flex w-full items-center justify-between text-[11px] font-medium text-slate-500 hover:text-primary" @click="menu = menu === lead.id ? null : lead.id">Déplacer vers… <Icon name="chevrons-up-down" size="size-3" /></button>
              <div v-if="menu === lead.id" class="absolute left-0 right-0 top-8 z-20 rounded-xl border border-line bg-white p-1 shadow-lg">
                <button v-for="c in columns.filter((c) => c.status !== col.status)" :key="c.status" type="button" class="block w-full rounded-lg px-2.5 py-1.5 text-left text-xs hover:bg-surface" @click="move(lead, c.status)">{{ c.label }}</button>
              </div>
            </div>
          </div>
          <p v-if="!col.leads.length" class="rounded-xl border border-dashed border-slate-300 py-6 text-center text-xs text-slate-400">Vide</p>
        </div>
      </section>
    </div>
  </div>
</template>
