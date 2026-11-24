<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import { formatDateTime, money } from '@/format';

defineProps({ column: Object, value: [String, Number, Boolean, Object, Array, null], url: String });
const isFile = (v) => typeof v === 'string' && /\.(pdf|docx|xlsx|zip|txt|csv|pptx)$/i.test(v);
</script>

<template>
  <template v-if="column.type === 'title'"><Link v-if="url" :href="url" class="font-semibold text-ink hover:text-primary">{{ value ?? '—' }}</Link><span v-else class="font-semibold text-ink">{{ value ?? '—' }}</span></template>
  <template v-else-if="column.type === 'bool'"><span v-if="value" class="inline-flex text-emerald-500"><Icon name="circle-check" label="Oui" /></span><span v-else class="inline-flex text-slate-300"><Icon name="circle" label="Non" /></span></template>
  <template v-else-if="column.type === 'badge'"><span v-if="value" class="status-pill bg-slate-50 text-slate-700 ring-slate-200">{{ value }}</span></template>
  <template v-else-if="column.type === 'datetime' || column.type === 'date'"><span v-if="value" class="whitespace-nowrap text-slate-500">{{ formatDateTime(value) }}</span><span v-else class="text-slate-300">—</span></template>
  <template v-else-if="column.type === 'image'">
    <span v-if="isFile(value)" class="grid size-10 place-items-center rounded-lg bg-surface text-slate-400"><Icon name="file-text" /></span>
    <img v-else-if="value" :src="value" alt="" class="size-10 rounded-lg object-cover ring-1 ring-line" loading="lazy">
    <span v-else class="grid size-10 place-items-center rounded-lg bg-surface text-slate-300"><Icon name="image" size="size-4" /></span>
  </template>
  <template v-else-if="column.type === 'money'"><span v-if="value !== null && value !== undefined" class="whitespace-nowrap font-medium">{{ money(value) }}</span><span v-else class="text-slate-300">—</span></template>
  <span v-else-if="column.type === 'muted'" class="text-slate-500">{{ value ?? '—' }}</span>
  <template v-else>{{ value ?? '—' }}</template>
</template>
