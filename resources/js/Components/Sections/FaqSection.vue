<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Emphasis from '@/Components/Ui/Emphasis.vue';
import RichText from '@/Components/Ui/RichText.vue';
import SectionHeading from '@/Components/Site/SectionHeading.vue';
import FaqList from '@/Components/Site/FaqList.vue';

defineProps({ section: { type: Object, required: true } });
</script>

<template>
  <section v-if="section.data?.length" class="section" :id="section.anchor || undefined">
    <div class="container-x">
      <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end"><SectionHeading :section="section" align="left" /><Link href="/faq" class="link-arrow shrink-0">Toutes les questions <Icon name="arrow-right" size="size-4" /></Link></div>
      <div class="mt-12"><FaqList :faqs="section.data" open-first /></div>
    </div>
  </section>
</template>
