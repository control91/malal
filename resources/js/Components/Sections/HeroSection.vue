<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Emphasis from '@/Components/Ui/Emphasis.vue';
import RichText from '@/Components/Ui/RichText.vue';
import SectionHeading from '@/Components/Site/SectionHeading.vue';
import RingVisual from '@/Components/Site/RingVisual.vue';

defineProps({ section: { type: Object, required: true } });
const site = computed(() => usePage().props.site);
</script>

<template>
  <section class="relative isolate overflow-hidden" :id="section.anchor || undefined" :aria-labelledby="`hero-${section.id}`">
    <div class="grid-pattern absolute inset-0 -z-10 opacity-60" />
    <div class="glow -left-40 top-10 size-[34rem] bg-primary/25" /><div class="glow right-0 top-1/3 size-[26rem] bg-violet-600/15" />
    <div class="container-x relative grid items-center gap-12 pt-14 sm:pt-20 lg:grid-cols-12 lg:gap-8 lg:pt-24">
      <div class="relative order-last mx-auto w-56 sm:w-72 lg:order-none lg:col-span-5 lg:w-full lg:max-w-md" aria-hidden="true">
        <img v-if="section.image" :src="section.image" :alt="section.image_alt" class="w-full" width="480" height="480" fetchpriority="high">
        <RingVisual v-else />
      </div>
      <div class="lg:col-span-7">
        <p v-if="section.eyebrow" class="eyebrow motion-safe:animate-[fade-up_.8s_ease-out_both]">{{ section.eyebrow }}</p>
        <h1 :id="`hero-${section.id}`" class="h-display mt-6 motion-safe:animate-[fade-up_.8s_.08s_ease-out_both]"><Emphasis :text="section.title" /></h1>
        <div class="mt-8 flex flex-col gap-8 sm:flex-row sm:items-center motion-safe:animate-[fade-up_.8s_.16s_ease-out_both]">
          <p v-if="section.subtitle" class="lead max-w-xl flex-1">{{ section.subtitle }}</p>
          <Link v-if="section.cta2_label" :href="section.cta2_url" class="btn-circle group"><span class="flex flex-col items-center gap-2 px-4">{{ section.cta2_label }}<Icon name="arrow-down-right" class="transition group-hover:-rotate-45" /></span></Link>
        </div>
        <div class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-4 motion-safe:animate-[fade-up_.8s_.24s_ease-out_both]">
          <Link v-if="section.cta_label" :href="section.cta_url" class="btn btn-primary btn-lg">{{ section.cta_label }} <Icon name="arrow-up-right" size="size-4" /></Link>
          <ul v-if="section.items.length" class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-400">
            <li v-for="item in section.items" :key="item.id" class="flex items-center gap-2"><Icon :name="item.icon || 'circle-check'" size="size-4 shrink-0 text-primary-400" />{{ item.title }}</li>
          </ul>
        </div>
      </div>
    </div>
    <p class="pointer-events-none mt-12 select-none overflow-hidden whitespace-nowrap text-center font-display leading-[0.78] text-white/95 sm:mt-16" aria-hidden="true" style="font-size: clamp(5rem, 22vw, 21rem)">{{ site.company_name }}</p>
  </section>
</template>
