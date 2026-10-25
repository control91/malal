<script setup>
import { reactive, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
import PageHeader from '@/Components/Site/PageHeader.vue';
import ProductCard from '@/Components/Site/ProductCard.vue';
import CtaBand from '@/Components/Site/CtaBand.vue';

const props = defineProps({ products: Object, filters: Object, categories: Array, statuses: Array });
const form = reactive({ ...props.filters });
let timer;
watch(form, () => {
    clearTimeout(timer);
    timer = setTimeout(() => router.get(route('solutions.index'), Object.fromEntries(Object.entries(form).filter(([, v]) => v)), { preserveState: true, preserveScroll: true, replace: true, only: ['products', 'filters'] }), 300);
});
</script>

<template>
  <PageHeader eyebrow="Catalogue" title="Nos *solutions*" subtitle="Logiciels SaaS, services Cloud et solutions d'IA conçus pour les organisations africaines. Chaque fiche indique clairement sa disponibilité." />
  <section class="section-sm">
    <div class="container-x">
      <form class="flex flex-col gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-3 md:flex-row md:items-center" role="search" @submit.prevent>
        <label class="relative flex-1"><span class="sr-only">Rechercher</span>
          <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-slate-500"><Icon name="search" size="size-4" /></span>
          <input v-model="form.q" type="search" placeholder="Rechercher une solution, une fonctionnalité…" class="field-input !pl-10">
        </label>
        <label class="md:w-56"><span class="sr-only">Domaine</span><select v-model="form.domaine" class="field-select"><option value="">Tous les domaines</option><option v-for="c in categories" :key="c.slug" :value="c.slug">{{ c.name }}</option></select></label>
        <label class="md:w-48"><span class="sr-only">Disponibilité</span><select v-model="form.statut" class="field-select"><option value="">Toute disponibilité</option><option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option></select></label>
      </form>
      <div class="mt-10" aria-live="polite">
        <p class="mb-6 text-sm text-slate-500">{{ products.total }} solution{{ products.total > 1 ? 's' : '' }}</p>
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
          <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
          <div v-if="!products.data.length" class="col-span-full rounded-[var(--radius-card)] border border-dashed border-white/15 p-12 text-center">
            <p class="font-semibold text-white">Aucune solution ne correspond à votre recherche.</p>
            <Link :href="route('solutions.index')" class="link-arrow mt-3">Réinitialiser les filtres</Link>
          </div>
        </div>
        <Pagination :meta="products" />
      </div>
    </div>
  </section>
  <CtaBand title="Vous ne trouvez pas la solution idéale ?" text="Nous concevons aussi des logiciels sur mesure, adaptés à vos processus." />
</template>
