<script setup>
import Icon from '@/Components/Ui/Icon.vue';
import ResourceForm from '@/Components/Admin/ResourceForm.vue';
import { formatDateTime } from '@/format';

defineProps({ resource: Object, record: Object, fieldsets: Array, repeaters: Array, values: Object, canEdit: Boolean, extra: Object });
</script>

<template>
  <ResourceForm v-bind="{ resource, record, fieldsets, repeaters, values, canEdit }">
    <template #sidebar>
      <template v-if="extra">
        <div v-if="extra.scheduled" class="mt-4 rounded-xl bg-amber-50 p-3 text-xs text-amber-800 ring-1 ring-amber-200"><Icon name="clock" size="size-3.5 inline" /> Programmé pour le {{ formatDateTime(extra.published_at) }}.</div>
        <a :href="extra.preview_url" target="_blank" class="btn btn-ghost mt-2 w-full"><Icon name="eye" size="size-4" />Prévisualiser</a>
      </template>
      <div class="mt-4 rounded-xl bg-surface p-3 text-xs leading-relaxed text-slate-500"><strong class="text-ink">Publication différée :</strong> choisissez le statut « Publié » et une date future. L'article apparaîtra automatiquement à cette date.</div>
    </template>
  </ResourceForm>
</template>
