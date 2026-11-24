<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
import PageHeader from '@/Components/Site/PageHeader.vue';
import ArticleCard from '@/Components/Site/ArticleCard.vue';

const props = defineProps({ articles: Object, heading: String, description: String, q: String, categories: Array, current_category: String, current_tag: String });
const search = ref(props.q);
let timer;
watch(search, (value) => { clearTimeout(timer); timer = setTimeout(() => router.get(window.location.pathname, value ? { q: value } : {}, { preserveState: true, preserveScroll: true, replace: true }), 350); });
</script>

<template>
  <PageHeader eyebrow="Ressources" :title="heading" :subtitle="description" :parent="current_category || current_tag ? { label: 'Blog', url: route('blog.index') } : null" />
  <section class="section-sm">
    <div class="container-x">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <nav aria-label="Catégories" class="-mx-1 flex gap-1 overflow-x-auto pb-1">
          <Link :href="route('blog.index')" class="tab whitespace-nowrap" :class="{ 'is-active': !current_category && !current_tag }">Tous</Link>
          <Link v-for="c in categories" :key="c.slug" :href="route('blog.category', c.slug)" class="tab whitespace-nowrap" :class="{ 'is-active': current_category === c.slug }">{{ c.name }}</Link>
        </nav>
        <label class="relative md:w-72"><span class="sr-only">Rechercher un article</span><span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-slate-500"><Icon name="search" size="size-4" /></span><input v-model="search" type="search" placeholder="Rechercher…" class="field-input !pl-10"></label>
      </div>
      <p v-if="q" class="mt-6 text-sm text-slate-500">{{ articles.total }} résultat{{ articles.total > 1 ? 's' : '' }} pour « {{ q }} »</p>
      <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <ArticleCard v-for="a in articles.data" :key="a.id" :article="a" />
        <div v-if="!articles.data.length" class="col-span-full rounded-[var(--radius-card)] border border-dashed border-white/15 p-12 text-center text-slate-500">Aucun article pour le moment.</div>
      </div>
      <Pagination :meta="articles" />
    </div>
  </section>
</template>
