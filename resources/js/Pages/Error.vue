<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({ status: Number });
const texts = {
    403: ['Accès refusé', "Vous n'avez pas les droits nécessaires pour accéder à cette page."],
    404: ['Cette page est introuvable', "Elle a peut-être été déplacée ou n'existe plus. Utilisez le menu ou revenez à l'accueil."],
    419: ['Votre session a expiré', 'Pour votre sécurité, le formulaire n\'a pas été envoyé. Rechargez la page et réessayez.'],
    429: ['Trop de requêtes', 'Merci de patienter quelques instants avant de réessayer.'],
    500: ['Un incident technique est survenu', 'Notre équipe a été informée. Merci de réessayer dans quelques instants.'],
    503: ['Maintenance en cours', 'Le site revient très vite.'],
};
const text = computed(() => texts[props.status] ?? texts[500]);
</script>

<template>
  <section class="relative isolate min-h-[70vh] overflow-hidden">
    <div class="grid-pattern absolute inset-0 -z-10" /><div class="glow left-1/2 top-1/4 size-[30rem] -translate-x-1/2 bg-primary/30" />
    <div class="container-x flex min-h-[70vh] flex-col items-center justify-center py-24 text-center">
      <p class="font-display text-7xl text-gradient sm:text-8xl">{{ status }}</p>
      <h1 class="mt-6 font-display text-3xl sm:text-4xl">{{ text[0] }}</h1>
      <p class="lead mx-auto mt-4 max-w-xl">{{ text[1] }}</p>
      <div class="mt-10 flex flex-col gap-3 sm:flex-row"><Link href="/" class="btn btn-primary">Retour à l'accueil</Link><Link href="/contact" class="btn btn-secondary">Nous contacter</Link></div>
    </div>
  </section>
</template>
