<script setup>
import { Head, router } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
import PageHeading from '@/Components/Admin/PageHeading.vue';
import { timeAgo } from '@/format';

defineProps({ notifications: Object });
</script>

<template>
  <Head title="Notifications" />
  <PageHeading title="Notifications" icon="bell"><button class="btn btn-secondary" @click="router.post(route('admin.notifications.read-all'))">Tout marquer lu</button></PageHeading>
  <div class="dash-card max-w-3xl divide-y divide-line">
    <button v-for="n in notifications.data" :key="n.id" type="button" class="flex w-full gap-3 px-4 py-3 text-left hover:bg-surface" :class="{ 'bg-primary-50/40': !n.read_at }" @click="router.post(route('admin.notifications.read', n.id))">
      <span class="grid size-8 shrink-0 place-items-center rounded-full bg-white text-primary ring-1 ring-line"><Icon :name="n.icon" size="size-4" /></span>
      <span class="min-w-0 flex-1"><span class="block truncate text-sm font-medium text-ink">{{ n.title }}</span><span v-if="n.body" class="block truncate text-xs text-slate-500">{{ n.body }}</span><span class="text-[11px] text-slate-400">{{ timeAgo(n.created_at) }}</span></span>
      <span v-if="!n.read_at" class="mt-1.5 size-2 shrink-0 rounded-full bg-primary" />
    </button>
    <p v-if="!notifications.data.length" class="px-4 py-8 text-center text-sm text-slate-400">Aucune notification.</p>
  </div>
  <div class="mt-6 max-w-3xl"><Pagination :meta="notifications" admin /></div>
</template>
