<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Logo from '@/Components/Ui/Logo.vue';
import NewsletterForm from './NewsletterForm.vue';

const page = usePage();
const site = computed(() => page.props.site);
const menus = computed(() => page.props.menus ?? {});
const columns = computed(() => ['footer_solutions', 'footer_company', 'footer_resources'].map((key) => menus.value[key]).filter((m) => m?.items?.length));
</script>

<template>
  <footer class="relative isolate overflow-hidden border-t border-white/10 bg-night" aria-labelledby="footer-title">
    <h2 id="footer-title" class="sr-only">Pied de page</h2>
    <div class="glow -left-40 bottom-0 size-[30rem] bg-primary/15" />
    <div class="container-x py-16 sm:py-20">
      <div class="grid gap-12 lg:grid-cols-12">
        <div class="lg:col-span-4">
          <h3 class="font-display text-xl">Où nous trouver</h3>
          <ul class="mt-7 space-y-6 text-[0.95rem]">
            <li class="flex gap-4"><span class="grid size-11 shrink-0 place-items-center rounded-full border border-white/15 text-primary-400"><Icon name="map-pin" /></span>
              <span><span class="block font-medium text-white">{{ site.address || site.city }}</span><span class="text-slate-400"><template v-if="site.address">{{ site.city }}, </template>{{ site.country }}</span></span></li>
            <li v-if="site.phone" class="flex gap-4"><span class="grid size-11 shrink-0 place-items-center rounded-full border border-white/15 text-primary-400"><Icon name="phone" /></span>
              <span><a :href="`tel:${site.phone}`" class="block font-medium text-white hover:text-primary-400">{{ site.phone }}</a><span class="text-slate-400">{{ site.opening_hours || 'Du lundi au vendredi' }}</span></span></li>
            <li class="flex gap-4"><span class="grid size-11 shrink-0 place-items-center rounded-full border border-white/15 text-primary-400"><Icon name="mail" /></span>
              <span><a :href="`mailto:${site.email_contact}`" class="block font-medium text-white hover:text-primary-400">{{ site.email_contact }}</a><span class="text-slate-400">Réponse par un membre de l'équipe</span></span></li>
          </ul>
        </div>
        <div class="lg:col-span-5">
          <h3 class="font-display text-xl">Newsletter</h3>
          <p class="mt-3 text-[0.95rem] text-slate-400">Une sélection mensuelle de ressources sur le Cloud, le SaaS et l'IA. Pas de spam.</p>
          <NewsletterForm />
          <div class="mt-10 grid gap-8 sm:grid-cols-3">
            <div v-for="menu in columns" :key="menu.title">
              <h4 class="font-display text-base">{{ menu.title }}</h4>
              <ul class="mt-4 space-y-2.5 text-[0.95rem]">
                <li v-for="item in menu.items" :key="item.label"><Link :href="item.url" class="text-slate-400 transition hover:text-white">{{ item.label }}</Link></li>
              </ul>
            </div>
          </div>
        </div>
        <div class="lg:col-span-3 lg:text-right">
          <h3 class="font-display text-xl">Parlons de votre projet</h3>
          <a v-if="site.phone" :href="`tel:${site.phone}`" class="mt-6 block font-display text-3xl text-white transition hover:text-primary-400">{{ site.phone }}</a>
          <Link :href="route('quote')" class="mt-3 inline-flex items-center gap-2 text-slate-300 hover:text-white">et obtenez un devis personnalisé <Icon name="arrow-up-right" size="size-4" /></Link>
          <ul v-if="site.social.length" class="mt-8 flex gap-2 lg:justify-end" aria-label="Réseaux sociaux">
            <li v-for="link in site.social" :key="link.label"><a :href="link.url" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-full border border-white/15 text-slate-300 transition hover:border-primary hover:bg-primary hover:text-white"><Icon :name="link.icon" size="size-4" :label="link.label" /></a></li>
          </ul>
        </div>
      </div>
      <div class="mt-16 flex flex-col gap-6 border-t border-white/10 pt-8 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
        <Link href="/" class="shrink-0"><Logo dark /></Link>
        <ul class="flex flex-wrap gap-x-6 gap-y-2"><li v-for="item in menus.footer_legal?.items ?? []" :key="item.label"><Link :href="item.url" class="hover:text-white">{{ item.label }}</Link></li></ul>
        <p>© {{ site.year }} {{ site.legal_name || site.company_name }}<template v-if="site.registration_number"> · RCCM {{ site.registration_number }}</template></p>
      </div>
    </div>
  </footer>
</template>
