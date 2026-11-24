<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import PageHeader from '@/Components/Site/PageHeader.vue';
import InquiryForm from '@/Components/Site/InquiryForm.vue';

const props = defineProps({ initial: Object, kinds: Array, categories: Array });
const site = computed(() => usePage().props.site);
const fields = [
    { name: 'kind', label: 'Type de demande', type: 'select', required: true, options: props.kinds, wide: true },
    { name: 'first_name', label: 'Prénom', required: true, autocomplete: 'given-name' },
    { name: 'last_name', label: 'Nom', required: true, autocomplete: 'family-name' },
    { name: 'organization', label: 'Organisation', autocomplete: 'organization' },
    { name: 'email', label: 'Email', type: 'email', required: true, autocomplete: 'email' },
    { name: 'phone', label: 'Téléphone', type: 'tel', autocomplete: 'tel' },
    { name: 'service_category_id', label: 'Domaine concerné', type: 'select', options: props.categories, placeholder: '— Choisir un domaine —' },
    { name: 'subject', label: 'Objet', required: true, wide: true },
    { name: 'message', label: 'Message', type: 'textarea', required: true },
];
</script>

<template>
  <PageHeader eyebrow="Contact" title="Parlons de votre *projet*" subtitle="Une question, un besoin Cloud, SaaS ou IA, une idée de partenariat ? Écrivez-nous : un membre de l'équipe vous répondra." />
  <section class="section-sm">
    <div class="container-x grid gap-12 lg:grid-cols-12">
      <div class="lg:col-span-7"><div class="card"><InquiryForm :action="route('contact.store')" :fields="fields" :initial="initial" submit-label="Envoyer le message" /></div></div>
      <aside class="space-y-5 lg:col-span-5">
        <div class="card">
          <h2 class="font-display text-lg">Coordonnées</h2>
          <ul class="mt-5 space-y-4 text-sm">
            <li class="flex gap-3"><span class="icon-tile size-10 rounded-xl"><Icon name="mail" /></span><span><span class="block font-medium text-white">Email</span><a :href="`mailto:${site.email_contact}`" class="text-primary-400">{{ site.email_contact }}</a></span></li>
            <li v-if="site.phone" class="flex gap-3"><span class="icon-tile size-10 rounded-xl"><Icon name="phone" /></span><span><span class="block font-medium text-white">Téléphone</span><a :href="`tel:${site.phone}`">{{ site.phone }}</a></span></li>
            <li v-if="site.whatsapp" class="flex gap-3"><span class="icon-tile size-10 rounded-xl"><Icon name="brand-whatsapp" /></span><span><span class="block font-medium text-white">WhatsApp</span>{{ site.whatsapp }}</span></li>
            <li class="flex gap-3"><span class="icon-tile size-10 rounded-xl"><Icon name="map-pin" /></span><span><span class="block font-medium text-white">Adresse</span><template v-if="site.address">{{ site.address }}, </template>{{ site.city }}, {{ site.country }}</span></li>
            <li v-if="site.opening_hours" class="flex gap-3"><span class="icon-tile size-10 rounded-xl"><Icon name="clock" /></span><span><span class="block font-medium text-white">Horaires</span>{{ site.opening_hours }}</span></li>
          </ul>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
          <Link :href="route('quote')" class="card card-hover flex items-center gap-4 !p-5"><span class="icon-tile"><Icon name="receipt" /></span><span><span class="block font-semibold text-white">Demander un devis</span><span class="text-sm text-slate-500">Pour un projet précis</span></span></Link>
          <Link :href="route('demo')" class="card card-hover flex items-center gap-4 !p-5"><span class="icon-tile"><Icon name="monitor" /></span><span><span class="block font-semibold text-white">Voir une démo</span><span class="text-sm text-slate-500">Découvrir une solution</span></span></Link>
        </div>
        <iframe v-if="site.map_embed_url" :src="site.map_embed_url" title="Carte" class="h-64 w-full rounded-[var(--radius-card)] border border-white/10" loading="lazy" referrerpolicy="no-referrer" />
      </aside>
    </div>
  </section>
</template>
