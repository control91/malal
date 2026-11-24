<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import { formatDateTime } from '@/format';

defineProps({ activities: Array, showInquiry: Boolean });
const icons = { email: 'mail', call: 'phone', meeting: 'users', demo: 'monitor', inquiry: 'inbox', status: 'refresh-cw', assignment: 'user-check', follow_up: 'calendar-check', system: 'settings', note: 'notebook-pen' };
const tones = { email: 'bg-primary-50 text-primary', call: 'bg-emerald-50 text-emerald-600', meeting: 'bg-emerald-50 text-emerald-600', demo: 'bg-emerald-50 text-emerald-600', inquiry: 'bg-accent-100 text-accent-600', follow_up: 'bg-amber-50 text-amber-600' };
</script>

<template>
  <ol class="relative space-y-5 before:absolute before:inset-y-1 before:left-[1.05rem] before:w-px before:bg-line">
    <li v-for="a in activities" :key="a.id" class="relative flex gap-4">
      <span class="relative z-10 grid size-9 shrink-0 place-items-center rounded-full ring-4 ring-white" :class="tones[a.kind] ?? 'bg-surface text-slate-500'"><Icon :name="icons[a.kind] ?? 'notebook-pen'" size="size-4" /></span>
      <div class="min-w-0 flex-1 pt-1">
        <p class="text-xs text-slate-500"><span class="font-semibold text-ink">{{ a.kind_label }}</span> · {{ a.author }} · <time :datetime="a.at">{{ formatDateTime(a.at) }}</time><template v-if="showInquiry && a.inquiry"> · <Link :href="route('admin.inquiries.show', a.inquiry.id)" class="text-primary">{{ a.inquiry.reference }}</Link></template></p>
        <p class="mt-1 whitespace-pre-line break-words text-sm text-slate-700">{{ a.content }}</p>
      </div>
    </li>
    <li v-if="!activities.length" class="text-sm text-slate-400">Aucune activité.</li>
  </ol>
</template>
