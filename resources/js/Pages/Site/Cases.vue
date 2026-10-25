<script setup>
import { Link } from '@inertiajs/vue3';
import PageHeader from '@/Components/Site/PageHeader.vue';
import CaseCard from '@/Components/Site/CaseCard.vue';
import CtaBand from '@/Components/Site/CtaBand.vue';

defineProps({ cases: Array, sectors: Array, current_sector: String, testimonials: Array });
</script>

<template>
  <PageHeader eyebrow="Réalisations" title="Projets et *résultats*" subtitle="Des projets présentés avec l'accord de nos clients et des résultats vérifiables." />
  <section class="section-sm">
    <div class="container-x">
      <nav v-if="sectors.length > 1" class="flex flex-wrap gap-1" aria-label="Secteurs">
        <Link :href="route('cases.index')" class="tab" :class="{ 'is-active': !current_sector }">Tous les secteurs</Link>
        <Link v-for="s in sectors" :key="s" :href="route('cases.index', { secteur: s })" class="tab" :class="{ 'is-active': current_sector === s }">{{ s }}</Link>
      </nav>
      <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <CaseCard v-for="item in cases" :key="item.id" :item="item" />
        <div v-if="!cases.length" class="col-span-full rounded-[var(--radius-card)] border border-dashed border-white/15 p-12 text-center"><p class="font-semibold text-white">Nos premières études de cas seront publiées prochainement.</p><p class="mt-2 text-sm text-slate-500">Nous ne publions un projet qu'avec l'accord explicite du client.</p></div>
      </div>
    </div>
  </section>
  <section v-if="testimonials.length" class="section-sm muted-zone"><div class="container-x"><h2 class="font-display text-2xl">Ils témoignent</h2>
    <div class="mt-8 grid gap-6 md:grid-cols-3"><figure v-for="t in testimonials" :key="t.id" class="card"><blockquote class="text-slate-300">« {{ t.quote }} »</blockquote><figcaption class="mt-4 text-sm font-semibold text-white">{{ t.author_name }}<span class="block font-normal text-slate-500">{{ t.author_role }}<template v-if="t.organization"> · {{ t.organization }}</template></span></figcaption></figure></div>
  </div></section>
  <CtaBand />
</template>
