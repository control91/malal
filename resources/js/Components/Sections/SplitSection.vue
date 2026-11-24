<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Emphasis from '@/Components/Ui/Emphasis.vue';
import RichText from '@/Components/Ui/RichText.vue';
import SectionHeading from '@/Components/Site/SectionHeading.vue';

defineProps({ section: { type: Object, required: true } });
const layers = [['app-window', 'Vos applications et vos utilisateurs'], ['workflow', 'Logiciels SaaS, API et automatisations'], ['database', 'Données, sauvegardes et sécurité'], ['server', 'Infrastructure cloud']];
</script>

<template>
  <section class="section" :id="section.anchor || undefined">
    <div class="container-x grid items-center gap-12 lg:grid-cols-2 lg:gap-20">
      <div>
        <SectionHeading :section="section" align="left" />
        <RichText :html="section.body" class="mt-6" data-reveal />
        <ul v-if="section.items.length" class="mt-8 space-y-4" data-reveal>
          <li v-for="item in section.items" :key="item.id" class="flex gap-4"><span class="icon-tile size-10 rounded-xl"><Icon :name="item.icon || 'check'" /></span><span><span class="block font-semibold text-white">{{ item.title }}</span><span v-if="item.text" class="mt-1 block text-sm text-slate-500">{{ item.text }}</span></span></li>
        </ul>
        <div class="mt-8 flex flex-wrap gap-3">
          <Link v-if="section.cta_label" :href="section.cta_url" class="btn btn-primary">{{ section.cta_label }} <Icon name="arrow-right" size="size-4" /></Link>
          <Link v-if="section.cta2_label" :href="section.cta2_url" class="btn btn-secondary">{{ section.cta2_label }}</Link>
        </div>
      </div>
      <div data-reveal>
        <img v-if="section.image" :src="section.image" :alt="section.image_alt" class="w-full rounded-3xl" loading="lazy" width="720" height="540">
        <div v-else class="relative overflow-hidden rounded-3xl border border-white/10 bg-night-800 p-8 sm:p-10">
          <div class="grid-pattern absolute inset-0" /><div class="glow -right-10 -top-10 size-72 bg-primary/40" />
          <div class="relative space-y-3" aria-hidden="true">
            <div v-for="([icon, label], i) in layers" :key="label" class="glass flex items-center gap-3 rounded-2xl px-5 py-4 text-sm font-medium text-white" :style="{ marginLeft: `${i * 1.5}rem` }"><Icon :name="icon" size="size-5 text-accent" />{{ label }}</div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
