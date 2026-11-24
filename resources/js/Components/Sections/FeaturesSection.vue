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
    <div class="container-x grid gap-14 lg:grid-cols-12">
      <div class="lg:col-span-5">
        <div class="lg:sticky lg:top-32">
          <SectionHeading :section="section" align="left" />
          <RichText :html="section.body" class="mt-6" data-reveal />
          <Link v-if="section.cta_label" :href="section.cta_url" class="btn btn-secondary mt-9" data-reveal>{{ section.cta_label }} <Icon name="arrow-up-right" size="size-4" /></Link>
        </div>
      </div>
      <div class="grid gap-x-10 gap-y-14 sm:grid-cols-2 lg:col-span-7 lg:pt-4">
        <div v-for="(item, i) in section.items" :key="item.id" :class="{ 'sm:translate-y-16': i % 2 }" data-reveal :style="{ '--reveal-delay': `${i * 60}ms` }">
          <span class="text-primary-400"><Icon :name="item.icon || 'sparkles'" size="size-11" stroke="1.3" /></span>
          <h3 class="mt-6 font-display text-xl">{{ item.title }}</h3>
          <p v-if="item.text" class="mt-3 text-[0.95rem] leading-relaxed text-slate-400">{{ item.text }}</p>
          <Link v-if="item.link_url" :href="item.link_url" class="link-arrow mt-4">{{ item.link_label || 'En savoir plus' }} <Icon name="arrow-right" size="size-4" /></Link>
        </div>
      </div>
    </div>
  </section>
</template>
