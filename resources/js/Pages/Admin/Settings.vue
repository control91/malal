<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import PageHeading from '@/Components/Admin/PageHeading.vue';
import FieldInput from '@/Components/Admin/FieldInput.vue';
import { cleanPayload } from '@/formdata';

const props = defineProps({ groups: Array, values: Object, canEdit: Boolean });
const clears = Object.fromEntries(props.groups.flatMap((g) => g.fields).filter((f) => ['image', 'logo'].includes(f.type)).map((f) => [`${f.name}_clear`, false]));
const form = useForm({ ...props.values, ...clears });
const submit = () => form.transform(cleanPayload).post(route('admin.settings.update'), { forceFormData: true, preserveScroll: true });
</script>

<template>
  <Head title="Configuration" />
  <PageHeading title="Configuration générale" subtitle="Identité de l'entreprise, coordonnées, réseaux sociaux, SEO et notifications. Les modifications sont journalisées." icon="settings" />
  <div v-if="!canEdit" class="alert alert-info mb-6"><Icon name="eye" size="size-4" /> Consultation seule.</div>
  <form class="grid gap-6 xl:grid-cols-[14rem_1fr]" novalidate @submit.prevent="submit">
    <nav class="hidden xl:block"><ul class="sticky top-24 space-y-1"><li v-for="(g, i) in groups" :key="g.title"><a :href="`#cfg-${i}`" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-white hover:text-ink"><Icon :name="g.icon" size="size-4" />{{ g.title }}</a></li></ul></nav>
    <div class="min-w-0 space-y-6">
      <div v-if="form.hasErrors" class="alert alert-error"><Icon name="triangle-alert" class="shrink-0" /><p>Certains champs sont invalides : ils sont signalés ci-dessous.</p></div>
      <section v-for="(g, i) in groups" :id="`cfg-${i}`" :key="g.title" class="dash-card scroll-mt-24">
        <div class="dash-card-header"><h2 class="dash-card-title flex items-center gap-2"><Icon :name="g.icon" size="size-4 text-primary" />{{ g.title }}</h2></div>
        <div class="grid gap-5 p-5 sm:grid-cols-2"><FieldInput v-for="f in g.fields" :key="f.name" v-model="form[f.name]" v-model:clear="form[`${f.name}_clear`]" :field="f" :error="form.errors[f.name]" :disabled="!canEdit" :class="{ 'sm:col-span-2': f.type === 'textarea' || f.wide }" /></div>
        <p v-if="g.title === 'Couleurs de marque'" class="px-5 pb-5 text-xs text-slate-500">Les couleurs s'appliquent immédiatement à l'ensemble du site et du back-office. Vérifiez le contraste du texte blanc sur la couleur principale.</p>
      </section>
      <div v-if="canEdit" class="sticky bottom-4 flex justify-end"><button class="btn btn-primary btn-lg shadow-lg" :disabled="form.processing"><Icon name="check" size="size-4" />Enregistrer les paramètres</button></div>
    </div>
  </form>
</template>
