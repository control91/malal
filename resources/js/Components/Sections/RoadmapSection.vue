<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Emphasis from '@/Components/Ui/Emphasis.vue';
import RichText from '@/Components/Ui/RichText.vue';
import SectionHeading from '@/Components/Site/SectionHeading.vue';

defineProps({ section: { type: Object, required: true } });
</script>

<template>
  <section class="section" :class="{ 'muted-zone': section.theme === 'muted' }" :id="section.anchor || undefined">
    <div class="container-x">
      <SectionHeading :section="section" />
      <ol class="relative mx-auto mt-14 max-w-3xl space-y-6 before:absolute before:inset-y-2 before:left-[1.15rem] before:w-px before:bg-gradient-to-b before:from-primary before:via-accent before:to-white/10 sm:before:left-[1.4rem]">
        <li v-for="item in section.items" :key="item.id" class="relative flex gap-5 sm:gap-6" data-reveal>
          <span class="relative z-10 grid size-10 shrink-0 place-items-center rounded-full ring-4 ring-night sm:size-12" :class="item.progress === 'done' ? 'bg-emerald-500 text-white' : item.progress === 'in_progress' ? 'bg-primary text-white' : 'bg-white/10 text-slate-400'">
            <Icon :name="item.progress === 'done' ? 'check' : item.icon || 'flag'" />
          </span>
          <div class="card flex-1 !p-5 sm:!p-6">
            <div class="flex flex-wrap items-center gap-2">
              <span v-if="item.badge" class="text-xs font-semibold uppercase tracking-wider text-primary-400">{{ item.badge }}</span>
              <span v-if="item.progress === 'done'" class="badge badge-available">Réalisé</span><span v-else-if="item.progress === 'in_progress'" class="badge badge-beta">En cours</span><span v-else-if="item.progress === 'planned'" class="badge badge-coming_soon">Prévu</span>
            </div>
            <h3 class="mt-2 font-display text-lg">{{ item.title }}</h3>
            <p v-if="item.text" class="mt-2 text-sm leading-relaxed text-slate-400">{{ item.text }}</p>
          </div>
        </li>
      </ol>
      <p class="mx-auto mt-8 max-w-3xl text-center text-xs text-slate-500">Les étapes « Prévu » sont des objectifs : leur calendrier peut évoluer.</p>
    </div>
  </section>
</template>
