<script setup>
import Icon from '@/Components/Ui/Icon.vue';
import FieldInput from './FieldInput.vue';

/** Inline editor for a hasMany relation (rows are { id?, ...fields, _delete? }). */
const props = defineProps({ repeater: { type: Object, required: true }, errors: { type: Object, default: () => ({}) }, disabled: Boolean });
const rows = defineModel({ type: Array, default: () => [] });
const add = () => {
    const row = {};
    for (const f of props.repeater.fields) row[f.name] = f.type === 'boolean' ? (f.name === 'is_active' || f.name === 'is_published' || f.name === 'included') : f.name === 'order' ? (rows.value.length + 1) * 10 : '';
    rows.value = [...rows.value, row];
};
const remove = (index) => {
    const row = rows.value[index];
    if (row.id) row._delete = !row._delete;
    else rows.value = rows.value.filter((_, i) => i !== index);
};
const span = (f) => (f.type === 'textarea' ? 'sm:col-span-2 lg:col-span-4' : ['title', 'label', 'name', 'question'].includes(f.name) ? 'sm:col-span-2' : '');
</script>

<template>
  <section class="dash-card">
    <div class="dash-card-header">
      <div><h2 class="dash-card-title">{{ repeater.label }}</h2><p v-if="repeater.help" class="mt-0.5 text-xs text-slate-500">{{ repeater.help }}</p></div>
      <button v-if="!disabled" type="button" class="btn btn-secondary btn-sm" @click="add"><Icon name="plus" size="size-4" />Ajouter</button>
    </div>
    <div class="divide-y divide-line">
      <div v-for="(row, index) in rows" :key="row.id ?? `new-${index}`" class="p-5 transition" :class="{ 'bg-rose-50/50 opacity-60': row._delete }">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <FieldInput v-for="f in repeater.fields" :key="f.name" v-model="row[f.name]" :field="f" compact :class="span(f)" :disabled="disabled || row._delete" :error="errors[`${repeater.relation}.${index}.${f.name}`]" />
        </div>
        <button v-if="!disabled" type="button" class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium text-rose-600" @click="remove(index)"><Icon :name="row._delete ? 'refresh-cw' : 'trash-2'" size="size-3.5" />{{ row._delete ? 'Annuler la suppression' : 'Supprimer cet élément' }}</button>
      </div>
      <p v-if="!rows.length" class="px-5 py-6 text-center text-sm text-slate-400">Aucun élément. Cliquez sur « Ajouter ».</p>
    </div>
  </section>
</template>
