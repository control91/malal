<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import SectionRenderer from '@/Components/Sections/SectionRenderer.vue';

defineProps({ sections: { type: Array, required: true } });
const site = computed(() => usePage().props.site);
</script>

<template>
  <SectionRenderer v-if="sections.length" :sections="sections" />
  <section v-else class="dark-zone"><div class="container-x py-32 text-center">
    <h1 class="h-display">{{ site.company_name }}</h1><p class="lead mx-auto mt-6 max-w-xl">{{ site.tagline }}</p>
    <p v-if="$page.props.auth" class="mt-8 text-sm text-slate-400">La page d'accueil n'a pas encore de contenu : lancez <code>php artisan app:seed-site</code> ou éditez-la depuis le back-office.</p>
  </div></section>
</template>
