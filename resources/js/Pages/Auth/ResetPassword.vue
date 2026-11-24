<script setup>
import { useForm } from '@inertiajs/vue3';
import Field from '@/Components/Admin/Field.vue';

const props = defineProps({ token: String, email: String });
const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' });
</script>

<template>
  <h2 class="font-display text-2xl font-bold text-ink">Choisissez un nouveau mot de passe</h2>
  <form class="mt-8 space-y-5" @submit.prevent="form.post(route('password.update'))">
    <Field v-model="form.email" label="Adresse email" type="email" required :error="form.errors.email" />
    <Field v-model="form.password" label="Nouveau mot de passe" type="password" required autocomplete="new-password" help="12 caractères minimum." :error="form.errors.password" />
    <Field v-model="form.password_confirmation" label="Confirmation" type="password" required autocomplete="new-password" />
    <button class="btn btn-primary btn-lg w-full" :disabled="form.processing">Enregistrer</button>
  </form>
</template>
