<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Emphasis from '@/Components/Ui/Emphasis.vue';
import RichText from '@/Components/Ui/RichText.vue';
import SectionHeading from '@/Components/Site/SectionHeading.vue';
import ProductCard from '@/Components/Site/ProductCard.vue';

defineProps({ section: { type: Object, required: true } });
</script>

<template>
  <section class="section" :class="{ 'muted-zone': section.theme === 'muted' }" :id="section.anchor || undefined">
    <div class="container-x">
      <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
        <SectionHeading :section="section" align="left" />
        <Link v-if="section.cta_label" :href="section.cta_url" class="btn btn-secondary shrink-0" data-reveal>{{ section.cta_label }} <Icon name="arrow-right" size="size-4" /></Link>
      </div>
      <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        <ProductCard v-for="product in section.data ?? []" :key="product.id" :product="product" />
        <div v-if="!section.data?.length" class="card col-span-full text-center text-slate-500">Nos solutions seront présentées ici très prochainement.</div>
      </div>
    </div>
  </section>
</template>
