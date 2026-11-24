<script setup>
import SectionHeading from '@/Components/Site/SectionHeading.vue';
import CaseCard from '@/Components/Site/CaseCard.vue';

defineProps({ section: { type: Object, required: true } });
</script>

<template>
  <section v-if="section.data?.length" class="section" :id="section.anchor || undefined">
    <div class="container-x">
      <SectionHeading :section="section" />
      <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3"><CaseCard v-for="item in section.data" :key="item.id" :item="item" /></div>
    </div>
  </section>
</template>
