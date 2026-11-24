<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import RichText from '@/Components/Ui/RichText.vue';
import StatusBadge from '@/Components/Ui/StatusBadge.vue';
import ProductCard from '@/Components/Site/ProductCard.vue';
import PlanCard from '@/Components/Site/PlanCard.vue';
import CtaBand from '@/Components/Site/CtaBand.vue';

defineProps({ product: Object, pricingGroups: Array, related: Array });
const billing = ref('monthly');
</script>

<template>
  <section class="relative isolate overflow-hidden border-b border-white/10">
    <div class="grid-pattern absolute inset-0 -z-10" /><div class="glow -top-32 right-1/4 size-[32rem] bg-primary/30" />
    <div class="container-x grid items-center gap-12 pb-20 pt-12 sm:pt-16 lg:grid-cols-12">
      <div class="lg:col-span-7">
        <nav aria-label="Fil d'Ariane" class="text-xs text-slate-400"><Link href="/" class="hover:text-white">Accueil</Link> / <Link :href="route('solutions.index')" class="hover:text-white">Solutions</Link> / <span class="text-slate-300">{{ product.name }}</span></nav>
        <div class="mt-8 flex flex-wrap items-center gap-2">
          <Link :href="product.category_url" class="badge bg-white/10 text-slate-200 hover:bg-white/15">{{ product.category }}</Link>
          <StatusBadge :status="product.status" /><span v-if="product.is_example" class="badge badge-example">Exemple — contenu de démonstration</span>
        </div>
        <h1 class="h-display mt-5 !text-4xl sm:!text-5xl lg:!text-6xl">{{ product.name }}</h1>
        <p class="mt-4 font-display text-xl text-accent">{{ product.tagline }}</p>
        <p class="lead mt-5 max-w-2xl">{{ product.summary }}</p>
        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
          <Link v-if="product.demo_enabled && product.status !== 'coming_soon'" :href="route('demo', { solution: product.slug })" class="btn btn-primary btn-lg">Demander une démo <Icon name="arrow-right" size="size-4" /></Link>
          <Link :href="route('quote', { solution: product.slug })" class="btn btn-secondary btn-lg">{{ product.status === 'coming_soon' ? 'Être informé du lancement' : 'Demander un devis' }}</Link>
          <a v-if="product.external_url && product.status !== 'coming_soon'" :href="product.external_url" class="btn btn-ghost btn-lg" target="_blank" rel="noopener">Accéder <Icon name="external-link" size="size-4" /></a>
        </div>
      </div>
      <div class="lg:col-span-5">
        <img v-if="product.screenshots.length" :src="product.screenshots[0].image" :alt="product.screenshots[0].caption || product.name" class="w-full rounded-2xl shadow-2xl ring-1 ring-white/10" width="720" height="450">
        <div v-else class="glass rounded-3xl p-6" aria-hidden="true">
          <div class="flex items-center gap-3"><img v-if="product.logo" :src="product.logo" alt="" class="size-12 rounded-2xl" width="48" height="48"><span v-else class="grid size-12 place-items-center rounded-2xl bg-gradient-to-br from-primary to-accent text-white"><Icon :name="product.icon || 'package'" size="size-6" /></span><div><p class="font-semibold text-white">{{ product.name }}</p><p class="text-xs text-slate-400">{{ product.category }}</p></div></div>
          <ul class="mt-6 space-y-2.5"><li v-for="f in product.all_features.slice(0, 4)" :key="f.id" class="flex items-center gap-3 rounded-xl bg-white/5 px-4 py-3 text-sm text-slate-200"><Icon :name="f.icon || 'check'" size="size-4 text-accent" />{{ f.title }}</li></ul>
        </div>
      </div>
    </div>
  </section>

  <section v-if="product.description" class="section-sm">
    <div class="container-x"><RichText :html="product.description" class="mx-auto max-w-3xl" data-reveal /></div>
  </section>

  <section v-if="product.screenshots.length > 1" class="section-sm">
    <div class="container-x">
      <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3"><figure v-for="s in product.screenshots" :key="s.id" class="overflow-hidden rounded-2xl border border-white/10" data-reveal><img :src="s.image" :alt="s.caption || product.name" loading="lazy" class="aspect-video w-full object-cover" width="640" height="360"><figcaption v-if="s.caption" class="px-4 py-3 text-sm text-slate-400">{{ s.caption }}</figcaption></figure></div>
    </div>
  </section>

  <section v-for="group in pricingGroups" :key="group.name" class="section-sm" id="tarifs">
    <div class="container-x">
      <div class="flex flex-wrap items-end justify-between gap-4"><div><h2 class="font-display text-2xl" data-reveal>Tarifs — {{ group.name }}</h2><p v-if="group.description" class="mt-2 text-slate-400">{{ group.description }}</p></div>
        <div class="inline-flex rounded-lg bg-white/5 p-1 ring-1 ring-white/10" role="radiogroup" aria-label="Période de facturation"><button v-for="b in [['monthly', 'Mensuel'], ['yearly', 'Annuel']]" :key="b[0]" type="button" role="radio" :aria-checked="billing === b[0]" class="rounded-md px-4 py-1.5 text-sm font-semibold transition" :class="billing === b[0] ? 'bg-white text-night' : 'text-slate-400'" @click="billing = b[0]">{{ b[1] }}</button></div></div>
      <div class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3"><PlanCard v-for="plan in group.plans" :key="plan.id" :plan="plan" :billing="billing" /></div>
    </div>
  </section>

  <section v-if="related.length" class="section-sm muted-zone"><div class="container-x"><h2 class="font-display text-2xl">Autres solutions {{ product.category }}</h2><div class="mt-8 grid gap-5 md:grid-cols-3"><ProductCard v-for="p in related" :key="p.id" :product="p" /></div></div></section>
  <CtaBand />
</template>
