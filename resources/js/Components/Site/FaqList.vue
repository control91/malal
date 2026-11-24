<script setup>
import { ref } from 'vue';
import Icon from '@/Components/Ui/Icon.vue';
import RichText from '@/Components/Ui/RichText.vue';

const props = defineProps({ faqs: { type: Array, required: true }, openFirst: { type: Boolean, default: false } });
const open = ref(props.openFirst ? 0 : -1);
</script>

<template>
  <div class="grid gap-4 lg:grid-cols-2">
    <div v-for="(faq, i) in faqs" :key="faq.id" class="h-fit rounded-xl border transition" :class="open === i ? 'border-primary bg-primary/[0.08]' : 'border-white/10 bg-white/[0.02]'">
      <h3>
        <button type="button" class="flex w-full items-center justify-between gap-6 px-6 py-5 text-left font-display text-lg" :aria-expanded="open === i" :aria-controls="`faq-${faq.id}`" @click="open = open === i ? -1 : i">
          <span><span class="text-primary-400">{{ String(i + 1).padStart(2, '0') }}.</span> {{ faq.question }}</span>
          <span class="grid size-8 shrink-0 place-items-center rounded-full border border-white/15 transition" :class="{ 'rotate-45 border-primary bg-primary': open === i }"><Icon name="plus" size="size-4" /></span>
        </button>
      </h3>
      <div v-show="open === i" :id="`faq-${faq.id}`"><RichText :html="faq.answer" class="px-6 pb-6 text-[0.95rem]" /></div>
    </div>
  </div>
</template>
