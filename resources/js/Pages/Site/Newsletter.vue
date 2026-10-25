<script setup>
import { Link, router } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';

const props = defineProps({ state: String, email: String, token: String });
const unsubscribe = () => router.post(route('newsletter.unsubscribe', props.token));
</script>

<template>
  <section class="section"><div class="container-x max-w-xl text-center">
    <template v-if="state === 'confirmed'"><span class="mx-auto grid size-16 place-items-center rounded-full bg-emerald-400/10 text-emerald-300"><Icon name="mail-open" size="size-8" /></span><h1 class="mt-6 font-display text-3xl">Inscription confirmée</h1><p class="mt-4 text-slate-400">Merci ! Vous recevrez notre prochaine newsletter à l'adresse {{ email }}.</p></template>
    <template v-else-if="state === 'unsubscribed'"><h1 class="font-display text-3xl">Vous êtes désinscrit(e)</h1><p class="mt-4 text-slate-400">Vous ne recevrez plus nos emails. Vous pouvez vous réinscrire à tout moment.</p></template>
    <template v-else><h1 class="font-display text-3xl">Se désinscrire de la newsletter</h1><p class="mt-4 text-slate-400">Confirmez-vous la désinscription de {{ email }} ?</p><button type="button" class="btn btn-primary mt-6" @click="unsubscribe">Confirmer la désinscription</button></template>
    <Link href="/" class="link-arrow mt-8">Retour à l'accueil <Icon name="arrow-right" size="size-4" /></Link>
  </div></section>
</template>
