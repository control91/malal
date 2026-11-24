<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import PageHeading from '@/Components/Admin/PageHeading.vue';
import Field from '@/Components/Admin/Field.vue';
import UploadInput from '@/Components/Admin/UploadInput.vue';

const props = defineProps({ profile: Object });
const form = useForm({ first_name: props.profile.first_name, last_name: props.profile.last_name, job_title: props.profile.job_title, phone: props.profile.phone, bio: props.profile.bio ?? '', avatar: null });
const password = useForm({ current_password: '', password: '', password_confirmation: '' });
</script>

<template>
  <Head title="Mon profil" />
  <PageHeading title="Mon profil" subtitle="Ces informations apparaissent comme auteur de vos articles." icon="user" />
  <div class="grid max-w-5xl gap-6 lg:grid-cols-2">
    <form class="dash-card space-y-5 p-5" @submit.prevent="form.post(route('admin.profile.update'), { forceFormData: true, preserveScroll: true })">
      <h2 class="dash-card-title">Informations</h2>
      <div class="grid gap-5 sm:grid-cols-2"><Field v-model="form.first_name" label="Prénom" :error="form.errors.first_name" /><Field v-model="form.last_name" label="Nom" :error="form.errors.last_name" /><Field v-model="form.job_title" label="Fonction" /><Field v-model="form.phone" label="Téléphone" /></div>
      <Field v-model="form.bio" label="Biographie" type="textarea" />
      <div><span class="field-label">Photo</span><UploadInput v-model="form.avatar" :current="profile.avatar ? { url: profile.avatar, path: 'Photo actuelle' } : null" accept="image/jpeg,image/png,image/webp" /><p v-if="form.errors.avatar" class="field-error">{{ form.errors.avatar }}</p></div>
      <p class="text-sm text-slate-500">Email : <strong class="text-ink">{{ profile.email }}</strong> · Rôle : <strong class="text-ink">{{ profile.role }}</strong></p>
      <button class="btn btn-primary" :disabled="form.processing">Enregistrer</button>
    </form>
    <form class="dash-card h-fit space-y-5 p-5" @submit.prevent="password.put(route('admin.profile.password'), { preserveScroll: true, onSuccess: () => password.reset() })">
      <h2 class="dash-card-title">Mot de passe</h2>
      <Field v-model="password.current_password" label="Mot de passe actuel" type="password" autocomplete="current-password" :error="password.errors.current_password" />
      <Field v-model="password.password" label="Nouveau mot de passe" type="password" autocomplete="new-password" help="12 caractères minimum." :error="password.errors.password" />
      <Field v-model="password.password_confirmation" label="Confirmation" type="password" autocomplete="new-password" />
      <button class="btn btn-primary" :disabled="password.processing">Mettre à jour</button>
    </form>
  </div>
</template>
