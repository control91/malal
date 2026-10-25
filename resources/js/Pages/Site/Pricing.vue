<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import PageHeader from '@/Components/Site/PageHeader.vue';
import PlanCard from '@/Components/Site/PlanCard.vue';
import CtaBand from '@/Components/Site/CtaBand.vue';

defineProps({ tables: Array });
const billing = ref('monthly');
const site = computed(() => usePage().props.site);
</script>

<template>
  <PageHeader eyebrow="Tarifs" title="Des offres *transparentes*" subtitle="Des formules claires pour démarrer, et des devis sur mesure pour les projets spécifiques. Les prix affichés sont indicatifs et confirmés dans votre proposition commerciale." />
  <section class="section-sm">
    <div class="container-x">
      <div v-if="tables.length" class="flex justify-center">
        <div class="inline-flex rounded-lg bg-white/5 p-1 ring-1 ring-white/10" role="radiogroup" aria-label="Période de facturation">
          <button v-for="b in [['monthly', 'Mensuel'], ['yearly', 'Annuel']]" :key="b[0]" type="button" role="radio" :aria-checked="billing === b[0]" class="rounded-md px-5 py-2 text-sm font-semibold transition" :class="billing === b[0] ? 'bg-white text-night' : 'text-slate-400'" @click="billing = b[0]">{{ b[1] }}</button>
        </div>
      </div>
      <div v-for="table in tables" :key="table.name" class="mt-16 first:mt-12">
        <div class="flex flex-col justify-between gap-3 md:flex-row md:items-end" data-reveal>
          <div><h2 class="font-display text-2xl sm:text-3xl">{{ table.name }}</h2><p v-if="table.description" class="mt-2 max-w-2xl text-slate-400">{{ table.description }}</p></div>
          <Link v-if="table.category" :href="table.category.url" class="link-arrow">Découvrir {{ table.category.name }} <Icon name="arrow-right" size="size-4" /></Link>
        </div>
        <div class="mt-8 grid gap-5 md:grid-cols-2" :class="{ 'lg:grid-cols-3': table.plans.length >= 3, 'xl:grid-cols-4': table.plans.length >= 4 }"><PlanCard v-for="plan in table.plans" :key="plan.id" :plan="plan" :billing="billing" /></div>
        <details v-if="table.rows.length && table.plans.length > 1" class="group mt-6 rounded-[var(--radius-card)] border border-white/10 bg-white/[0.02]">
          <summary class="flex cursor-pointer list-none items-center justify-between px-6 py-4 text-sm font-semibold text-white [&::-webkit-details-marker]:hidden">Comparer les offres en détail <Icon name="chevron-down" size="size-4 transition group-open:rotate-180" /></summary>
          <div class="table-wrap border-t border-white/10">
            <table class="table">
              <thead><tr><th scope="col">Caractéristique</th><th v-for="plan in table.plans" :key="plan.id" scope="col" class="text-center">{{ plan.name }}</th></tr></thead>
              <tbody><tr v-for="row in table.rows" :key="row.label"><th scope="row" class="px-4 py-3 text-left font-medium text-slate-300">{{ row.label }}</th>
                <td v-for="(cell, i) in row.cells" :key="i" class="text-center"><template v-if="cell && cell.included"><span v-if="cell.value" class="font-semibold text-white">{{ cell.value }}</span><Icon v-else name="check" class="inline text-emerald-400" label="Inclus" /></template><Icon v-else name="minus" class="inline text-slate-600" label="Non inclus" /></td></tr></tbody>
            </table>
          </div>
        </details>
        <div v-if="table.options.length" class="mt-6 rounded-[var(--radius-card)] border border-white/10 bg-white/[0.02] p-6">
          <h3 class="text-sm font-semibold text-white">Options</h3>
          <ul class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3"><li v-for="o in table.options" :key="o.name" class="flex items-start justify-between gap-4 rounded-xl bg-white/[0.03] p-4 text-sm ring-1 ring-white/10"><span><span class="block font-medium text-white">{{ o.name }}</span><span v-if="o.description" class="text-slate-500">{{ o.description }}</span></span><span class="shrink-0 font-semibold text-white">{{ o.price_label || 'Sur devis' }}</span></li></ul>
        </div>
      </div>
      <div v-if="!tables.length" class="mx-auto max-w-xl rounded-[var(--radius-card)] border border-dashed border-white/15 p-12 text-center">
        <h2 class="font-display text-2xl">Tarifs sur devis</h2><p class="mt-3 text-slate-400">Nos grilles tarifaires publiques arrivent prochainement. En attendant, nous établissons une proposition personnalisée.</p>
        <Link :href="route('quote')" class="btn btn-primary mt-6">Demander un devis</Link>
      </div>
      <p v-if="site.hosting_disclosure && tables.length" class="mx-auto mt-14 flex max-w-3xl items-start gap-2 text-sm text-slate-500"><Icon name="info" size="size-4 mt-0.5 shrink-0" />{{ site.hosting_disclosure }}</p>
    </div>
  </section>
  <CtaBand title="Besoin d'une offre sur mesure ?" text="Volumes importants, contraintes réglementaires, intégration spécifique : nous construisons une proposition adaptée." />
</template>
