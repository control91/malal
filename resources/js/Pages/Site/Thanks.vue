<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';

defineProps({ reference: String });
</script>

<template>
  <section class="section"><div class="container-x max-w-2xl text-center">
    <span class="mx-auto grid size-16 place-items-center rounded-full bg-emerald-400/10 text-emerald-300 ring-8 ring-emerald-400/5"><Icon name="circle-check" size="size-8" /></span>
    <h1 class="mt-8 font-display text-4xl">Merci, votre demande est bien reçue.</h1>
    <p class="lead mt-5">Votre référence : <strong class="font-mono text-white">{{ reference }}</strong>. <template v-if="$page.props.site.send_acknowledgement">Un email de confirmation vient de vous être envoyé.</template> Un membre de notre équipe reviendra vers vous rapidement.</p>
    <div class="mt-10 flex flex-col justify-center gap-3 sm:flex-row"><Link href="/" class="btn btn-primary">Retour à l'accueil</Link><Link :href="route('blog.index')" class="btn btn-secondary">Lire nos ressources</Link></div>
  </div></section>
</template>
