<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Emphasis from '@/Components/Ui/Emphasis.vue';
import RichText from '@/Components/Ui/RichText.vue';
import SectionHeading from '@/Components/Site/SectionHeading.vue';

defineProps({ section: { type: Object, required: true } });
</script>

<template>
  <section class="relative isolate overflow-hidden border-t border-white/10 py-24 sm:py-32" :id="section.anchor || undefined">
    <div class="grid-pattern absolute inset-0 -z-10 opacity-50" /><div class="glow left-1/2 top-1/2 size-[36rem] -translate-x-1/2 -translate-y-1/2 bg-primary/25" />
    <div class="container-x grid items-center gap-12 lg:grid-cols-12" data-reveal>
      <div class="lg:col-span-8">
        <p v-if="section.eyebrow" class="eyebrow">{{ section.eyebrow }}</p>
        <h2 class="h-section mt-5"><Emphasis :text="section.title" /></h2>
        <p v-if="section.subtitle" class="lead mt-6 max-w-2xl">{{ section.subtitle }}</p>
        <Link v-if="section.cta2_label" :href="section.cta2_url" class="btn btn-secondary mt-9">{{ section.cta2_label }} <Icon name="arrow-up-right" size="size-4" /></Link>
      </div>
      <div class="flex lg:col-span-4 lg:justify-end">
        <Link v-if="section.cta_label" :href="section.cta_url" class="btn-circle group !size-44 bg-primary text-base before:!border-primary-300/60 hover:bg-primary-600 sm:!size-52"><span class="flex flex-col items-center gap-2 px-6">{{ section.cta_label }}<Icon name="arrow-up-right" size="size-6 transition group-hover:rotate-45" /></span></Link>
      </div>
    </div>
  </section>
</template>
