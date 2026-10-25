<script setup>
import { computed, ref } from 'vue';
import icons from '@/icons';
import Icon from '@/Components/Ui/Icon.vue';

const model = defineModel({ type: String, default: '' });
defineProps({ disabled: Boolean });
const open = ref(false);
const query = ref('');
const names = Object.keys(icons).filter((n) => !n.startsWith('brand-'));
const filtered = computed(() => names.filter((n) => n.includes(query.value.toLowerCase())));
const pick = (name) => { model.value = name; open.value = false; };
</script>

<template>
  <div class="relative">
    <button type="button" class="field-input flex items-center gap-3 text-left" :disabled="disabled" :aria-expanded="open" @click="open = !open">
      <span class="grid size-7 place-items-center rounded-lg bg-primary-50 text-primary"><Icon v-if="model" :name="model" size="size-4" /></span>
      <span class="flex-1 truncate" :class="model ? 'text-ink' : 'text-slate-400'">{{ model || 'Choisir une icône' }}</span>
      <Icon name="chevrons-up-down" size="size-4 text-slate-400" />
    </button>
    <div v-if="open" class="absolute z-30 mt-2 w-80 rounded-2xl border border-line bg-white p-3 shadow-[var(--shadow-lift)]">
      <input v-model="query" type="search" placeholder="Rechercher…" class="field-input mb-2">
      <div class="grid max-h-64 grid-cols-7 gap-1 overflow-y-auto">
        <button type="button" class="grid aspect-square place-items-center rounded-lg text-xs text-slate-400 hover:bg-surface" title="Aucune" @click="pick('')">—</button>
        <button v-for="name in filtered" :key="name" type="button" class="grid aspect-square place-items-center rounded-lg text-slate-600 hover:bg-primary-50 hover:text-primary" :class="{ 'bg-primary text-white': name === model }" :title="name" @click="pick(name)"><Icon :name="name" size="size-5" /></button>
      </div>
    </div>
  </div>
</template>
