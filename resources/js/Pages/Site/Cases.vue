<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import PageHeader from '@/Components/Site/PageHeader.vue';
import CtaBand from '@/Components/Site/CtaBand.vue';

defineProps({ cases: Array, sectors: Array, current_sector: String, testimonials: Array });

// Main visual: the cover, otherwise the first gallery image; the rest become thumbnails.
const visuals = (item) => {
    const gallery = item.images.map((i) => ({ src: i.image, caption: i.caption, id: i.id }));
    const main = item.cover ? { src: item.cover, caption: '', id: 'cover' } : gallery.shift();
    return { main, thumbs: gallery.slice(0, 2) };
};
</script>

<template>
  <PageHeader eyebrow="Réalisations" title="Projets et *résultats*" subtitle="Des projets présentés avec l'accord de nos clients et des résultats vérifiables." />
  <section class="section-sm">
    <div class="container-x">
      <nav v-if="sectors.length > 1" class="flex flex-wrap gap-1" aria-label="Secteurs">
        <Link :href="route('cases.index')" class="tab" :class="{ 'is-active': !current_sector }">Tous les secteurs</Link>
        <Link v-for="s in sectors" :key="s" :href="route('cases.index', { secteur: s })" class="tab" :class="{ 'is-active': current_sector === s }">{{ s }}</Link>
      </nav>

      <div class="mt-10 space-y-8">
        <article v-for="(item, index) in cases" :key="item.id" class="card grid gap-10 lg:grid-cols-2 lg:items-center" data-reveal>
          <div :class="index % 2 ? 'lg:order-2' : ''">
            <div class="flex flex-wrap items-center gap-2">
              <span class="badge bg-white/90 text-ink">{{ item.sector }}</span>
              <span v-if="item.year" class="chip">{{ item.year }}</span>
              <span v-if="item.is_example" class="badge badge-example">Exemple illustratif</span>
            </div>
            <h2 class="mt-6 font-display text-2xl sm:text-3xl">{{ item.title }}</h2>
            <p v-if="item.client" class="mt-2 font-medium text-primary-400">{{ item.client }}</p>
            <p class="mt-4 leading-relaxed text-slate-400">{{ item.summary }}</p>
            <div v-if="item.technologies.length" class="mt-6 flex flex-wrap gap-1.5"><span v-for="tech in item.technologies" :key="tech" class="chip">{{ tech }}</span></div>
            <div class="mt-8"><Link :href="item.url" class="btn btn-primary">Voir le projet <Icon name="arrow-right" size="size-4" /></Link></div>
          </div>

          <div :class="index % 2 ? 'lg:order-1' : ''">
            <template v-if="visuals(item).main">
              <figure class="overflow-hidden rounded-2xl border border-white/10">
                <img :src="visuals(item).main.src" :alt="visuals(item).main.caption || item.title" class="aspect-video w-full object-cover" width="720" height="405" loading="lazy">
                <figcaption v-if="visuals(item).main.caption" class="px-4 py-3 text-sm text-slate-400">{{ visuals(item).main.caption }}</figcaption>
              </figure>
              <div v-if="visuals(item).thumbs.length" class="mt-4 grid grid-cols-2 gap-4">
                <figure v-for="shot in visuals(item).thumbs" :key="shot.id" class="overflow-hidden rounded-xl border border-white/10">
                  <img :src="shot.src" :alt="shot.caption || item.title" class="aspect-video w-full object-cover" width="340" height="190" loading="lazy">
                  <figcaption v-if="shot.caption" class="px-3 py-2 text-xs text-slate-400">{{ shot.caption }}</figcaption>
                </figure>
              </div>
            </template>
            <div v-else class="relative flex aspect-video flex-col items-center justify-center gap-3 overflow-hidden rounded-2xl bg-gradient-to-br from-ink to-primary-700 text-white/50">
              <div class="grid-pattern absolute inset-0" />
              <Icon name="award" size="size-10" />
              <p class="relative text-sm">Images du projet bientôt disponibles</p>
            </div>
          </div>
        </article>

        <div v-if="!cases.length" class="rounded-[var(--radius-card)] border border-dashed border-white/15 p-12 text-center"><p class="font-semibold text-white">Nos premières études de cas seront publiées prochainement.</p><p class="mt-2 text-sm text-slate-500">Nous ne publions un projet qu'avec l'accord explicite du client.</p></div>
      </div>
    </div>
  </section>
  <section v-if="testimonials.length" class="section-sm muted-zone"><div class="container-x"><h2 class="font-display text-2xl">Ils témoignent</h2>
    <div class="mt-8 grid gap-6 md:grid-cols-3"><figure v-for="t in testimonials" :key="t.id" class="card"><blockquote class="text-slate-300">« {{ t.quote }} »</blockquote><figcaption class="mt-4 text-sm font-semibold text-white">{{ t.author_name }}<span class="block font-normal text-slate-500">{{ t.author_role }}<template v-if="t.organization"> · {{ t.organization }}</template></span></figcaption></figure></div>
  </div></section>
  <CtaBand />
</template>
