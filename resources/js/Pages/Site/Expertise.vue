<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Emphasis from '@/Components/Ui/Emphasis.vue';
import RichText from '@/Components/Ui/RichText.vue';
import StatusBadge from '@/Components/Ui/StatusBadge.vue';
import ProductCard from '@/Components/Site/ProductCard.vue';
import CaseCard from '@/Components/Site/CaseCard.vue';
import FaqList from '@/Components/Site/FaqList.vue';
import CtaBand from '@/Components/Site/CtaBand.vue';

const props = defineProps({ category: Object, groups: Array, products: Array, faqs: Array, cases: Array });
const site = computed(() => usePage().props.site);
const groupTitle = { available: 'Services disponibles', beta: 'En bêta', coming_soon: 'À venir' };
</script>

<template>
  <section class="relative isolate overflow-hidden border-b border-white/10">
    <div class="grid-pattern absolute inset-0 -z-10" /><div class="glow -top-40 left-1/3 size-[34rem] bg-primary/30" />
    <div class="container-x grid items-center gap-12 pb-20 pt-12 sm:pt-16 lg:grid-cols-12">
      <div class="lg:col-span-7">
        <nav aria-label="Fil d'Ariane" class="text-xs text-slate-400"><Link href="/" class="hover:text-white">Accueil</Link> <span aria-hidden="true">/</span> <span class="text-slate-300">{{ category.name }}</span></nav>
        <p class="eyebrow mt-8">{{ category.name }}</p>
        <h1 class="h-display mt-5 !text-4xl sm:!text-5xl lg:!text-6xl"><Emphasis :text="category.hero_title || category.name" /></h1>
        <p class="lead mt-6 max-w-2xl">{{ category.hero_subtitle || category.tagline }}</p>
        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
          <Link :href="route('quote', { domaine: category.slug })" class="btn btn-primary btn-lg">Parler à un expert <Icon name="arrow-right" size="size-4" /></Link>
          <a v-if="products.length" href="#solutions" class="btn btn-secondary btn-lg">Voir les solutions</a>
        </div>
      </div>
      <div class="hidden lg:col-span-5 lg:block">
        <img v-if="category.image" :src="category.image" alt="" class="w-full rounded-3xl" width="560" height="460">
        <div v-else class="glass relative rounded-3xl p-8">
          <span class="grid size-16 place-items-center rounded-2xl bg-gradient-to-br from-primary to-accent text-white"><Icon :name="category.icon || 'layers'" size="size-8" /></span>
          <ul class="mt-8 space-y-3 text-sm"><li v-for="group in groups" :key="group.status" class="flex items-center justify-between rounded-xl bg-white/5 px-4 py-3"><span class="text-slate-300">{{ group.services.length }} service{{ group.services.length > 1 ? 's' : '' }}</span><StatusBadge :status="group.status" /></li></ul>
        </div>
      </div>
    </div>
  </section>

  <section v-if="category.description" class="section-sm"><div class="container-x max-w-3xl"><RichText :html="category.description" class="prose-lg" data-reveal /></div></section>
  <div v-if="category.show_hosting_disclosure && site.hosting_disclosure" class="container-x" :class="{ 'mt-14': !category.description }">
    <div class="alert alert-info items-start" data-reveal><Icon name="info" class="mt-0.5 shrink-0" /><div><p class="font-semibold">Transparence sur notre infrastructure</p><p class="mt-1">{{ site.hosting_disclosure }}</p></div></div>
  </div>

  <section v-for="(group, gi) in groups" :key="group.status" :class="gi === 0 ? 'section-sm' : 'pb-14 sm:pb-20'">
    <div class="container-x">
      <div class="flex flex-wrap items-center gap-3" data-reveal><h2 class="font-display text-2xl">{{ groupTitle[group.status] }}</h2><StatusBadge :status="group.status" /></div>
      <p v-if="group.status === 'beta'" class="mt-2 text-sm text-slate-500">Services ouverts à un nombre limité de clients, susceptibles d'évoluer.</p>
      <p v-else-if="group.status === 'coming_soon'" class="mt-2 text-sm text-slate-500">En préparation : contactez-nous pour être prévenu de leur ouverture.</p>
      <div class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        <article v-for="service in group.services" :key="service.id" class="card" :class="{ 'border-dashed opacity-80': group.status === 'coming_soon' }" data-reveal>
          <span class="icon-tile"><Icon :name="service.icon || 'circle-dot'" size="size-6" /></span>
          <h3 class="mt-5 font-display text-lg">{{ service.name }}</h3>
          <p class="mt-2 text-sm leading-relaxed text-slate-400">{{ service.summary }}</p>
          <ul v-if="service.details.length" class="mt-4 space-y-1.5 text-sm text-slate-400"><li v-for="line in service.details" :key="line" class="flex gap-2"><Icon name="check" size="size-4 mt-0.5 shrink-0 text-emerald-400" />{{ line }}</li></ul>
        </article>
      </div>
    </div>
  </section>

  <section v-if="products.length" id="solutions" class="section muted-zone">
    <div class="container-x">
      <div class="max-w-2xl" data-reveal><p class="eyebrow">Solutions</p><h2 class="h-section mt-4">Nos solutions {{ category.name }}</h2></div>
      <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3"><ProductCard v-for="product in products" :key="product.id" :product="product" /></div>
    </div>
  </section>

  <section v-if="category.demo_space_enabled" class="section-sm">
    <div class="container-x">
      <div class="relative overflow-hidden rounded-[2rem] border border-dashed border-primary/40 bg-primary/[0.05] p-8 sm:p-12" data-reveal>
        <div class="grid items-center gap-8 lg:grid-cols-2">
          <div><span class="badge badge-coming_soon">Bientôt</span><h2 class="mt-4 font-display text-2xl sm:text-3xl">Espace de démonstrations interactives</h2>
            <p class="mt-3 text-slate-400">Cet espace accueillera des démonstrations à essayer en ligne. En attendant, nous présentons nos prototypes lors d'une démonstration personnalisée.</p>
            <Link :href="route('demo')" class="btn btn-primary mt-6">Demander une démonstration <Icon name="arrow-right" size="size-4" /></Link></div>
          <div class="rounded-2xl border border-white/10 bg-night-800 p-5" aria-hidden="true">
            <div class="flex items-center gap-2 border-b border-white/10 pb-3 text-xs text-slate-500"><span class="size-2.5 rounded-full bg-rose-400/60" /><span class="size-2.5 rounded-full bg-amber-400/60" /><span class="size-2.5 rounded-full bg-emerald-400/60" /><span class="ml-2">assistant — démo</span></div>
            <div class="mt-4 space-y-3 text-sm"><p class="ml-auto w-fit max-w-[80%] rounded-2xl rounded-br-md bg-primary px-4 py-2.5 text-white">Résume ce contrat en 5 points.</p><p class="w-fit max-w-[85%] rounded-2xl rounded-bl-md bg-white/5 px-4 py-2.5 text-slate-300">Exemple de réponse — la démonstration interactive arrive prochainement.</p></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section v-if="cases.length" class="section-sm"><div class="container-x"><h2 class="font-display text-2xl" data-reveal>Réalisations associées</h2><div class="mt-8 grid gap-6 md:grid-cols-3"><CaseCard v-for="item in cases" :key="item.id" :item="item" /></div></div></section>
  <section v-if="faqs.length" class="section-sm"><div class="container-x"><p class="eyebrow">FAQ</p><h2 class="h-section mt-4 !text-3xl">Questions fréquentes</h2><div class="mt-10"><FaqList :faqs="faqs" /></div></div></section>
  <CtaBand />
</template>
