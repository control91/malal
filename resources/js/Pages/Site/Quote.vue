<script setup>
import PageHeader from '@/Components/Site/PageHeader.vue';
import InquiryForm from '@/Components/Site/InquiryForm.vue';

const props = defineProps({ initial: Object, categories: Array, products: Array, budgets: Array, timelines: Array });
const fields = [
    { name: 'first_name', label: 'Prénom', required: true, autocomplete: 'given-name' },
    { name: 'last_name', label: 'Nom', required: true, autocomplete: 'family-name' },
    { name: 'organization', label: 'Organisation', required: true, autocomplete: 'organization' },
    { name: 'job_title', label: 'Fonction' },
    { name: 'email', label: 'Email', type: 'email', required: true, autocomplete: 'email' },
    { name: 'phone', label: 'Téléphone', type: 'tel', autocomplete: 'tel' },
    { name: 'country', label: 'Pays', autocomplete: 'country-name' },
    { name: 'service_category_id', label: 'Domaine', type: 'select', options: props.categories, placeholder: '— Choisir un domaine —' },
    { name: 'product_id', label: 'Solution', type: 'select', options: props.products, placeholder: '— Choisir une solution —', wide: true },
    { name: 'budget', label: 'Budget indicatif', type: 'select', options: props.budgets, placeholder: 'Non précisé' },
    { name: 'timeline', label: 'Échéance', type: 'select', options: props.timelines, placeholder: 'Non précisé' },
    { name: 'subject', label: 'Objet', required: true, wide: true },
    { name: 'message', label: 'Décrivez votre projet', type: 'textarea', required: true },
];
const steps = [['Analyse', 'Nous étudions votre demande et revenons vers vous pour préciser le besoin.'], ['Échange', 'Un appel ou une réunion pour cadrer le périmètre, les délais et les contraintes.'], ['Proposition', 'Une offre détaillée et transparente, sans engagement.']];
</script>

<template>
  <PageHeader eyebrow="Devis" title="Demander un *devis*" subtitle="Décrivez votre projet : nous vous proposerons une offre adaptée à vos objectifs et à votre budget." />
  <section class="section-sm">
    <div class="container-x grid gap-12 lg:grid-cols-12">
      <div class="lg:col-span-8"><div class="card"><InquiryForm :action="route('quote.store')" :fields="fields" :initial="{ pricing_plan_id: '', ...initial }" submit-label="Envoyer ma demande de devis" /></div></div>
      <aside class="lg:col-span-4"><div class="card sticky top-28">
        <h2 class="font-display text-lg">Comment ça se passe ?</h2>
        <ol class="mt-5 space-y-5 text-sm"><li v-for="(step, i) in steps" :key="step[0]" class="flex gap-3"><span class="grid size-7 shrink-0 place-items-center rounded-full bg-primary text-xs font-bold text-white">{{ i + 1 }}</span><span><strong class="text-white">{{ step[0] }}</strong><br>{{ step[1] }}</span></li></ol>
      </div></aside>
    </div>
  </section>
</template>
