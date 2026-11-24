<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Field from '@/Components/Admin/Field.vue';
import StatusPill from '@/Components/Admin/StatusPill.vue';
import Timeline from '@/Components/Admin/Timeline.vue';
import { formatDateTime, initials } from '@/format';

const props = defineProps({ inquiry: Object, activities: Array, audit: Array, statuses: Array, staff: Array, activityKinds: Array, reply: Object, canEdit: Boolean, canCrm: Boolean });
const tab = ref('reply');
const update = useForm({ status: props.inquiry.status, assigned_to_id: props.inquiry.assigned_to_id ?? '' });
const note = useForm({ kind: 'note', content: '' });
const replyForm = useForm({ subject: props.reply.subject, message: props.reply.message, mark_answered: true });
const details = [['Domaine', 'category'], ['Solution', 'product'], ['Offre', 'plan'], ['Budget', 'budget'], ['Échéance', 'timeline']];
</script>

<template>
  <Head :title="inquiry.reference" />
  <div class="mb-8">
    <Link :href="route('admin.resources.index', 'demandes')" class="mb-3 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-primary"><Icon name="arrow-left" size="size-4" />Demandes</Link>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div><p class="font-mono text-xs text-slate-400">{{ inquiry.reference }} · reçue le {{ formatDateTime(inquiry.created_at) }}</p><h1 class="mt-1 font-display text-2xl font-bold text-ink sm:text-3xl">{{ inquiry.subject }}</h1></div>
      <div class="flex items-center gap-2"><span class="chip">{{ inquiry.kind }}</span><StatusPill :status="inquiry.status" :label="inquiry.status_label" /></div>
    </div>
  </div>
  <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
    <div class="min-w-0 space-y-6">
      <section class="dash-card">
        <div class="dash-card-header"><h2 class="dash-card-title">Message</h2></div>
        <div class="p-5">
          <p class="whitespace-pre-line break-words text-[0.95rem] leading-relaxed text-slate-700">{{ inquiry.message }}</p>
          <dl class="mt-6 grid gap-4 border-t border-line pt-5 text-sm sm:grid-cols-3">
            <template v-for="[label, key] in details" :key="key"><div v-if="inquiry[key]"><dt class="text-xs text-slate-400">{{ label }}</dt><dd class="font-medium text-ink">{{ inquiry[key] }}</dd></div></template>
            <div><dt class="text-xs text-slate-400">Consentement</dt><dd class="font-medium text-ink">{{ inquiry.consent_at ? `Oui, le ${formatDateTime(inquiry.consent_at)}` : 'Non' }}</dd></div>
            <div class="sm:col-span-3"><dt class="text-xs text-slate-400">Page d'origine</dt><dd class="truncate text-slate-600">{{ inquiry.source_url || '—' }}</dd></div>
          </dl>
        </div>
      </section>
      <section v-if="canEdit" class="dash-card">
        <div class="flex gap-1 border-b border-line bg-surface/60 p-2">
          <button type="button" class="tab" :class="{ 'is-active': tab === 'reply' }" @click="tab = 'reply'"><Icon name="send" size="size-4 inline" /> Répondre par email</button>
          <button type="button" class="tab" :class="{ 'is-active': tab === 'note' }" @click="tab = 'note'"><Icon name="notebook-pen" size="size-4 inline" /> Commentaire interne</button>
        </div>
        <form v-if="tab === 'reply'" class="space-y-4 p-5" @submit.prevent="replyForm.post(route('admin.inquiries.reply', inquiry.id), { preserveScroll: true })">
          <p class="text-xs text-slate-500">Destinataire : <strong class="text-ink">{{ inquiry.email }}</strong> · la réponse est tracée dans l'historique.</p>
          <Field v-model="replyForm.subject" label="Objet" required :error="replyForm.errors.subject" />
          <Field v-model="replyForm.message" label="Message" type="textarea" :rows="8" required :error="replyForm.errors.message" />
          <label class="flex items-center gap-2 text-sm"><input v-model="replyForm.mark_answered" type="checkbox" class="field-checkbox"> Marquer la demande comme répondue</label>
          <div class="flex justify-end"><button class="btn btn-primary" :disabled="replyForm.processing"><Icon name="send" size="size-4" />Envoyer la réponse</button></div>
        </form>
        <form v-else class="space-y-4 p-5" @submit.prevent="note.post(route('admin.inquiries.note', inquiry.id), { preserveScroll: true, onSuccess: () => note.reset('content') })">
          <select v-model="note.kind" class="field-select sm:w-48"><option v-for="k in activityKinds" :key="k.value" :value="k.value">{{ k.label }}</option></select>
          <Field v-model="note.content" label="Contenu" type="textarea" :rows="3" :error="note.errors.content" />
          <div class="flex justify-end"><button class="btn btn-primary" :disabled="note.processing">Ajouter</button></div>
        </form>
      </section>
      <section id="timeline" class="dash-card"><div class="dash-card-header"><h2 class="dash-card-title">Historique</h2></div><div class="p-5"><Timeline :activities="activities" /></div></section>
    </div>
    <aside class="space-y-6">
      <section class="dash-card p-5">
        <div class="flex items-center gap-3"><span class="grid size-11 place-items-center rounded-full bg-gradient-to-br from-primary to-accent text-sm font-bold text-white">{{ initials(inquiry.full_name) }}</span>
          <div class="min-w-0"><p class="truncate font-semibold text-ink">{{ inquiry.full_name }}</p><p class="truncate text-xs text-slate-500">{{ [inquiry.job_title, inquiry.organization].filter(Boolean).join(' · ') }}</p></div></div>
        <ul class="mt-5 space-y-2.5 text-sm">
          <li class="flex items-center gap-2.5"><Icon name="mail" size="size-4 text-slate-400" /><a :href="`mailto:${inquiry.email}`" class="truncate text-primary">{{ inquiry.email }}</a></li>
          <li v-if="inquiry.phone" class="flex items-center gap-2.5"><Icon name="phone" size="size-4 text-slate-400" /><a :href="`tel:${inquiry.phone}`">{{ inquiry.phone }}</a></li>
          <li v-if="inquiry.country" class="flex items-center gap-2.5"><Icon name="map-pin" size="size-4 text-slate-400" />{{ inquiry.country }}</li>
        </ul>
        <Link v-if="inquiry.lead && canCrm" :href="route('admin.leads.show', inquiry.lead.id)" class="btn btn-secondary btn-sm mt-5 w-full"><Icon name="contact" size="size-4" />Fiche prospect · {{ inquiry.lead.status }}</Link>
      </section>
      <section class="dash-card p-5">
        <h2 class="dash-card-title">Traitement</h2>
        <form class="mt-4 space-y-4" @submit.prevent="update.patch(route('admin.inquiries.update', inquiry.id), { preserveScroll: true })">
          <label class="block"><span class="field-label">Statut</span><select v-model="update.status" class="field-select" :disabled="!canEdit"><option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option></select></label>
          <label class="block"><span class="field-label">Responsable</span><select v-model="update.assigned_to_id" class="field-select" :disabled="!canEdit"><option value="">— Personne —</option><option v-for="u in staff" :key="u.value" :value="u.value">{{ u.label }}</option></select></label>
          <button v-if="canEdit" class="btn btn-primary w-full" :disabled="update.processing">Mettre à jour</button>
        </form>
        <p v-if="inquiry.answered_at" class="mt-4 text-xs text-slate-500">Répondue le {{ formatDateTime(inquiry.answered_at) }}</p>
      </section>
      <section v-if="audit.length" class="dash-card p-5"><h2 class="dash-card-title">Journal des opérations</h2>
        <ul class="mt-4 space-y-3 text-xs"><li v-for="(e, i) in audit" :key="i"><span class="font-semibold text-ink">{{ e.action }}</span> · {{ e.actor }}<br><span class="text-slate-400">{{ formatDateTime(e.at) }}</span></li></ul></section>
      <p class="px-1 text-[11px] text-slate-400">IP : {{ inquiry.ip_address || '—' }}</p>
    </aside>
  </div>
</template>
