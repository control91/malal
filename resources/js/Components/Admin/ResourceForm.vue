<script setup>
import { computed, onBeforeUnmount, onMounted, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import PageHeading from './PageHeading.vue';
import FieldInput from './FieldInput.vue';
import RepeaterEditor from './RepeaterEditor.vue';
import { confirmDialog } from '@/confirm';
import { cleanPayload } from '@/formdata';
import { formatDateTime } from '@/format';

/** Generic create/edit form driven by the Resource schema. Slots: before, after, sidebar. */
const props = defineProps({ resource: Object, record: Object, fieldsets: Array, repeaters: Array, values: Object, canEdit: Boolean });
const clears = Object.fromEntries(props.fieldsets.flatMap((s) => s.fields).filter((f) => ['image', 'logo', 'file'].includes(f.type)).map((f) => [`${f.name}_clear`, false]));
const form = useForm({ ...props.values, ...clears, _continue: false, next: new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '').get('next') ?? '' });

// Slug auto-fill from its source field while the slug has not been edited by hand.
const slugFields = props.fieldsets.flatMap((s) => s.fields).filter((f) => f.type === 'slug');
const touched = new Set(slugFields.filter((f) => form[f.name]).map((f) => f.name));
const slugify = (v) => String(v || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 120);
for (const f of slugFields) watch(() => form[f.slugFrom], (v) => { if (!touched.has(f.name)) form[f.name] = slugify(v); });

const submit = (cont = false) => {
    form._continue = cont;
    const url = props.record ? route('admin.resources.update', [props.resource.slug, props.record.id]) : route('admin.resources.store', props.resource.slug);
    form.transform((data) => cleanPayload(data)).post(url, { forceFormData: true, preserveScroll: true });
};
const destroy = async () => {
    if (await confirmDialog({ title: `Supprimer « ${props.record.label} » ?`, text: 'Cette action est définitive et sera journalisée.', label: 'Supprimer' })) {
        router.delete(route('admin.resources.destroy', [props.resource.slug, props.record.id]));
    }
};
const title = computed(() => props.record ? props.record.label : `Nouveau · ${props.resource.label}`);

// Warn before leaving with unsaved changes.
const beforeUnload = (e) => { if (form.isDirty && !form.processing) { e.preventDefault(); e.returnValue = ''; } };
let removeGuard;
onMounted(() => {
    window.addEventListener('beforeunload', beforeUnload);
    removeGuard = router.on('before', (event) => {
        if (form.isDirty && !form.processing && event.detail.visit.method === 'get' && !window.confirm('Des modifications ne sont pas enregistrées. Quitter quand même ?')) return false;
    });
});
onBeforeUnmount(() => { window.removeEventListener('beforeunload', beforeUnload); removeGuard?.(); });
defineExpose({ form });
</script>

<template>
  <Head :title="title" />
  <PageHeading :title="title" :back="{ url: route('admin.resources.index', resource.slug), label: resource.labelPlural }" />
  <div v-if="!canEdit" class="alert alert-info mb-6"><Icon name="eye" size="size-4" /> Consultation seule : votre rôle ne permet pas de modifier cet élément.</div>
  <form class="grid gap-6 xl:grid-cols-[1fr_20rem]" novalidate @submit.prevent="submit(false)">
    <div class="min-w-0 space-y-6">
      <div v-if="form.errors.form" class="alert alert-error"><Icon name="triangle-alert" class="shrink-0" /><p>{{ form.errors.form }}</p></div>
      <div v-else-if="form.hasErrors" class="alert alert-error"><Icon name="triangle-alert" class="shrink-0" /><p>Le formulaire contient des erreurs. Vérifiez les champs signalés.</p></div>
      <slot name="before" :form="form" />
      <section v-for="set in fieldsets" :key="set.title" class="dash-card">
        <div v-if="set.title" class="dash-card-header"><h2 class="dash-card-title">{{ set.title }}</h2></div>
        <div class="grid gap-5 p-5 sm:grid-cols-2">
          <FieldInput v-for="field in set.fields" :key="field.name" v-model="form[field.name]" v-model:clear="form[`${field.name}_clear`]" :field="field" :error="form.errors[field.name]" :disabled="!canEdit"
                      :class="{ 'sm:col-span-2': ['richtext', 'textarea', 'multiselect'].includes(field.type) || field.wide }" @update:model-value="field.type === 'slug' && touched.add(field.name)" />
        </div>
      </section>
      <RepeaterEditor v-for="r in repeaters" :key="r.relation" v-model="form[r.relation]" :repeater="r" :errors="form.errors" :disabled="!canEdit" />
      <slot name="after" :form="form" />
    </div>
    <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
      <div class="dash-card p-5">
        <p v-if="record && record.published !== null" class="flex items-center justify-between text-sm"><span class="text-slate-500">Statut</span><span class="status-pill" :class="record.published ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-50 text-slate-600 ring-slate-200'">{{ record.published ? 'Publié' : 'Non publié' }}</span></p>
        <p v-if="record?.updated_at" class="mt-3 flex items-center justify-between text-sm"><span class="text-slate-500">Modifié</span><span>{{ formatDateTime(record.updated_at) }}</span></p>
        <div v-if="canEdit" class="mt-5 grid gap-2">
          <button type="submit" class="btn btn-primary w-full" :disabled="form.processing"><Icon :name="form.processing ? 'loader-circle' : 'check'" size="size-4" :class="{ 'animate-spin': form.processing }" />Enregistrer</button>
          <button type="button" class="btn btn-secondary w-full" :disabled="form.processing" @click="submit(true)">Enregistrer et continuer</button>
        </div>
        <a v-if="record?.public_url" :href="record.public_url" target="_blank" class="btn btn-ghost mt-2 w-full"><Icon name="external-link" size="size-4" />Voir sur le site</a>
        <slot name="sidebar" :form="form" />
      </div>
      <button v-if="record?.can_delete" type="button" class="btn w-full text-rose-600 hover:bg-rose-50" @click="destroy"><Icon name="trash-2" size="size-4" />Supprimer</button>
    </aside>
  </form>
</template>
