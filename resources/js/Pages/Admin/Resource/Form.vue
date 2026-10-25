<script setup>
import ResourceForm from '@/Components/Admin/ResourceForm.vue';

defineProps({ resource: Object, record: Object, fieldsets: Array, repeaters: Array, values: Object, canEdit: Boolean, extra: Object });
</script>

<template>
  <ResourceForm v-bind="{ resource, record, fieldsets, repeaters, values, canEdit }" />
</template>
