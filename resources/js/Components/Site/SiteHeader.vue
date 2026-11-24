<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Logo from '@/Components/Ui/Logo.vue';

const page = usePage();
const site = computed(() => page.props.site);
const items = computed(() => page.props.menus?.header?.items ?? []);
const open = ref(false);
const submenu = ref(null);
const mobileSub = ref(null);
const scrolled = ref(false);

const isActive = (url) => {
    if (!url || url.startsWith('http') || url.startsWith('#')) return false;
    const path = page.url.split('?')[0];
    return url === '/' ? path === '/' : path.startsWith(url);
};
const onScroll = () => { scrolled.value = window.scrollY > 8; };
const onKey = (e) => { if (e.key === 'Escape') { open.value = false; submenu.value = null; } };
let off;
onMounted(() => {
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('keydown', onKey);
    off = router.on('navigate', () => { open.value = false; submenu.value = null; });
    onScroll();
});
onBeforeUnmount(() => { window.removeEventListener('scroll', onScroll); window.removeEventListener('keydown', onKey); off?.(); });
watch(open, (value) => { document.documentElement.style.overflow = value ? 'hidden' : ''; });
</script>

<template>
  <header class="sticky top-0 z-40 border-b backdrop-blur-xl transition-colors" :class="scrolled ? 'border-white/10 bg-night/90' : 'border-white/[0.06] bg-night/70'">
    <nav class="container-x flex h-20 items-center justify-between gap-6" aria-label="Navigation principale">
      <Link href="/" class="shrink-0 rounded-lg" :aria-label="`${site.company_name} — accueil`"><Logo dark /></Link>

      <ul class="hidden items-center gap-1 xl:flex">
        <template v-for="(item, index) in items" :key="item.label">
          <li v-if="!item.is_button" class="relative" @mouseenter="item.children.length && (submenu = index)" @mouseleave="submenu = null">
            <template v-if="item.children.length">
              <button type="button" class="nav-link inline-flex items-center gap-1.5" :class="{ 'is-active': isActive(item.url) }" :aria-expanded="submenu === index" aria-haspopup="true" @click="submenu = submenu === index ? null : index">
                {{ item.label }} <Icon name="chevron-down" size="size-3.5" class="transition" :class="{ 'rotate-180': submenu === index }" />
              </button>
              <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="translate-y-2 opacity-0" leave-active-class="transition duration-100" leave-to-class="opacity-0">
                <div v-show="submenu === index" class="absolute left-1/2 top-full w-[36rem] -translate-x-1/2 pt-4">
                  <div class="grid grid-cols-2 gap-1 rounded-xl border border-white/10 bg-night-800/95 p-3 shadow-2xl shadow-black/60 backdrop-blur-xl">
                    <Link v-for="child in item.children" :key="child.label" :href="child.url" class="group flex gap-3.5 rounded-lg p-3 transition hover:bg-white/[0.05]">
                      <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary-400 ring-1 ring-primary/25 transition group-hover:bg-primary group-hover:text-white"><Icon :name="child.icon || 'arrow-up-right'" /></span>
                      <span><span class="block text-[0.95rem] font-medium text-white">{{ child.label }}</span><span v-if="child.description" class="mt-0.5 block text-sm leading-snug text-slate-400">{{ child.description }}</span></span>
                    </Link>
                  </div>
                </div>
              </Transition>
            </template>
            <Link v-else :href="item.url" class="nav-link" :class="{ 'is-active': isActive(item.url) }">{{ item.label }}</Link>
          </li>
        </template>
      </ul>

      <div class="flex items-center gap-3">
        <a v-if="site.phone" :href="`tel:${site.phone}`" class="hidden items-center gap-3 text-sm 2xl:flex">
          <span class="grid size-10 place-items-center rounded-full border border-white/15 text-primary-400"><Icon name="phone" size="size-4" /></span>
          <span><span class="block text-xs text-slate-500">Appelez-nous</span><span class="font-medium text-white">{{ site.phone }}</span></span>
        </a>
        <template v-for="item in items" :key="`btn-${item.label}`">
          <Link v-if="item.is_button" :href="item.url" class="btn btn-primary hidden sm:inline-flex">{{ item.label }} <Icon name="arrow-up-right" size="size-4" /></Link>
        </template>
        <button type="button" class="grid size-11 place-items-center rounded-lg border border-white/15 text-white transition hover:bg-white/10 xl:hidden" aria-label="Ouvrir le menu" :aria-expanded="open" @click="open = true"><Icon name="menu" /></button>
      </div>
    </nav>

    <Teleport to="body">
      <Transition enter-active-class="transition duration-300" enter-from-class="opacity-0" leave-active-class="transition duration-200" leave-to-class="opacity-0">
        <div v-if="open" class="site fixed inset-0 z-50 !bg-transparent xl:hidden" role="dialog" aria-modal="true" aria-label="Menu">
          <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="open = false" />
          <div class="absolute inset-y-0 right-0 flex w-[min(24rem,100%)] flex-col overflow-y-auto border-l border-white/10 bg-night-800 p-6">
            <div class="mb-8 flex items-center justify-between">
              <Logo dark />
              <button type="button" class="grid size-10 place-items-center rounded-lg border border-white/15 text-white" aria-label="Fermer le menu" @click="open = false"><Icon name="x" /></button>
            </div>
            <ul class="space-y-1">
              <li v-for="(item, index) in items" :key="item.label">
                <template v-if="item.children.length">
                  <button type="button" class="flex w-full items-center justify-between rounded-lg px-3 py-3 font-display text-lg text-white hover:bg-white/5" :aria-expanded="mobileSub === index" @click="mobileSub = mobileSub === index ? null : index">
                    {{ item.label }} <Icon name="chevron-down" class="transition" :class="{ 'rotate-180': mobileSub === index }" />
                  </button>
                  <ul v-show="mobileSub === index" class="mb-2 ml-3 space-y-0.5 border-l border-white/10 pl-3">
                    <li v-for="child in item.children" :key="child.label"><Link :href="child.url" class="flex items-center gap-2.5 rounded-lg px-2 py-2 text-slate-300 hover:text-white"><Icon :name="child.icon || 'arrow-up-right'" size="size-4 text-primary-400" />{{ child.label }}</Link></li>
                  </ul>
                </template>
                <Link v-else-if="item.is_button" :href="item.url" class="btn btn-primary btn-lg mt-6 w-full">{{ item.label }}</Link>
                <Link v-else :href="item.url" class="block rounded-lg px-3 py-3 font-display text-lg text-white hover:bg-white/5">{{ item.label }}</Link>
              </li>
            </ul>
            <div class="mt-auto space-y-2 border-t border-white/10 pt-6 text-sm text-slate-400">
              <a v-if="site.phone" :href="`tel:${site.phone}`" class="flex items-center gap-2.5"><Icon name="phone" size="size-4 text-primary-400" />{{ site.phone }}</a>
              <a :href="`mailto:${site.email_contact}`" class="flex items-center gap-2.5"><Icon name="mail" size="size-4 text-primary-400" />{{ site.email_contact }}</a>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </header>
</template>
