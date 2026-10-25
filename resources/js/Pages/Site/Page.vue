<script setup>
import Icon from '@/Components/Ui/Icon.vue';
import RichText from '@/Components/Ui/RichText.vue';
import PageHeader from '@/Components/Site/PageHeader.vue';
import SectionRenderer from '@/Components/Sections/SectionRenderer.vue';
import { formatLongDate } from '@/format';

defineProps({ page: { type: Object, required: true }, sections: { type: Array, required: true } });
</script>

<template>
  <PageHeader :eyebrow="page.eyebrow" :title="page.title" :subtitle="page.intro" />
  <div v-if="!page.is_published" class="container-x mt-6"><div class="alert alert-warning"><Icon name="eye" size="size-4" /> Aperçu : cette page n'est pas publiée.</div></div>
  <section v-if="page.is_legal" class="section-sm">
    <div class="container-x grid gap-12 lg:grid-cols-12">
      <aside class="lg:col-span-3"><div class="sticky top-28 rounded-2xl border border-white/10 bg-white/[0.03] p-5 text-sm text-slate-400">
        <p class="font-semibold text-white">Dernière mise à jour</p><p class="mt-1">{{ formatLongDate(page.updated_at) }}</p>
        <p class="mt-4 font-semibold text-white">Une question ?</p><a :href="`mailto:${$page.props.site.email_contact}`" class="mt-1 block text-primary-400">{{ $page.props.site.email_contact }}</a>
      </div></aside>
      <RichText tag="article" :html="page.body" class="lg:col-span-9 lg:max-w-3xl" />
    </div>
  </section>
  <section v-else-if="page.body" class="section-sm"><div class="container-x max-w-3xl"><RichText :html="page.body" class="prose-lg" /></div></section>
  <SectionRenderer :sections="sections" />
</template>
