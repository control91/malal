<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Logo from '@/Components/Ui/Logo.vue';
import { closeConfirm, confirmState } from '@/confirm';
import { initials, timeAgo } from '@/format';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const nav = computed(() => page.props.adminNav ?? []);
const sidebar = ref(false);
const userMenu = ref(false);
const notifOpen = ref(false);
const notifications = ref(null);
const query = ref('');
const results = ref([]);
const toasts = ref([]);

const activeUrl = computed(() => {
    const path = page.url.split('?')[0];
    const urls = nav.value.flatMap((g) => g.items.map((i) => new URL(i.url, 'http://x').pathname)).filter((u) => path.startsWith(u));
    return urls.sort((a, b) => b.length - a.length)[0];
});
const isActive = (url) => new URL(url, 'http://x').pathname === activeUrl.value;

let searchTimer;
watch(query, (q) => {
    clearTimeout(searchTimer);
    if (q.trim().length < 2) { results.value = []; return; }
    searchTimer = setTimeout(async () => {
        const response = await fetch(`${route('admin.search')}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
        results.value = response.ok ? await response.json() : [];
    }, 250);
});
const openNotifications = async () => {
    notifOpen.value = !notifOpen.value;
    if (notifOpen.value) {
        const response = await fetch(route('admin.notifications.menu'), { headers: { Accept: 'application/json' } });
        notifications.value = response.ok ? await response.json() : [];
    }
};
const readNotification = (n) => router.post(route('admin.notifications.read', n.id));

const pushToast = (type, text) => {
    if (!text) return;
    const id = Date.now() + Math.random();
    toasts.value.push({ id, type, text });
    setTimeout(() => { toasts.value = toasts.value.filter((t) => t.id !== id); }, 6000);
};
watch(() => page.props.flash, (flash) => { pushToast('success', flash?.success); pushToast('error', flash?.error); }, { immediate: true, deep: true });

const closeMenus = (e) => { if (!e.target.closest('[data-menu]')) { userMenu.value = false; notifOpen.value = false; results.value = query.value ? results.value : []; } };
let off;
onMounted(() => { document.addEventListener('click', closeMenus); off = router.on('navigate', () => { sidebar.value = false; userMenu.value = false; notifOpen.value = false; query.value = ''; }); });
onBeforeUnmount(() => { document.removeEventListener('click', closeMenus); off?.(); });
</script>

<template>
  <Head title="Tableau de bord" />
  <div class="min-h-screen">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-full focus:bg-white focus:px-4 focus:py-2">Aller au contenu</a>
    <div v-if="sidebar" class="fixed inset-0 z-40 bg-ink/60 backdrop-blur-sm lg:hidden" @click="sidebar = false" />
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-ink transition-transform duration-300 lg:translate-x-0" aria-label="Navigation du back-office">
      <div class="flex h-16 shrink-0 items-center justify-between px-5">
        <Link :href="route('admin.dashboard')"><Logo dark /></Link>
        <button class="text-slate-400 lg:hidden" aria-label="Fermer" @click="sidebar = false"><Icon name="x" /></button>
      </div>
      <nav class="flex-1 space-y-6 overflow-y-auto px-3 pb-6 pt-2">
        <Link :href="route('admin.dashboard')" class="dash-link" :class="{ 'is-active': page.url === '/admin' }"><Icon name="layout-dashboard" size="size-[18px]" />Tableau de bord</Link>
        <div v-for="group in nav" :key="group.title">
          <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ group.title }}</p>
          <ul class="space-y-0.5">
            <li v-for="item in group.items" :key="item.url">
              <Link :href="item.url" class="dash-link" :class="{ 'is-active': isActive(item.url) }" :aria-current="isActive(item.url) ? 'page' : undefined">
                <Icon :name="item.icon" size="size-[18px]" /><span class="flex-1">{{ item.label }}</span>
                <span v-if="item.label === 'Demandes' && page.props.newInquiries" class="rounded-full bg-accent px-1.5 text-[11px] font-bold text-ink">{{ page.props.newInquiries }}</span>
              </Link>
            </li>
          </ul>
        </div>
      </nav>
      <div class="border-t border-white/10 p-3"><a href="/" target="_blank" class="dash-link"><Icon name="external-link" size="size-[18px]" />Voir le site</a></div>
    </aside>

    <div class="lg:pl-72">
      <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-line bg-white/85 px-4 backdrop-blur-xl sm:px-6">
        <button class="btn btn-ghost size-10 rounded-full p-0 lg:hidden" aria-label="Ouvrir la navigation" @click="sidebar = true"><Icon name="menu" /></button>
        <div class="relative max-w-md flex-1" data-menu>
          <label for="global-search" class="sr-only">Recherche globale</label>
          <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"><Icon name="search" size="size-4" /></span>
          <input id="global-search" v-model="query" type="search" autocomplete="off" placeholder="Rechercher une demande, un prospect, un article…" class="field-input !rounded-full !bg-surface !pl-9 !shadow-none">
          <div v-if="query.trim().length > 1" class="absolute left-0 right-0 top-12 z-40 overflow-hidden rounded-2xl border border-line bg-white shadow-[var(--shadow-lift)]">
            <Link v-for="r in results" :key="r.url" :href="r.url" class="flex items-center gap-3 px-4 py-2.5 hover:bg-surface"><span class="grid size-8 place-items-center rounded-lg bg-primary-50 text-primary"><Icon :name="r.icon" size="size-4" /></span><span class="min-w-0 flex-1"><span class="block truncate text-sm font-medium text-ink">{{ r.title }}</span><span class="block truncate text-xs text-slate-500">{{ r.kind }} · {{ r.subtitle }}</span></span></Link>
            <p v-if="!results.length" class="px-4 py-4 text-sm text-slate-400">Aucun résultat pour « {{ query }} ».</p>
          </div>
        </div>
        <div class="ml-auto flex items-center gap-1.5">
          <div class="relative" data-menu>
            <button class="btn btn-ghost relative size-10 rounded-full p-0" aria-label="Notifications" @click="openNotifications"><Icon name="bell" /><span v-if="page.props.auth?.unread" class="absolute right-2 top-2 size-2 rounded-full bg-rose-500 ring-2 ring-white" /></button>
            <div v-if="notifOpen" class="absolute right-0 mt-2 w-80 overflow-hidden rounded-2xl border border-line bg-white shadow-[var(--shadow-lift)]">
              <div class="flex items-center justify-between border-b border-line px-4 py-3"><p class="text-sm font-semibold text-ink">Notifications</p><button class="text-xs font-medium text-primary" @click="router.post(route('admin.notifications.read-all'), {}, { preserveScroll: true })">Tout marquer lu</button></div>
              <div class="max-h-96 overflow-y-auto">
                <p v-if="notifications === null" class="p-4 text-sm text-slate-400">Chargement…</p>
                <button v-for="n in notifications ?? []" :key="n.id" type="button" class="flex w-full gap-3 px-4 py-3 text-left hover:bg-surface" :class="{ 'bg-primary-50/40': !n.read_at }" @click="readNotification(n)">
                  <span class="grid size-8 shrink-0 place-items-center rounded-full bg-white text-primary ring-1 ring-line"><Icon :name="n.icon" size="size-4" /></span>
                  <span class="min-w-0 flex-1"><span class="block truncate text-sm font-medium text-ink">{{ n.title }}</span><span v-if="n.body" class="block truncate text-xs text-slate-500">{{ n.body }}</span><span class="text-[11px] text-slate-400">{{ timeAgo(n.created_at) }}</span></span>
                </button>
                <p v-if="notifications && !notifications.length" class="px-4 py-8 text-center text-sm text-slate-400">Aucune notification.</p>
              </div>
              <Link :href="route('admin.notifications.index')" class="block border-t border-line px-4 py-2.5 text-center text-xs font-semibold text-primary hover:bg-surface">Toutes les notifications</Link>
            </div>
          </div>
          <div class="relative" data-menu>
            <button class="flex items-center gap-2.5 rounded-full p-1 pr-2 hover:bg-surface" :aria-expanded="userMenu" @click="userMenu = !userMenu">
              <img v-if="user.avatar" :src="user.avatar" alt="" class="size-8 rounded-full object-cover" width="32" height="32"><span v-else class="grid size-8 place-items-center rounded-full bg-gradient-to-br from-primary to-accent text-xs font-bold text-white">{{ initials(user.name) }}</span>
              <span class="hidden text-left sm:block"><span class="block text-sm font-semibold leading-tight text-ink">{{ user.name }}</span><span class="block text-xs text-slate-500">{{ user.role }}</span></span>
              <Icon name="chevron-down" size="size-4 text-slate-400" />
            </button>
            <div v-if="userMenu" class="absolute right-0 mt-2 w-56 rounded-2xl border border-line bg-white p-1.5 shadow-[var(--shadow-lift)]">
              <Link :href="route('admin.profile.edit')" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm hover:bg-surface"><Icon name="user" size="size-4" />Mon profil</Link>
              <Link :href="route('logout')" method="post" as="button" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-rose-600 hover:bg-rose-50"><Icon name="log-out" size="size-4" />Déconnexion</Link>
            </div>
          </div>
        </div>
      </header>

      <div class="pointer-events-none fixed right-4 top-20 z-[60] flex w-full max-w-sm flex-col gap-2" aria-live="polite">
        <TransitionGroup enter-active-class="toast" leave-active-class="transition duration-300" leave-to-class="opacity-0">
          <div v-for="t in toasts" :key="t.id" class="pointer-events-auto flex items-start gap-3 rounded-2xl border bg-white p-4 text-sm shadow-[var(--shadow-lift)]" :class="t.type === 'error' ? 'border-rose-200' : 'border-emerald-200'" role="status">
            <Icon :name="t.type === 'error' ? 'circle-alert' : 'circle-check'" :class="t.type === 'error' ? 'shrink-0 text-rose-500' : 'shrink-0 text-emerald-500'" />
            <p class="flex-1 text-ink">{{ t.text }}</p>
            <button class="text-slate-400 hover:text-ink" aria-label="Fermer" @click="toasts = toasts.filter((x) => x.id !== t.id)"><Icon name="x" size="size-4" /></button>
          </div>
        </TransitionGroup>
      </div>

      <main id="contenu" class="px-4 py-8 sm:px-6 lg:px-10"><slot /></main>
    </div>

    <div v-if="confirmState.open" class="fixed inset-0 z-[70] grid place-items-center p-4" role="dialog" aria-modal="true" @keydown.esc="closeConfirm(false)">
      <div class="absolute inset-0 bg-ink/50 backdrop-blur-sm" @click="closeConfirm(false)" />
      <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
        <span class="grid size-12 place-items-center rounded-full" :class="confirmState.danger ? 'bg-rose-50 text-rose-600' : 'bg-primary-50 text-primary'"><Icon name="triangle-alert" size="size-6" /></span>
        <h2 class="mt-5 text-lg font-semibold text-ink">{{ confirmState.title }}</h2>
        <p class="mt-2 text-sm text-slate-600">{{ confirmState.text }}</p>
        <div class="mt-6 flex justify-end gap-2">
          <button type="button" class="btn btn-secondary" @click="closeConfirm(false)">Annuler</button>
          <button type="button" class="btn" :class="confirmState.danger ? 'btn-danger' : 'btn-primary'" autofocus @click="closeConfirm(true)">{{ confirmState.label }}</button>
        </div>
      </div>
    </div>
  </div>
</template>
