<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Emphasis from '@/Components/Ui/Emphasis.vue';
import RichText from '@/Components/Ui/RichText.vue';
import SectionHeading from '@/Components/Site/SectionHeading.vue';

defineProps({ section: { type: Object, required: true } });
</script>

<template>
  <section v-if="section.data?.length" class="section-sm" :id="section.anchor || undefined">
    <div class="container-x">
      <SectionHeading :section="section" />
      <ul class="mt-10 flex flex-wrap items-center justify-center gap-x-12 gap-y-8">
        <li v-for="partner in section.data" :key="partner.id"><a :href="partner.url || '#'" target="_blank" rel="noopener" class="block opacity-70 grayscale transition hover:opacity-100 hover:grayscale-0">
          <img v-if="partner.logo" :src="partner.logo" :alt="partner.name" class="h-10 w-auto" loading="lazy" height="40"><span v-else class="font-display text-lg text-slate-400">{{ partner.name }}</span>
        </a></li>
      </ul>
    </div>
  </section>
</template>
