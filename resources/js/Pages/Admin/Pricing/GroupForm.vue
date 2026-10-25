<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import ResourceForm from '@/Components/Admin/ResourceForm.vue';
import { money } from '@/format';

defineProps({ resource: Object, record: Object, fieldsets: Array, repeaters: Array, values: Object, canEdit: Boolean, extra: Object });
</script>

<template>
  <ResourceForm v-bind="{ resource, record, fieldsets, repeaters, values, canEdit }">
    <template #after>
      <section v-if="record" class="dash-card">
        <div class="dash-card-header"><div><h2 class="dash-card-title">Offres du groupe</h2><p class="mt-0.5 text-xs text-slate-500">Prix mensuels/annuels, caractéristiques comparées et statut.</p></div>
          <Link v-if="canEdit" :href="route('admin.resources.create', { resource: 'offres', pricing_group_id: record.id, currency: 'GNF', status: 'available', cta_label: 'Demander un devis', is_published: 1 })" class="btn btn-primary btn-sm"><Icon name="plus" size="size-4" />Ajouter une offre</Link></div>
        <div class="table-wrap"><table class="table">
          <thead><tr><th>Offre</th><th>Mensuel</th><th>Annuel</th><th>Disponibilité</th><th>Publié</th><th /></tr></thead>
          <tbody>
            <tr v-for="plan in extra.plans" :key="plan.id">
              <td><Link :href="route('admin.resources.edit', ['offres', plan.id])" class="font-semibold text-ink hover:text-primary">{{ plan.name }}</Link> <span v-if="plan.is_highlighted" class="chip">Recommandée</span></td>
              <td>{{ plan.price_on_request ? 'Sur devis' : money(plan.price_monthly, plan.currency) || '—' }}</td>
              <td>{{ plan.price_on_request ? '—' : money(plan.price_yearly, plan.currency) || '—' }}</td>
              <td><span class="status-pill bg-slate-50 text-slate-700 ring-slate-200">{{ plan.status }}</span></td>
              <td><Icon :name="plan.is_published ? 'circle-check' : 'circle'" :class="plan.is_published ? 'text-emerald-500' : 'text-slate-300'" /></td>
              <td class="text-right"><Link :href="route('admin.resources.edit', ['offres', plan.id])" class="btn btn-ghost btn-sm"><Icon name="pencil" size="size-4" label="Modifier" /></Link></td>
            </tr>
            <tr v-if="!extra.plans.length"><td colspan="6" class="py-8 text-center text-slate-400">Aucune offre dans ce groupe.</td></tr>
          </tbody>
        </table></div>
      </section>
    </template>
  </ResourceForm>
</template>
