<script setup>
import { computed, useId } from 'vue';
import Icon from '@/Components/Ui/Icon.vue';
import RichEditor from './RichEditor.vue';
import IconPicker from './IconPicker.vue';
import UploadInput from './UploadInput.vue';

/** Renders one field from the server-side schema (app/Admin/Field.php). */
const props = defineProps({ field: { type: Object, required: true }, error: String, disabled: Boolean, compact: Boolean, form: Object });
const model = defineModel();
const clear = defineModel('clear');
const id = useId();
const count = computed(() => (typeof model.value === 'string' ? model.value.length : 0));
const toggleMulti = (value) => {
    const list = Array.isArray(model.value) ? [...model.value] : [];
    const i = list.indexOf(value);
    i === -1 ? list.push(value) : list.splice(i, 1);
    model.value = list;
};
// Existing file of an upload field ({ path, url }), kept while a new File is selected.
const uploadCurrent = model.value && typeof model.value === 'object' && !(model.value instanceof File) && 'url' in model.value && model.value.url ? model.value : null;
</script>

<template>
  <div :class="{ 'has-error': error, 'sm:col-span-2': field.wide && !compact }">
    <label v-if="field.type === 'boolean'" class="flex items-start gap-3 rounded-xl border border-line bg-white px-4 py-3 text-sm">
      <input :id="id" v-model="model" type="checkbox" class="field-checkbox mt-0.5" :disabled="disabled">
      <span><span class="font-medium text-ink">{{ field.label }}</span><span v-if="field.help" class="mt-0.5 block text-xs text-slate-500">{{ field.help }}</span></span>
    </label>
    <template v-else>
      <label :for="id" class="field-label flex items-center justify-between gap-2" :class="{ '!text-xs !text-slate-500': compact }">
        <span>{{ field.label }}<span v-if="field.required" class="text-rose-500"> *</span></span>
        <span v-if="field.counter" class="text-xs font-normal" :class="count > field.counter ? 'text-rose-500' : 'text-slate-400'">{{ count }}/{{ field.counter }}</span>
      </label>
      <RichEditor v-if="field.type === 'richtext'" v-model="model" :disabled="disabled" />
      <IconPicker v-else-if="field.type === 'icon'" v-model="model" :disabled="disabled" />
      <UploadInput v-else-if="['image', 'logo', 'file'].includes(field.type)" v-model="model" v-model:clear="clear" :current="uploadCurrent" :accept="field.accept" :image="field.type !== 'file'" :disabled="disabled" />
      <select v-else-if="field.type === 'select'" :id="id" v-model="model" class="field-select" :disabled="disabled">
        <option :value="''">—</option>
        <option v-for="o in field.options ?? []" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>
      <div v-else-if="field.type === 'multiselect'" class="grid max-h-48 gap-1.5 overflow-y-auto rounded-xl border border-line bg-white p-3 sm:grid-cols-2">
        <label v-for="o in field.options ?? []" :key="o.value" class="flex items-center gap-2 text-sm"><input type="checkbox" class="field-checkbox" :checked="(model ?? []).includes(o.value)" :disabled="disabled" @change="toggleMulti(o.value)">{{ o.label }}</label>
        <p v-if="!(field.options ?? []).length" class="text-xs text-slate-400">Aucun élément disponible.</p>
      </div>
      <textarea v-else-if="field.type === 'textarea'" :id="id" v-model="model" :rows="compact ? 2 : 4" class="field-input" :maxlength="field.max" :disabled="disabled" />
      <input v-else-if="field.type === 'color'" :id="id" v-model="model" type="color" class="h-11 w-24 cursor-pointer rounded-xl border-0 bg-white p-1 ring-1 ring-line" :disabled="disabled">
      <input v-else :id="id" v-model="model" class="field-input" :type="{ number: 'number', email: 'email', url: 'url', date: 'date', datetime: 'datetime-local', password: 'password' }[field.type] ?? 'text'"
             :step="field.type === 'number' ? 'any' : undefined" :maxlength="field.max" :disabled="disabled" :autocomplete="field.type === 'password' ? 'new-password' : undefined">
      <p v-if="field.help" class="field-help">{{ field.help }}</p>
    </template>
    <p v-if="error" class="field-error"><Icon name="circle-alert" size="size-3.5" />{{ error }}</p>
  </div>
</template>
