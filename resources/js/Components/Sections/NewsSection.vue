<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Emphasis from '@/Components/Ui/Emphasis.vue';
import RichText from '@/Components/Ui/RichText.vue';
import SectionHeading from '@/Components/Site/SectionHeading.vue';
import ArticleCard from '@/Components/Site/ArticleCard.vue';

defineProps({ section: { type: Object, required: true } });
</script>

<template>
  <section v-if="section.data?.length" class="section" :id="section.anchor || undefined">
    <div class="container-x">
      <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
        <SectionHeading :section="section" align="left" />
        <Link :href="section.cta_url || '/blog'" class="btn btn-secondary shrink-0">{{ section.cta_label || 'Toutes les actualités' }} <Icon name="arrow-right" size="size-4" /></Link>
      </div>
      <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3"><ArticleCard v-for="article in section.data.slice(0, 3)" :key="article.id" :article="article" /></div>
    </div>
  </section>
</template>
