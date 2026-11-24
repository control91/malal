<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import SiteHeader from '@/Components/Site/SiteHeader.vue';
import SiteFooter from '@/Components/Site/SiteFooter.vue';

const page = usePage();
const site = computed(() => page.props.site);
const seo = computed(() => page.props.seo ?? {});
const title = computed(() => {
    const t = seo.value.title;
    return !t || t === site.value.company_name ? `${site.value.company_name} — ${site.value.tagline}` : `${t} · ${site.value.company_name}`;
});
const flash = computed(() => page.props.flash ?? {});

const progress = ref(0);
const showTop = ref(false);
const onScroll = () => {
    const max = document.documentElement.scrollHeight - window.innerHeight;
    progress.value = max > 0 ? window.scrollY / max : 0;
    showTop.value = window.scrollY > 400;
};
onMounted(() => { window.addEventListener('scroll', onScroll, { passive: true }); onScroll(); });
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));
const toTop = () => window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
</script>

<template>
  <Head :title="title" />
  <a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-full focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-ink">Aller au contenu</a>

  <div v-if="site.announcement" class="relative z-50 border-b border-white/10 bg-night text-center text-xs font-medium text-slate-200 sm:text-sm">
    <div class="container-x py-2.5">
      <component :is="site.announcement_url ? Link : 'span'" :href="site.announcement_url || undefined" class="inline-flex items-center gap-2 hover:text-white">
        <span class="badge bg-accent/15 text-accent ring-1 ring-accent/30">Nouveau</span>{{ site.announcement }}<Icon v-if="site.announcement_url" name="arrow-right" size="size-4" />
      </component>
    </div>
  </div>

  <SiteHeader />

  <div v-if="flash.success || flash.error" class="container-x mt-4" role="status">
    <div class="alert" :class="flash.error ? 'alert-error' : 'alert-success'">{{ flash.error || flash.success }}</div>
  </div>

  <main id="contenu" tabindex="-1"><slot /></main>

  <SiteFooter />

  <button v-show="showTop" type="button" class="to-top" aria-label="Revenir en haut de la page" @click="toTop">
    <svg class="absolute inset-0 size-full -rotate-90" viewBox="0 0 48 48" aria-hidden="true"><circle cx="24" cy="24" r="22.5" fill="none" stroke="var(--brand-primary)" stroke-width="2" pathLength="100" stroke-dasharray="100" :stroke-dashoffset="100 - progress * 100" /></svg>
    <Icon name="arrow-up" size="size-4 relative" />
  </button>
</template>
