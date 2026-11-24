<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import StatusBadge from '@/Components/Ui/StatusBadge.vue';
import PageHeader from '@/Components/Site/PageHeader.vue';
import CtaBand from '@/Components/Site/CtaBand.vue';

defineProps({ category: Object, products: Array });
</script>

<template>
  <PageHeader eyebrow="Intelligence Artificielle" title="Nos applications *IA*" subtitle="Assistants, automatisation, analyse de documents, API : découvrez nos projets d'Intelligence Artificielle, avec des exemples concrets de chaque application." />

  <section class="section-sm">
    <div class="container-x space-y-8">
      <article v-for="(product, index) in products" :key="product.id" class="card grid gap-10 lg:grid-cols-2 lg:items-center" data-reveal>
        <div :class="index % 2 ? 'lg:order-2' : ''">
          <div class="flex flex-wrap items-center gap-3">
            <img v-if="product.logo" :src="product.logo" alt="" class="size-12 rounded-2xl object-cover ring-1 ring-white/10" width="48" height="48" loading="lazy">
            <span v-else class="icon-tile"><Icon :name="product.icon || 'sparkles'" size="size-6" /></span>
            <StatusBadge :status="product.status" />
            <span v-if="product.is_example" class="badge badge-example">Exemple</span>
          </div>
          <h2 class="mt-6 font-display text-2xl sm:text-3xl">{{ product.name }}</h2>
          <p class="mt-2 font-medium text-primary-400">{{ product.tagline }}</p>
          <p class="mt-4 leading-relaxed text-slate-400">{{ product.summary }}</p>
          <ul v-if="product.all_features.length" class="mt-6 grid gap-2 text-sm text-slate-300 sm:grid-cols-2">
            <li v-for="feature in product.all_features" :key="feature" class="flex items-start gap-2"><Icon name="check" size="size-4 mt-0.5 shrink-0 text-emerald-400" />{{ feature }}</li>
          </ul>
          <div class="mt-8 flex flex-wrap gap-3">
            <a v-if="product.external_url && product.status !== 'coming_soon'" :href="product.external_url" class="btn btn-primary" target="_blank" rel="noopener">Essayer l'application <Icon name="external-link" size="size-4" /></a>
            <Link v-if="product.demo_enabled && product.status !== 'coming_soon'" :href="route('demo', { solution: product.slug })" :class="product.external_url ? 'btn btn-ghost' : 'btn btn-primary'">Demander une démo</Link>
            <Link :href="product.url" class="btn btn-ghost">Voir le projet <Icon name="arrow-right" size="size-4" /></Link>
          </div>
        </div>

        <div :class="index % 2 ? 'lg:order-1' : ''">
          <template v-if="product.screenshots.length">
            <figure class="overflow-hidden rounded-2xl border border-white/10">
              <img :src="product.screenshots[0].image" :alt="product.screenshots[0].caption || product.name" class="aspect-video w-full object-cover" width="720" height="405" loading="lazy">
              <figcaption v-if="product.screenshots[0].caption" class="px-4 py-3 text-sm text-slate-400">{{ product.screenshots[0].caption }}</figcaption>
            </figure>
            <div v-if="product.screenshots.length > 1" class="mt-4 grid grid-cols-2 gap-4">
              <figure v-for="shot in product.screenshots.slice(1)" :key="shot.id" class="overflow-hidden rounded-xl border border-white/10">
                <img :src="shot.image" :alt="shot.caption || product.name" class="aspect-video w-full object-cover" width="340" height="190" loading="lazy">
                <figcaption v-if="shot.caption" class="px-3 py-2 text-xs text-slate-400">{{ shot.caption }}</figcaption>
              </figure>
            </div>
          </template>
          <div v-else class="flex aspect-video flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-white/15 text-slate-500">
            <Icon name="image" size="size-8" />
            <p class="text-sm">Exemples d'utilisation bientôt disponibles</p>
          </div>
        </div>
      </article>

      <div v-if="!products.length" class="rounded-[var(--radius-card)] border border-dashed border-white/15 p-12 text-center">
        <p class="font-semibold text-white">Nos applications d'IA seront présentées ici prochainement.</p>
        <Link :href="route('contact')" class="link-arrow mt-3">Parlez-nous de votre projet <Icon name="arrow-right" size="size-4" /></Link>
      </div>

      <p v-if="category && products.length" class="text-center"><Link :href="category.url" class="link-arrow">Découvrir notre expertise en {{ category.name }} <Icon name="arrow-right" size="size-4" /></Link></p>
    </div>
  </section>

  <CtaBand title="Un projet d'IA en tête ?" text="Assistant interne, automatisation de documents, analyse de données : nous concevons l'application adaptée à votre organisation." />
</template>
