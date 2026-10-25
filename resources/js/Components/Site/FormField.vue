<script setup>
import { computed, useId } from 'vue';
import Icon from '@/Components/Ui/Icon.vue';

/** Accessible public form field. type: text | email | tel | textarea | select | checkbox */
const props = defineProps({
    label: { type: String, required: true },
    type: { type: String, default: 'text' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: '' },
    autocomplete: { type: String, default: undefined },
    help: { type: String, default: '' },
    rows: { type: Number, default: 5 },
    maxlength: { type: Number, default: undefined },
});
const model = defineModel();
const id = useId();
const describedBy = computed(() => (props.error ? `${id}-error` : undefined));
</script>

<template>
  <div :class="{ 'has-error': error }">
    <label v-if="type === 'checkbox'" class="field-check-label">
      <input :id="id" v-model="model" type="checkbox" class="field-checkbox mt-0.5" :required="required" :aria-invalid="!!error" :aria-describedby="describedBy">
      <span>{{ label }}<span v-if="required" class="text-rose-500" aria-hidden="true"> *</span></span>
    </label>
    <template v-else>
      <label :for="id" class="field-label">{{ label }}<span v-if="required" class="text-rose-500" aria-hidden="true"> *</span><span v-else class="font-normal text-slate-400"> (facultatif)</span></label>
      <select v-if="type === 'select'" :id="id" v-model="model" class="field-select" :required="required" :aria-invalid="!!error" :aria-describedby="describedBy">
        <option value="">{{ placeholder || '— Choisir —' }}</option>
        <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
      </select>
      <textarea v-else-if="type === 'textarea'" :id="id" v-model="model" class="field-input" :rows="rows" :required="required" :maxlength="maxlength" :placeholder="placeholder" :aria-invalid="!!error" :aria-describedby="describedBy" />
      <input v-else :id="id" v-model="model" :type="type" class="field-input" :required="required" :maxlength="maxlength" :placeholder="placeholder" :autocomplete="autocomplete" :inputmode="type === 'tel' ? 'tel' : undefined" :aria-invalid="!!error" :aria-describedby="describedBy">
    </template>
    <p v-if="help" class="field-help">{{ help }}</p>
    <p v-if="error" :id="`${id}-error`" class="field-error"><Icon name="circle-alert" size="size-3.5" />{{ error }}</p>
  </div>
</template>
