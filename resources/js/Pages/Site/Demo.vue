<script setup>
import PageHeader from '@/Components/Site/PageHeader.vue';
import InquiryForm from '@/Components/Site/InquiryForm.vue';

const props = defineProps({ initial: Object, products: Array });
const fields = [
    { name: 'product_id', label: 'Solution', type: 'select', required: true, options: props.products, placeholder: '— Choisir une solution —', wide: true },
    { name: 'first_name', label: 'Prénom', required: true, autocomplete: 'given-name' },
    { name: 'last_name', label: 'Nom', required: true, autocomplete: 'family-name' },
    { name: 'organization', label: 'Organisation', required: true, autocomplete: 'organization' },
    { name: 'job_title', label: 'Fonction' },
    { name: 'email', label: 'Email', type: 'email', required: true, autocomplete: 'email' },
    { name: 'phone', label: 'Téléphone', type: 'tel', autocomplete: 'tel' },
    { name: 'country', label: 'Pays', autocomplete: 'country-name' },
    { name: 'message', label: 'Vos besoins', type: 'textarea' },
];
</script>

<template>
  <PageHeader eyebrow="Démonstration" title="Voir une solution *en action*" subtitle="Choisissez la solution qui vous intéresse : nous organisons une démonstration adaptée à votre activité." />
  <section class="section-sm"><div class="container-x max-w-4xl"><div class="card"><InquiryForm :action="route('demo.store')" :fields="fields" :initial="initial" submit-label="Demander ma démonstration" /></div></div></section>
</template>
