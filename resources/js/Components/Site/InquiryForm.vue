<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import FormField from './FormField.vue';
import Honeypot from './Honeypot.vue';

/**
 * Generic public inquiry form. `fields`: [{ name, label, type, required, options, autocomplete, wide }]
 */
const props = defineProps({
    action: { type: String, required: true },
    fields: { type: Array, required: true },
    initial: { type: Object, default: () => ({}) },
    submitLabel: { type: String, default: 'Envoyer' },
});
const page = usePage();
const defaults = Object.fromEntries(props.fields.map((f) => [f.name, props.initial[f.name] ?? (f.type === 'checkbox' ? false : '')]));
const form = useForm({ ...defaults, ...props.initial, consent: false, website: '', form_token: page.props.antispam });
const globalError = computed(() => form.errors.form || page.props.flash?.error);
const submit = () => form.post(props.action, { preserveScroll: true, onFinish: () => { form.form_token = page.props.antispam; } });
</script>

<template>
  <form class="space-y-6" novalidate @submit.prevent="submit">
    <Honeypot v-model="form.website" />
    <div v-if="globalError" class="alert alert-error" role="alert"><Icon name="triangle-alert" class="shrink-0" /><p>{{ globalError }}</p></div>
    <div v-else-if="form.hasErrors" class="alert alert-error" role="alert"><Icon name="triangle-alert" class="shrink-0" /><p>Merci de corriger les champs signalés ci-dessous.</p></div>
    <div class="grid gap-5 sm:grid-cols-2">
      <FormField v-for="field in fields" :key="field.name" v-model="form[field.name]" :label="field.label" :type="field.type || 'text'" :required="!!field.required" :options="field.options || []"
                 :autocomplete="field.autocomplete" :placeholder="field.placeholder" :error="form.errors[field.name]" :class="{ 'sm:col-span-2': field.wide || field.type === 'textarea' }" />
    </div>
    <FormField v-model="form.consent" type="checkbox" required :error="form.errors.consent" label="J'accepte que mes données soient utilisées pour traiter ma demande, conformément à la politique de confidentialité." />
    <p class="text-xs text-slate-500">Vos données servent uniquement au traitement de votre demande. Consultez notre <Link href="/confidentialite" class="text-primary-400 underline">politique de confidentialité</Link>.</p>
    <button type="submit" class="btn btn-primary btn-lg w-full sm:w-auto" :disabled="form.processing">
      <template v-if="form.processing"><Icon name="loader-circle" size="size-4 animate-spin" /> Envoi…</template><template v-else>{{ submitLabel }}</template>
    </button>
  </form>
</template>
