<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Honeypot from './Honeypot.vue';

const page = usePage();
const form = useForm({ email: '', consent: false, website: '', form_token: page.props.antispam });
const submit = () => form.post(route('newsletter.subscribe'), {
    preserveScroll: true,
    onSuccess: () => form.reset('email', 'consent'),
    onFinish: () => { form.form_token = page.props.antispam; },
});
</script>

<template>
  <form class="mt-4 space-y-3" novalidate @submit.prevent="submit">
    <Honeypot v-model="form.website" />
    <label for="nl-email" class="sr-only">Adresse email</label>
    <div class="flex items-center gap-2 border-b border-white/20 pb-1 transition focus-within:border-primary-400">
      <input id="nl-email" v-model="form.email" type="email" required autocomplete="email" placeholder="Votre adresse email" class="min-w-0 flex-1 border-0 bg-transparent px-0 py-3 text-white placeholder:text-slate-500 focus:outline-none focus:ring-0">
      <button type="submit" class="grid size-10 place-items-center rounded-full text-primary-400 transition hover:bg-primary hover:text-white disabled:opacity-50" :disabled="form.processing" aria-label="S'inscrire"><Icon name="send" /></button>
    </div>
    <label class="flex items-start gap-2 text-xs text-slate-400">
      <input v-model="form.consent" type="checkbox" required class="field-checkbox mt-0.5 border-white/20 bg-white/5">
      <span>J'accepte de recevoir la newsletter. Désinscription en un clic.</span>
    </label>
    <p v-if="form.errors.email || form.errors.consent || form.errors.newsletter" class="text-xs text-rose-400" role="alert">{{ form.errors.email || form.errors.consent || form.errors.newsletter }}</p>
    <p v-if="form.recentlySuccessful" class="text-xs text-emerald-300" role="status">Merci ! Un email de confirmation vient de vous être envoyé.</p>
  </form>
</template>
