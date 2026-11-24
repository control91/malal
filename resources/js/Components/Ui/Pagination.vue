<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from './Icon.vue';

defineProps({ meta: { type: Object, required: true }, admin: { type: Boolean, default: false } });
</script>

<template>
  <nav v-if="meta.last_page > 1" class="flex items-center justify-center gap-2" :class="admin ? '' : 'mt-12'" aria-label="Pagination">
    <Link v-if="meta.prev_page_url" :href="meta.prev_page_url" preserve-scroll class="btn btn-secondary btn-sm" rel="prev"><Icon name="chevron-left" size="size-4" /> Précédent</Link>
    <span class="px-3 text-sm text-slate-500">Page {{ meta.current_page }} sur {{ meta.last_page }}</span>
    <Link v-if="meta.next_page_url" :href="meta.next_page_url" preserve-scroll class="btn btn-secondary btn-sm" rel="next">Suivant <Icon name="chevron-right" size="size-4" /></Link>
  </nav>
</template>
