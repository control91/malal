<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import RichText from '@/Components/Ui/RichText.vue';
import PageHeader from '@/Components/Site/PageHeader.vue';
import { formatLongDate } from '@/format';

defineProps({ job: Object });
</script>

<template>
  <PageHeader :eyebrow="job.contract" :title="job.title" :subtitle="job.summary" :parent="{ label: 'Carrières', url: route('careers') }" />
  <section class="section-sm">
    <div class="container-x grid max-w-5xl gap-12 lg:grid-cols-12">
      <RichText :html="job.description" class="lg:col-span-8" />
      <aside class="lg:col-span-4"><div class="card sticky top-28 space-y-4 text-sm">
        <p class="flex items-center gap-2"><Icon name="map-pin" size="size-4 text-primary-400" />{{ job.location }}<template v-if="job.remote_possible"> · télétravail possible</template></p>
        <p class="flex items-center gap-2"><Icon name="briefcase" size="size-4 text-primary-400" />{{ job.contract }}</p>
        <p v-if="job.closes_on" class="flex items-center gap-2"><Icon name="calendar" size="size-4 text-primary-400" />Candidatures jusqu'au {{ formatLongDate(job.closes_on) }}</p>
        <Link :href="route('contact', { type: 'career' })" class="btn btn-primary w-full">Postuler</Link>
        <p class="text-xs text-slate-500">Précisez l'intitulé « {{ job.title }} » dans l'objet et joignez un lien vers votre CV (LinkedIn, portfolio…).</p>
      </div></aside>
    </div>
  </section>
</template>
