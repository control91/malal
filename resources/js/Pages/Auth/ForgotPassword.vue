<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/Admin/Field.vue';

defineProps({ status: String });
const form = useForm({ email: '' });
</script>

<template>
  <h2 class="font-display text-2xl font-bold text-ink">Mot de passe oublié</h2>
  <p class="mt-2 text-sm text-slate-500">Saisissez votre adresse : si un compte existe, vous recevrez un lien de réinitialisation.</p>
  <div v-if="status" class="alert alert-success mt-6">{{ status }}</div>
  <form class="mt-8 space-y-5" @submit.prevent="form.post(route('password.email'))">
    <Field v-model="form.email" label="Adresse email" type="email" required autocomplete="email" :error="form.errors.email" />
    <button class="btn btn-primary btn-lg w-full" :disabled="form.processing">Envoyer le lien</button>
  </form>
  <Link :href="route('login')" class="mt-6 inline-block text-sm text-primary">← Retour à la connexion</Link>
</template>
