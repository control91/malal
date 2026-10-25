<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import RichText from '@/Components/Ui/RichText.vue';
import ArticleCard from '@/Components/Site/ArticleCard.vue';
import CtaBand from '@/Components/Site/CtaBand.vue';
import { formatLongDate, initials } from '@/format';

const props = defineProps({ article: Object, related: Array });
const canonical = computed(() => usePage().props.seo?.canonical ?? '');
const enc = (v) => encodeURIComponent(v);
const copied = ref(false);
const copy = async () => { await navigator.clipboard?.writeText(canonical.value); copied.value = true; setTimeout(() => (copied.value = false), 1800); };
</script>

<template>
  <article>
    <header class="relative isolate overflow-hidden border-b border-white/10">
      <div class="grid-pattern absolute inset-0 -z-10" /><div class="glow -top-32 left-1/3 size-[30rem] bg-primary/25" />
      <div class="container-x max-w-4xl pb-14 pt-12 sm:pt-16">
        <nav aria-label="Fil d'Ariane" class="text-xs text-slate-400"><Link href="/" class="hover:text-white">Accueil</Link> / <Link :href="route('blog.index')" class="hover:text-white">Blog</Link><template v-if="article.category"> / <Link :href="article.category.url" class="hover:text-white">{{ article.category.name }}</Link></template></nav>
        <p v-if="article.preview" class="mt-6 inline-flex items-center gap-2 rounded-full bg-amber-400/15 px-3 py-1 text-xs font-semibold text-amber-300"><Icon name="eye" size="size-3.5" /> Aperçu — {{ article.status }}</p>
        <h1 class="mt-6 font-display text-4xl leading-tight sm:text-5xl">{{ article.title }}</h1>
        <p v-if="article.excerpt" class="lead mt-5">{{ article.excerpt }}</p>
        <div class="mt-8 flex flex-wrap items-center gap-4 text-sm text-slate-400">
          <span v-if="article.author" class="flex items-center gap-2.5"><img v-if="article.author.avatar" :src="article.author.avatar" alt="" class="size-9 rounded-full object-cover" width="36" height="36"><span v-else class="grid size-9 place-items-center rounded-full bg-white/10 text-xs font-bold text-white">{{ initials(article.author.name) }}</span><span class="font-medium text-slate-200">{{ article.author.name }}</span></span>
          <time v-if="article.published_at" :datetime="article.published_at">{{ formatLongDate(article.published_at) }}</time><span>{{ article.reading_time }} min de lecture</span>
        </div>
      </div>
    </header>
    <div v-if="article.cover" class="container-x max-w-5xl pt-10"><img :src="article.cover" :alt="article.cover_alt" class="aspect-[2/1] w-full rounded-3xl object-cover" width="1200" height="600"></div>
    <div class="container-x grid max-w-5xl gap-12 py-14 lg:grid-cols-[1fr_12rem]">
      <RichText :html="article.body" class="prose-lg min-w-0" />
      <aside class="space-y-8 lg:sticky lg:top-28 lg:self-start">
        <div><p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Partager</p>
          <div class="mt-3 flex flex-wrap gap-2">
            <a class="grid size-10 place-items-center rounded-full bg-white/5 text-slate-300 hover:text-primary-400" target="_blank" rel="noopener" :href="`https://www.linkedin.com/sharing/share-offsite/?url=${enc(canonical)}`"><Icon name="brand-linkedin" size="size-4" label="LinkedIn" /></a>
            <a class="grid size-10 place-items-center rounded-full bg-white/5 text-slate-300 hover:text-primary-400" target="_blank" rel="noopener" :href="`https://x.com/intent/post?url=${enc(canonical)}&text=${enc(article.title)}`"><Icon name="brand-x" size="size-4" label="X" /></a>
            <a class="grid size-10 place-items-center rounded-full bg-white/5 text-slate-300 hover:text-primary-400" target="_blank" rel="noopener" :href="`https://wa.me/?text=${enc(article.title + ' ' + canonical)}`"><Icon name="brand-whatsapp" size="size-4" label="WhatsApp" /></a>
            <button type="button" class="inline-flex h-10 items-center gap-2 rounded-full bg-white/5 px-3 text-xs font-medium text-slate-300 hover:text-primary-400" @click="copy"><Icon name="link" size="size-4" />{{ copied ? 'Lien copié' : 'Copier' }}</button>
          </div>
        </div>
        <div v-if="article.tags.length"><p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Étiquettes</p><div class="mt-3 flex flex-wrap gap-1.5"><Link v-for="t in article.tags" :key="t.name" :href="t.url" class="chip hover:text-primary-400">#{{ t.name }}</Link></div></div>
      </aside>
    </div>
  </article>
  <section v-if="related.length" class="section-sm muted-zone"><div class="container-x"><h2 class="font-display text-2xl">À lire aussi</h2><div class="mt-8 grid gap-6 md:grid-cols-3"><ArticleCard v-for="a in related" :key="a.id" :article="a" /></div></div></section>
  <CtaBand />
</template>
