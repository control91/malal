<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Field from '@/Components/Admin/Field.vue';

defineProps({ status: String });
const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post(route('login.store'), { onFinish: () => form.reset('password') });
</script>

<template>
  <h2 class="font-display text-2xl font-bold text-ink">Connexion</h2>
  <p class="mt-2 text-sm text-slate-500">Accès réservé aux membres de l'équipe.</p>
  <div v-if="status" class="alert alert-success mt-6">{{ status }}</div>
  <div v-if="$page.props.flash.error" class="alert alert-error mt-6">{{ $page.props.flash.error }}</div>
  <form class="mt-8 space-y-5" novalidate @submit.prevent="submit">
    <Field v-model="form.email" label="Adresse email" type="email" required autocomplete="email" :error="form.errors.email" />
    <div>
      <Field v-model="form.password" label="Mot de passe" type="password" required autocomplete="current-password" :error="form.errors.password" />
      <Link :href="route('password.request')" class="mt-2 inline-block text-xs font-medium text-primary">Mot de passe oublié ?</Link>
    </div>
    <label class="flex items-center gap-2 text-sm text-slate-600"><input v-model="form.remember" type="checkbox" class="field-checkbox"> Rester connecté</label>
    <button class="btn btn-primary btn-lg w-full" type="submit" :disabled="form.processing">Se connecter <Icon name="log-in" size="size-4" /></button>
  </form>
</template>
