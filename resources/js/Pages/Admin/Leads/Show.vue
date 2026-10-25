<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Field from '@/Components/Admin/Field.vue';
import StatusPill from '@/Components/Admin/StatusPill.vue';
import Timeline from '@/Components/Admin/Timeline.vue';
import { confirmDialog } from '@/confirm';
import { formatDate, formatDateTime, initials, money } from '@/format';

const props = defineProps({ lead: Object, inquiries: Array, activities: Array, statuses: Array, activityKinds: Array, canEdit: Boolean });
const note = useForm({ kind: 'call', content: '' });
const followup = useForm({ next_follow_up_at: props.lead.next_follow_up_at ?? '', follow_up_note: props.lead.follow_up_note });
const setStatus = (status) => router.post(route('admin.leads.status', props.lead.id), { status }, { preserveScroll: true });
const convert = async () => { if (await confirmDialog({ title: 'Convertir en client ?', text: 'Le prospect passera au statut « Gagné » et sera marqué comme client.', label: 'Convertir', danger: false })) router.post(route('admin.leads.convert', props.lead.id)); };
</script>

<template>
  <Head :title="lead.full_name" />
  <div class="mb-8">
    <Link :href="route('admin.resources.index', 'prospects')" class="mb-3 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-primary"><Icon name="arrow-left" size="size-4" />Prospects</Link>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div class="flex items-center gap-4">
        <span class="grid size-14 place-items-center rounded-2xl bg-gradient-to-br from-primary to-accent font-display text-lg font-bold text-white">{{ initials(lead.full_name) }}</span>
        <div><h1 class="font-display text-2xl font-bold text-ink sm:text-3xl">{{ lead.full_name }}</h1>
          <p class="text-sm text-slate-500">{{ [lead.job_title, lead.organization].filter(Boolean).join(' · ') }}<span v-if="lead.is_client" class="status-pill ml-2 bg-emerald-50 text-emerald-700 ring-emerald-200">Client depuis le {{ formatDate(lead.converted_at) }}</span></p></div>
      </div>
      <div v-if="canEdit" class="flex flex-wrap gap-2">
        <Link :href="route('admin.resources.edit', ['prospects', lead.id])" class="btn btn-secondary"><Icon name="pencil" size="size-4" />Modifier</Link>
        <button v-if="!lead.is_client" type="button" class="btn btn-primary" @click="convert"><Icon name="badge-check" size="size-4" />Convertir en client</button>
      </div>
    </div>
  </div>
  <div class="dash-card mb-6 overflow-x-auto p-2">
    <ol class="flex min-w-max gap-1">
      <li v-for="s in statuses" :key="s.value" class="flex-1"><button type="button" :disabled="!canEdit" class="w-full rounded-xl px-4 py-2.5 text-sm font-semibold transition" :class="lead.status === s.value ? (s.value === 'won' ? 'bg-emerald-500 text-white' : s.value === 'lost' ? 'bg-slate-500 text-white' : 'bg-primary text-white') : 'text-slate-500 hover:bg-surface'" @click="lead.status !== s.value && setStatus(s.value)">{{ s.label }}</button></li>
    </ol>
  </div>
  <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
    <div class="min-w-0 space-y-6">
      <section v-if="canEdit" class="dash-card">
        <div class="dash-card-header"><h2 class="dash-card-title">Nouvelle interaction</h2></div>
        <form class="space-y-4 p-5" @submit.prevent="note.post(route('admin.leads.note', lead.id), { preserveScroll: true, onSuccess: () => note.reset('content') })">
          <select v-model="note.kind" class="field-select sm:w-48"><option v-for="k in activityKinds" :key="k.value" :value="k.value">{{ k.label }}</option></select>
          <Field v-model="note.content" label="Compte rendu" type="textarea" :rows="3" :error="note.errors.content" />
          <div class="flex justify-end"><button class="btn btn-primary" :disabled="note.processing">Enregistrer</button></div>
        </form>
      </section>
      <section class="dash-card"><div class="dash-card-header"><h2 class="dash-card-title">Historique des interactions</h2></div><div class="p-5"><Timeline :activities="activities" show-inquiry /></div></section>
    </div>
    <aside class="space-y-6">
      <section class="dash-card p-5">
        <h2 class="dash-card-title">Coordonnées</h2>
        <ul class="mt-4 space-y-2.5 text-sm">
          <li class="flex items-center gap-2.5"><Icon name="mail" size="size-4 text-slate-400" /><a :href="`mailto:${lead.email}`" class="truncate text-primary">{{ lead.email }}</a></li>
          <li v-if="lead.phone" class="flex items-center gap-2.5"><Icon name="phone" size="size-4 text-slate-400" /><a :href="`tel:${lead.phone}`">{{ lead.phone }}</a></li>
          <li v-if="lead.country" class="flex items-center gap-2.5"><Icon name="map-pin" size="size-4 text-slate-400" />{{ lead.country }}</li>
        </ul>
        <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-line pt-5 text-sm">
          <div><dt class="text-xs text-slate-400">Source</dt><dd class="font-medium text-ink">{{ lead.source }}</dd></div>
          <div><dt class="text-xs text-slate-400">Priorité</dt><dd class="font-medium" :class="lead.priority === 'high' ? 'text-rose-600' : 'text-ink'">{{ lead.priority_label }}</dd></div>
          <div><dt class="text-xs text-slate-400">Responsable</dt><dd class="font-medium text-ink">{{ lead.owner || '—' }}</dd></div>
          <div><dt class="text-xs text-slate-400">Valeur estimée</dt><dd class="font-medium text-ink">{{ lead.estimated_value ? money(lead.estimated_value, lead.currency) : '—' }}</dd></div>
          <div class="col-span-2"><dt class="text-xs text-slate-400">Intérêt</dt><dd class="font-medium text-ink">{{ lead.interest || '—' }}<template v-if="lead.product"> · {{ lead.product }}</template></dd></div>
          <div v-if="lead.lost_reason" class="col-span-2"><dt class="text-xs text-slate-400">Raison de la perte</dt><dd class="text-ink">{{ lead.lost_reason }}</dd></div>
        </dl>
        <div v-if="lead.notes" class="mt-5 rounded-xl bg-surface p-3 text-sm text-slate-600"><p class="text-xs font-semibold text-slate-400">Notes internes</p><p class="mt-1 whitespace-pre-line">{{ lead.notes }}</p></div>
      </section>
      <section class="dash-card p-5">
        <h2 class="dash-card-title flex items-center gap-2"><Icon name="calendar-check" size="size-4 text-primary" />Relance</h2>
        <p v-if="lead.overdue" class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">En retard</p>
        <form class="mt-4 space-y-4" @submit.prevent="followup.post(route('admin.leads.followup', lead.id), { preserveScroll: true })">
          <Field v-model="followup.next_follow_up_at" label="Date de relance" type="datetime-local" :disabled="!canEdit" :error="followup.errors.next_follow_up_at" />
          <Field v-model="followup.follow_up_note" label="Objet" :disabled="!canEdit" />
          <button v-if="canEdit" class="btn btn-secondary w-full" :disabled="followup.processing">Planifier</button>
        </form>
      </section>
      <section v-if="inquiries.length" class="dash-card p-5"><h2 class="dash-card-title">Demandes liées</h2>
        <ul class="mt-4 space-y-2"><li v-for="i in inquiries" :key="i.id"><Link :href="route('admin.inquiries.show', i.id)" class="flex items-center justify-between gap-2 rounded-xl px-3 py-2 text-sm hover:bg-surface"><span class="min-w-0"><span class="block truncate font-medium text-ink">{{ i.subject }}</span><span class="text-xs text-slate-400">{{ i.reference }} · {{ formatDate(i.created_at) }}</span></span><StatusPill :status="i.status" :label="i.status_label" /></Link></li></ul>
      </section>
    </aside>
  </div>
</template>
