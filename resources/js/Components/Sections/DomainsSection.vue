<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Emphasis from '@/Components/Ui/Emphasis.vue';
import RichText from '@/Components/Ui/RichText.vue';
import SectionHeading from '@/Components/Site/SectionHeading.vue';

defineProps({ section: { type: Object, required: true } });
</script>

<template>
  <section class="section" :id="section.anchor || undefined">
    <div class="container-x">
      <div class="grid gap-8 lg:grid-cols-12 lg:items-end">
        <div class="lg:col-span-7"><SectionHeading :section="section" align="left" /></div>
        <div class="flex items-end justify-between gap-8 lg:col-span-5">
          <RichText :html="section.body" class="text-[0.95rem]" />
          <Link :href="section.cta_url || '/solutions'" class="btn-circle group hidden sm:inline-grid"><span class="flex flex-col items-center gap-2 px-4">{{ section.cta_label || 'Toutes nos solutions' }}<Icon name="arrow-down-right" class="transition group-hover:-rotate-45" /></span></Link>
        </div>
      </div>
      <div class="mt-14">
        <Link v-for="(domain, i) in section.data ?? []" :key="domain.id" :href="domain.url" class="row-link group" data-reveal>
          <span class="font-display text-lg text-primary-400">{{ String(i + 1).padStart(2, '0') }}</span>
          <h3 class="flex items-center gap-4 font-display text-2xl transition group-hover:text-primary-400 sm:text-3xl"><Icon :name="domain.icon || 'layers'" size="size-7 shrink-0 text-slate-500 transition group-hover:text-primary-400" />{{ domain.name }}</h3>
          <p class="text-[0.95rem] leading-relaxed text-slate-400">{{ domain.summary }}</p>
          <span class="row-arrow justify-self-start sm:justify-self-end"><Icon name="arrow-right" /></span>
        </Link>
        <p v-if="!section.data?.length" class="text-slate-500">Les domaines d'expertise seront bientôt présentés ici.</p>
      </div>
    </div>
  </section>
</template>
