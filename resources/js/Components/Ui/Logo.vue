<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({ dark: { type: Boolean, default: false } });
const site = computed(() => usePage().props.site);
const uploaded = computed(() => (props.dark ? site.value.logo_dark : site.value.logo));
</script>

<template>
  <img v-if="uploaded" :src="uploaded" :alt="site.company_name" class="h-8 w-auto" width="140" height="32">
  <span v-else class="inline-flex items-center gap-3">
    <img src="/img/logo-mark.svg" alt="" class="h-7 w-auto shrink-0" width="50" height="28">
    <span class="font-display text-xl font-extrabold tracking-tight" :class="dark ? 'text-white' : 'text-ink'">{{ site.company_name }}</span>
  </span>
</template>
