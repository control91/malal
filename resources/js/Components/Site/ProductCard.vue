<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import StatusBadge from '@/Components/Ui/StatusBadge.vue';

defineProps({ product: { type: Object, required: true } });
</script>

<template>
  <article class="card card-hover group flex h-full flex-col" data-reveal>
    <div class="flex items-start justify-between gap-4">
      <img v-if="product.logo" :src="product.logo" alt="" class="size-12 rounded-2xl object-cover ring-1 ring-white/10" width="48" height="48" loading="lazy">
      <span v-else class="icon-tile"><Icon :name="product.icon || 'package'" size="size-6" /></span>
      <div class="flex flex-wrap justify-end gap-1.5"><StatusBadge :status="product.status" /><span v-if="product.is_example" class="badge badge-example">Exemple</span></div>
    </div>
    <p v-if="product.category" class="mt-6 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ product.category }}</p>
    <h3 class="mt-1.5 font-display text-xl"><Link :href="product.url" class="after:absolute after:inset-0">{{ product.name }}</Link></h3>
    <p class="mt-2 text-sm font-medium text-primary-400">{{ product.tagline }}</p>
    <p class="mt-3 line-clamp-4 flex-1 text-sm leading-relaxed text-slate-400">{{ product.summary }}</p>
    <ul v-if="product.features.length" class="mt-5 space-y-2 border-t border-white/10 pt-5 text-sm text-slate-400">
      <li v-for="feature in product.features" :key="feature" class="flex items-center gap-2"><Icon name="check" size="size-4 shrink-0 text-emerald-400" />{{ feature }}</li>
    </ul>
    <span class="link-arrow mt-6">Voir la solution <Icon name="arrow-right" size="size-4" /></span>
  </article>
</template>
