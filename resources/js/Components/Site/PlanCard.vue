<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import StatusBadge from '@/Components/Ui/StatusBadge.vue';
import { money } from '@/format';

defineProps({ plan: { type: Object, required: true }, billing: { type: String, default: 'monthly' } });
</script>

<template>
  <div class="relative flex flex-col rounded-[var(--radius-card)] border bg-white/[0.03] p-7" :class="plan.is_highlighted ? 'border-primary shadow-[0_30px_60px_-30px_var(--brand-primary)] ring-1 ring-primary' : 'border-white/10'" data-reveal>
    <span v-if="plan.is_highlighted" class="badge absolute -top-3 left-7 bg-primary text-white shadow">Recommandé</span>
    <div class="flex items-start justify-between gap-3"><h3 class="font-display text-lg">{{ plan.name }}</h3><StatusBadge v-if="plan.status !== 'available'" :status="plan.status" /></div>
    <p v-if="plan.description" class="mt-2 text-sm text-slate-400">{{ plan.description }}</p>
    <div class="mt-6 min-h-[5.5rem]">
      <template v-if="plan.price_on_request || (plan.price_monthly === null && plan.price_yearly === null)">
        <p class="font-display text-3xl text-white">Sur devis</p><p class="mt-1 text-sm text-slate-500">Tarif adapté à votre besoin</p>
      </template>
      <template v-else-if="billing === 'yearly' && plan.price_yearly !== null || plan.price_monthly === null">
        <p class="flex items-baseline gap-1.5"><span class="font-display text-4xl text-white">{{ money(plan.yearly_monthly) }}</span><span class="text-sm text-slate-500">{{ plan.currency }} / mois</span></p>
        <p class="mt-1 text-sm text-slate-500">facturé {{ money(plan.price_yearly, plan.currency) }} / an <span v-if="plan.saving" class="badge badge-available ml-1">−{{ plan.saving }} %</span></p>
      </template>
      <template v-else>
        <p class="flex items-baseline gap-1.5"><span class="font-display text-4xl text-white">{{ money(plan.price_monthly) }}</span><span class="text-sm text-slate-500">{{ plan.currency }} / mois</span></p>
        <p v-if="plan.price_yearly" class="mt-1 text-sm text-slate-500">ou {{ money(plan.price_yearly, plan.currency) }} / an</p>
      </template>
      <p v-if="plan.price_note" class="mt-1 text-xs text-slate-500">{{ plan.price_note }}</p>
    </div>
    <ul v-if="plan.features.length" class="mt-6 flex-1 space-y-3 border-t border-white/10 pt-6 text-sm">
      <li v-for="f in plan.features" :key="f.label" class="flex items-start gap-2.5" :class="f.included ? 'text-slate-300' : 'text-slate-500'">
        <Icon :name="f.included ? 'check' : 'minus'" size="size-4 mt-0.5 shrink-0" :class="f.included ? 'text-emerald-400' : ''" />
        <span>{{ f.label }}<template v-if="f.value"> : <strong class="font-medium" :class="f.included ? 'text-white' : ''">{{ f.value }}</strong></template></span>
      </li>
    </ul>
    <div v-else class="flex-1" />
    <Link :href="plan.cta_url" class="btn mt-8 w-full" :class="plan.is_highlighted ? 'btn-primary' : 'btn-secondary'">{{ plan.status === 'coming_soon' ? 'Être informé' : plan.cta_label }}</Link>
  </div>
</template>
