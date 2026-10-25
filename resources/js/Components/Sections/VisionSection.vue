<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import RichText from '@/Components/Ui/RichText.vue';
import SectionHeading from '@/Components/Site/SectionHeading.vue';
import AfricaMap from '@/Components/Site/AfricaMap.vue';

defineProps({ section: { type: Object, required: true } });
const phase = ref(1);
const auto = ref(true);
let timer;
onMounted(() => {
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        timer = setInterval(() => { if (auto.value) phase.value = (phase.value % 3) + 1; }, 4200);
    }
});
onBeforeUnmount(() => clearInterval(timer));
const pick = (n) => { phase.value = n; auto.value = false; };
</script>

<template>
  <section class="section dark-zone" :id="section.anchor || undefined">
    <div class="grid-pattern absolute inset-0 -z-10" /><div class="glow left-1/2 top-1/3 size-[40rem] -translate-x-1/2 bg-primary/20" />
    <div class="container-x grid items-center gap-14 lg:grid-cols-2">
      <div>
        <SectionHeading :section="section" align="left" />
        <RichText :html="section.body" class="mt-6" data-reveal />
        <ol class="mt-10 space-y-3" role="tablist" aria-label="Étapes de la vision">
          <li v-for="(item, i) in section.items.slice(0, 3)" :key="item.id">
            <button type="button" role="tab" :aria-selected="phase === i + 1" class="flex w-full gap-4 rounded-2xl p-5 text-left ring-1 transition"
                    :class="phase === i + 1 ? 'bg-white/10 ring-primary/60' : 'bg-white/[0.03] ring-white/10 hover:bg-white/[0.06]'" @click="pick(i + 1)">
              <span class="grid size-9 shrink-0 place-items-center rounded-full text-sm font-bold transition" :class="phase === i + 1 ? 'bg-accent text-ink' : 'bg-white/10 text-slate-300'">{{ i + 1 }}</span>
              <span>
                <span class="flex flex-wrap items-center gap-2"><span class="font-semibold text-white">{{ item.title }}</span><span v-if="item.badge" class="badge bg-white/10 text-slate-300">{{ item.badge }}</span>
                  <span v-if="item.progress === 'in_progress'" class="badge bg-emerald-400/15 text-emerald-300">En cours</span><span v-else-if="item.progress === 'planned'" class="badge bg-white/5 text-slate-400">Ambition</span><span v-else-if="item.progress === 'done'" class="badge bg-emerald-400/15 text-emerald-300">Réalisé</span></span>
                <span v-if="item.text" class="mt-1.5 block text-sm leading-relaxed text-slate-400">{{ item.text }}</span>
              </span>
            </button>
          </li>
        </ol>
      </div>
      <div class="relative" data-reveal>
        <AfricaMap :phase="phase" />
        <p class="mt-4 text-center text-xs text-slate-500">Représentation illustrative d'une ambition de développement — pas d'une présence existante.</p>
      </div>
    </div>
  </section>
</template>
