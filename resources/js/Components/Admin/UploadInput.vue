<script setup>
import { computed, ref } from 'vue';
import Icon from '@/Components/Ui/Icon.vue';

/** Image / file upload with preview. Value: File | { path, url } | null. Emits "clear". */
const props = defineProps({ accept: String, current: Object, disabled: Boolean, image: { type: Boolean, default: true } });
const model = defineModel();
const clear = defineModel('clear', { type: Boolean, default: false });
const preview = ref(null);
const url = computed(() => preview.value || (clear.value ? null : props.current?.url));
const onChange = (event) => {
    const file = event.target.files?.[0];
    model.value = file ?? null;
    preview.value = file && file.type.startsWith('image/') ? URL.createObjectURL(file) : null;
    clear.value = false;
};
const isImage = computed(() => url.value && /\.(jpe?g|png|webp|gif|svg)$|^blob:/i.test(url.value));
</script>

<template>
  <div class="flex items-center gap-4 rounded-xl border border-dashed border-line bg-white p-3">
    <img v-if="isImage" :src="url" alt="" class="size-16 shrink-0 rounded-lg object-cover ring-1 ring-line">
    <span v-else class="grid size-16 shrink-0 place-items-center rounded-lg bg-surface text-slate-400"><Icon :name="image ? 'image' : 'file'" size="size-6" /></span>
    <div class="min-w-0 flex-1 text-sm">
      <a v-if="current?.url && !clear" :href="current.url" target="_blank" class="block truncate text-xs text-primary">{{ current.path }}</a>
      <input type="file" class="field-file mt-1" :accept="accept" :disabled="disabled" @change="onChange">
      <label v-if="current?.url && !disabled" class="mt-2 flex items-center gap-2 text-xs text-slate-500"><input v-model="clear" type="checkbox" class="field-checkbox"> Supprimer le fichier actuel</label>
    </div>
  </div>
</template>
