<script setup>
defineProps({ status: String, label: String });
const tones = {
    new: 'bg-primary-50 text-primary-700 ring-primary-200', in_progress: 'bg-amber-50 text-amber-700 ring-amber-200',
    answered: 'bg-emerald-50 text-emerald-700 ring-emerald-200', won: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    spam: 'bg-rose-50 text-rose-700 ring-rose-200', lost: 'bg-slate-100 text-slate-600 ring-slate-200',
};
</script>

<template>
  <span class="status-pill" :class="tones[status] ?? 'bg-slate-50 text-slate-700 ring-slate-200'">{{ label }}</span>
</template>
