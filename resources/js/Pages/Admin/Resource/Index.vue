<script setup>
import { computed, reactive, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
import PageHeading from '@/Components/Admin/PageHeading.vue';
import DataCell from '@/Components/Admin/DataCell.vue';
import { confirmDialog } from '@/confirm';

const props = defineProps({ resource: Object, columns: Array, filters: Array, query: Object, records: Object, canEdit: Boolean });
const state = reactive({ q: props.query.q, sort: props.query.sort, ...Object.fromEntries(props.filters.map((f) => [f.name, f.value])) });
const hasFilters = computed(() => !!props.query.q || props.filters.some((f) => f.value));
let timer;
watch(state, () => {
    clearTimeout(timer);
    timer = setTimeout(() => router.get(route('admin.resources.index', props.resource.slug), Object.fromEntries(Object.entries(state).filter(([, v]) => v !== '' && v !== null)), { preserveState: true, preserveScroll: true, replace: true }), 300);
});
const toggleSort = (col) => { state.sort = state.sort === col.sort ? `-${col.sort}` : col.sort; };
const exportUrl = computed(() => `${route('admin.resources.export', props.resource.slug)}${window?.location?.search ?? ''}`);
const destroy = async (row) => {
    if (await confirmDialog({ title: 'Supprimer cet élément ?', text: `« ${row._label} » sera définitivement supprimé. Cette action est journalisée.`, label: 'Supprimer' })) {
        router.delete(route('admin.resources.destroy', [props.resource.slug, row.id]), { preserveScroll: true });
    }
};
const toggle = (row) => router.post(route('admin.resources.toggle', [props.resource.slug, row.id]), {}, { preserveScroll: true });
</script>

<template>
  <Head :title="resource.labelPlural" />
  <PageHeading :title="resource.labelPlural" :subtitle="resource.description" :icon="resource.icon">
    <a v-if="resource.exportable" :href="exportUrl" class="btn btn-secondary"><Icon name="download" size="size-4" />Export CSV</a>
    <Link v-if="resource.canCreate && canEdit" :href="route('admin.resources.create', resource.slug)" class="btn btn-primary"><Icon name="plus" size="size-4" />Ajouter</Link>
  </PageHeading>
  <div class="dash-card">
    <div v-if="resource.searchable || filters.length" class="flex flex-col gap-3 border-b border-line p-4 lg:flex-row lg:items-center">
      <label v-if="resource.searchable" class="relative flex-1"><span class="sr-only">Rechercher</span><span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"><Icon name="search" size="size-4" /></span><input v-model="state.q" type="search" placeholder="Rechercher…" class="field-input !pl-9"></label>
      <label v-for="f in filters" :key="f.name" class="lg:w-48"><span class="sr-only">{{ f.label }}</span>
        <select v-model="state[f.name]" class="field-select"><option value="">{{ f.label }} : tous</option><option v-for="o in f.options" :key="o.value" :value="String(o.value)">{{ o.label }}</option></select>
      </label>
    </div>
    <div v-if="records.data.length" class="table-wrap">
      <table class="table">
        <thead><tr>
          <th v-for="col in columns" :key="col.key" scope="col">
            <button v-if="col.sort" type="button" class="inline-flex items-center gap-1 hover:text-ink" @click="toggleSort(col)">{{ col.label }}<Icon v-if="state.sort === col.sort" name="arrow-up" size="size-3" /><Icon v-else-if="state.sort === `-${col.sort}`" name="arrow-down" size="size-3" /></button>
            <template v-else>{{ col.label }}</template>
          </th>
          <th scope="col" class="text-right"><span class="sr-only">Actions</span></th>
        </tr></thead>
        <tbody>
          <tr v-for="row in records.data" :key="row.id">
            <td v-for="col in columns" :key="col.key"><DataCell :column="col" :value="row[col.key]" :url="row._url" /></td>
            <td class="whitespace-nowrap text-right">
              <div class="inline-flex items-center gap-1">
                <button v-if="resource.publishable && canEdit" type="button" class="btn btn-ghost btn-sm !px-2.5" :class="row._published ? 'text-emerald-600' : 'text-slate-400'" :title="row._published ? 'Dépublier' : 'Publier'" @click="toggle(row)"><Icon :name="row._published ? 'eye' : 'eye-off'" size="size-4" :label="row._published ? 'Publié — cliquer pour dépublier' : 'Non publié — cliquer pour publier'" /></button>
                <Link v-if="row._url" :href="row._url" class="btn btn-ghost btn-sm !px-2.5" title="Ouvrir"><Icon :name="canEdit && !resource.detailRoute ? 'pencil' : 'arrow-right'" size="size-4" label="Ouvrir" /></Link>
                <button v-if="row._canDelete" type="button" class="btn btn-ghost btn-sm !px-2.5 text-rose-500 hover:!bg-rose-50" title="Supprimer" @click="destroy(row)"><Icon name="trash-2" size="size-4" label="Supprimer" /></button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-if="records.data.length" class="flex items-center justify-between gap-4 px-4 py-3 text-sm text-slate-500"><span>{{ records.total }} élément{{ records.total > 1 ? 's' : '' }}</span><Pagination :meta="records" admin /></div>
    <div v-else class="px-6 py-16 text-center">
      <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-surface text-slate-400"><Icon :name="resource.icon" size="size-6" /></span>
      <template v-if="hasFilters"><p class="mt-4 font-semibold text-ink">Aucun résultat</p><p class="mt-1 text-sm text-slate-500">Modifiez la recherche ou les filtres.</p><Link :href="route('admin.resources.index', resource.slug)" class="btn btn-secondary btn-sm mt-4">Réinitialiser</Link></template>
      <template v-else><p class="mt-4 font-semibold text-ink">Aucun élément pour le moment</p><Link v-if="resource.canCreate && canEdit" :href="route('admin.resources.create', resource.slug)" class="btn btn-primary btn-sm mt-4"><Icon name="plus" size="size-4" />Ajouter</Link></template>
    </div>
  </div>
</template>
