<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Emphasis from '@/Components/Ui/Emphasis.vue';
import RichText from '@/Components/Ui/RichText.vue';
import SectionHeading from '@/Components/Site/SectionHeading.vue';

defineProps({ section: { type: Object, required: true } });
</script>

<template>
  <section class="section-sm" :id="section.anchor || undefined">
    <div class="container-x max-w-3xl">
      <SectionHeading :section="section" align="left" />
      <RichText :html="section.body" class="prose-lg mt-8" data-reveal />
      <Link v-if="section.cta_label" :href="section.cta_url" class="btn btn-primary mt-8">{{ section.cta_label }} <Icon name="arrow-right" size="size-4" /></Link>
    </div>
  </section>
</template>
