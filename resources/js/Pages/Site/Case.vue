<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import RichText from '@/Components/Ui/RichText.vue';
import PageHeader from '@/Components/Site/PageHeader.vue';
import CtaBand from '@/Components/Site/CtaBand.vue';

defineProps({ item: Object });
</script>

<template>
  <PageHeader :eyebrow="item.sector" :title="item.title" :subtitle="item.summary" :parent="{ label: 'Réalisations', url: route('cases.index') }" />
  <section class="section-sm">
    <div class="container-x grid gap-12 lg:grid-cols-12">
      <aside class="lg:order-2 lg:col-span-4">
        <div class="card sticky top-28 space-y-5 text-sm">
          <div v-if="item.is_example" class="alert alert-warning !p-3 text-xs"><Icon name="info" size="size-4 shrink-0" /> Exemple illustratif : ce projet sert à présenter notre démarche et ne correspond pas à un client réel.</div>
          <div v-if="item.client"><p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Client</p><p class="mt-1 font-medium text-white">{{ item.client }}</p></div>
          <div><p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Secteur</p><p class="mt-1 font-medium text-white">{{ item.sector }}</p></div>
          <div v-if="item.year"><p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Année</p><p class="mt-1 font-medium text-white">{{ item.year }}</p></div>
          <div v-if="item.services.length"><p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Expertises</p><div class="mt-2 flex flex-wrap gap-1.5"><Link v-for="s in item.services" :key="s.name" :href="s.url" class="chip hover:text-primary-400">{{ s.name }}</Link></div></div>
          <div v-if="item.technologies.length"><p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Technologies</p><div class="mt-2 flex flex-wrap gap-1.5"><span v-for="t in item.technologies" :key="t" class="chip">{{ t }}</span></div></div>
        </div>
      </aside>
      <div class="space-y-12 lg:col-span-8">
        <img v-if="item.cover" :src="item.cover" alt="" class="w-full rounded-3xl object-cover" width="960" height="540">
        <RichText v-if="item.body" :html="item.body" />
        <div v-if="item.images.length" class="grid gap-4 sm:grid-cols-2"><figure v-for="img in item.images" :key="img.id"><img :src="img.image" :alt="img.caption" class="aspect-video w-full rounded-2xl object-cover" loading="lazy" width="640" height="360"><figcaption v-if="img.caption" class="mt-2 text-xs text-slate-500">{{ img.caption }}</figcaption></figure></div>
        <figure v-for="t in item.testimonials" :key="t.id" class="rounded-3xl border border-white/10 bg-white/[0.03] p-8"><blockquote class="font-display text-xl leading-relaxed text-white">« {{ t.quote }} »</blockquote><figcaption class="mt-5 text-sm text-slate-400"><strong class="text-white">{{ t.author_name }}</strong><template v-if="t.author_role">, {{ t.author_role }}</template><template v-if="t.organization"> — {{ t.organization }}</template></figcaption></figure>
      </div>
    </div>
  </section>
  <CtaBand title="Un projet similaire ?" />
</template>
