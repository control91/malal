<script setup>
import PageHeader from '@/Components/Site/PageHeader.vue';
import FaqList from '@/Components/Site/FaqList.vue';
import CtaBand from '@/Components/Site/CtaBand.vue';

defineProps({ groups: Array });
</script>

<template>
  <PageHeader eyebrow="FAQ" title="Questions *fréquentes*" subtitle="Les réponses aux questions que l'on nous pose le plus souvent. Vous ne trouvez pas la vôtre ? Contactez-nous." />
  <section class="section-sm">
    <div class="container-x space-y-14">
      <div v-for="group in groups" :key="group.title"><h2 class="font-display text-2xl">{{ group.title }}</h2><div class="mt-6"><FaqList :faqs="group.faqs" /></div></div>
      <p v-if="!groups.length" class="text-center text-slate-500">Aucune question publiée pour le moment.</p>
    </div>
  </section>
  <CtaBand title="Une autre question ?" text="Notre équipe vous répond volontiers." />
</template>
