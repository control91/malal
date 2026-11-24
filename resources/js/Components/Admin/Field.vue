<script setup>
import { useId } from 'vue';
import Icon from '@/Components/Ui/Icon.vue';

/** Simple labelled input for back-office and auth screens. */
defineProps({
    label: { type: String, required: true },
    type: { type: String, default: 'text' },
    error: { type: String, default: '' },
    help: { type: String, default: '' },
    required: { type: Boolean, default: false },
    autocomplete: { type: String, default: undefined },
    rows: { type: Number, default: 4 },
    disabled: { type: Boolean, default: false },
});
const model = defineModel();
const id = useId();
</script>

<template>
  <div :class="{ 'has-error': error }">
    <label :for="id" class="field-label">{{ label }}<span v-if="required" class="text-rose-500"> *</span></label>
    <textarea v-if="type === 'textarea'" :id="id" v-model="model" :rows="rows" class="field-input" :disabled="disabled" :aria-invalid="!!error" />
    <input v-else :id="id" v-model="model" :type="type" class="field-input" :required="required" :autocomplete="autocomplete" :disabled="disabled" :aria-invalid="!!error">
    <p v-if="help" class="field-help">{{ help }}</p>
    <p v-if="error" class="field-error"><Icon name="circle-alert" size="size-3.5" />{{ error }}</p>
  </div>
</template>
