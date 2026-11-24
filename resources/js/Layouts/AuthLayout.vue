<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Logo from '@/Components/Ui/Logo.vue';

const site = computed(() => usePage().props.site);
</script>

<template>
  <Head title="Connexion" />
  <div class="grid min-h-screen lg:grid-cols-2">
    <div class="dark-zone hidden flex-col justify-between p-12 lg:flex">
      <div class="grid-pattern absolute inset-0 -z-10" /><div class="glow -left-20 bottom-0 size-[30rem] bg-primary/30" />
      <Link href="/"><Logo dark /></Link>
      <div>
        <p class="eyebrow">Espace d'administration</p>
        <h1 class="mt-4 max-w-md font-display text-4xl font-bold leading-tight">Pilotez votre site, vos contenus et vos prospects.</h1>
        <ul class="mt-8 space-y-3 text-sm text-slate-300">
          <li class="flex items-center gap-3"><Icon name="shield-check" class="text-accent" />Accès protégé, tentatives de connexion limitées</li>
          <li class="flex items-center gap-3"><Icon name="history" class="text-accent" />Opérations sensibles journalisées</li>
          <li class="flex items-center gap-3"><Icon name="users" class="text-accent" />Permissions par rôle</li>
        </ul>
      </div>
      <p class="text-xs text-slate-500">© {{ site.year }} {{ site.company_name }}</p>
    </div>
    <main class="flex items-center justify-center bg-surface px-5 py-12">
      <div class="w-full max-w-sm"><Link href="/" class="mb-10 inline-block lg:hidden"><Logo /></Link><slot /></div>
    </main>
  </div>
</template>
