<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
import PageHeading from '@/Components/Admin/PageHeading.vue';
import { confirmDialog } from '@/confirm';

const props = defineProps({ resource: Object, columns: Array, filters: Array, query: Object, records: Object, canEdit: Boolean });
const q = ref(props.query.q);
const copied = ref(null);
let timer;
watch(q, (value) => { clearTimeout(timer); timer = setTimeout(() => router.get(route('admin.resources.index', 'medias'), value ? { q: value } : {}, { preserveState: true, replace: true }), 300); });
const copy = async (row) => { await navigator.clipboard?.writeText(new URL(row.file, window.location.origin).href); copied.value = row.id; setTimeout(() => (copied.value = null), 1500); };
const size = (bytes) => (bytes > 1048576 ? `${(bytes / 1048576).toFixed(1)} Mo` : `${Math.max(1, Math.round(bytes / 1024))} Ko`);
const destroy = async (row) => {
    if (await confirmDialog({ title: 'Supprimer ce média ?', text: "Les contenus qui l'utilisent afficheront un lien brisé.", label: 'Supprimer' })) router.delete(route('admin.resources.destroy', ['medias', row.id]), { preserveScroll: true });
};
const isImage = (url) => /\.(jpe?g|png|webp|gif|svg)$/i.test(url || '');
</script>

<template>
  <Head title="Médiathèque" />
  <PageHeading :title="resource.labelPlural" :subtitle="resource.description" :icon="resource.icon">
    <Link v-if="canEdit" :href="route('admin.resources.create', 'medias')" class="btn btn-primary"><Icon name="plus" size="size-4" />Ajouter</Link>
  </PageHeading>
  <input v-model="q" type="search" placeholder="Rechercher un média…" class="field-input mb-6 max-w-md">
  <ul v-if="records.data.length" class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
    <li v-for="row in records.data" :key="row.id" class="dash-card overflow-hidden">
      <Link :href="row._url" class="block aspect-[4/3] bg-surface"><img v-if="isImage(row.file)" :src="row.file" :alt="row.title" class="size-full object-cover" loading="lazy"><span v-else class="grid size-full place-items-center text-slate-400"><Icon name="file-text" size="size-10" /></span></Link>
      <div class="p-4">
        <p class="truncate text-sm font-semibold text-ink">{{ row.title }}</p>
        <p class="mt-0.5 text-xs text-slate-500">{{ (row.extension || '').toUpperCase() }} · {{ size(row.size) }}</p>
        <div class="mt-3 flex gap-1.5">
          <button type="button" class="btn btn-secondary btn-sm flex-1" @click="copy(row)"><Icon name="copy" size="size-3.5" />{{ copied === row.id ? 'Copiée' : "Copier l'URL" }}</button>
          <button v-if="row._canDelete" type="button" class="btn btn-ghost btn-sm !px-2 text-rose-500" @click="destroy(row)"><Icon name="trash-2" size="size-4" label="Supprimer" /></button>
        </div>
      </div>
    </li>
  </ul>
  <div v-else class="dash-card px-6 py-16 text-center text-slate-500">La médiathèque est vide.</div>
  <div class="mt-6"><Pagination :meta="records" admin /></div>
</template>
