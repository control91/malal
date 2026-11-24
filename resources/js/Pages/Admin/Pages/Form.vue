<script setup>
import { Link, router } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import ResourceForm from '@/Components/Admin/ResourceForm.vue';
import { confirmDialog } from '@/confirm';

const props = defineProps({ resource: Object, record: Object, fieldsets: Array, repeaters: Array, values: Object, canEdit: Boolean, extra: Object });
const move = (s, direction) => router.post(route('admin.sections.move', [s.id, direction]), {}, { preserveScroll: true });
const toggle = (s) => router.post(route('admin.resources.toggle', ['sections', s.id]), {}, { preserveScroll: true });
const remove = async (s) => {
    if (await confirmDialog({ title: 'Supprimer cette section ?', text: 'La section et ses éléments seront supprimés.', label: 'Supprimer' })) {
        router.delete(route('admin.resources.destroy', ['sections', s.id]), { data: { redirect: window.location.pathname }, preserveScroll: true });
    }
};
</script>

<template>
  <ResourceForm v-bind="{ resource, record, fieldsets, repeaters, values, canEdit }">
    <template #after>
      <section v-if="record" id="sections" class="dash-card">
        <div class="dash-card-header">
          <div><h2 class="dash-card-title">Sections de la page</h2><p class="mt-0.5 text-xs text-slate-500">Affichées dans cet ordre. Chaque type de section a sa propre mise en forme.</p></div>
          <Link v-if="canEdit" :href="route('admin.resources.create', { resource: 'sections', page_id: record.id, order: (extra.sections.length + 1) * 10, is_active: 1 })" class="btn btn-primary btn-sm"><Icon name="plus" size="size-4" />Ajouter une section</Link>
        </div>
        <ol class="divide-y divide-line">
          <li v-for="(s, i) in extra.sections" :key="s.id" class="flex items-center gap-4 px-5 py-3.5" :class="{ 'opacity-60': !s.is_active }">
            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface text-xs font-bold text-slate-500">{{ i + 1 }}</span>
            <div class="min-w-0 flex-1">
              <Link :href="route('admin.resources.edit', ['sections', s.id])" class="block truncate font-semibold text-ink hover:text-primary">{{ s.title || s.kind }}</Link>
              <p class="text-xs text-slate-500">{{ s.kind }}<template v-if="s.items_count"> · {{ s.items_count }} élément(s)</template><template v-if="!s.is_active"> · masquée</template></p>
            </div>
            <div v-if="canEdit" class="flex items-center gap-1">
              <button v-if="i > 0" type="button" class="btn btn-ghost btn-sm !px-2" title="Monter" @click="move(s, 'up')"><Icon name="arrow-up" size="size-4" label="Monter" /></button>
              <button v-if="i < extra.sections.length - 1" type="button" class="btn btn-ghost btn-sm !px-2" title="Descendre" @click="move(s, 'down')"><Icon name="arrow-down" size="size-4" label="Descendre" /></button>
              <button type="button" class="btn btn-ghost btn-sm !px-2" :title="s.is_active ? 'Masquer' : 'Afficher'" @click="toggle(s)"><Icon :name="s.is_active ? 'eye' : 'eye-off'" size="size-4" :label="s.is_active ? 'Masquer' : 'Afficher'" /></button>
              <Link :href="route('admin.resources.edit', ['sections', s.id])" class="btn btn-ghost btn-sm !px-2" title="Modifier"><Icon name="pencil" size="size-4" label="Modifier" /></Link>
              <button type="button" class="btn btn-ghost btn-sm !px-2 text-rose-500" title="Supprimer" @click="remove(s)"><Icon name="trash-2" size="size-4" label="Supprimer" /></button>
            </div>
          </li>
          <li v-if="!extra.sections.length" class="px-5 py-8 text-center text-sm text-slate-400">Aucune section. Le contenu principal ci-dessus est affiché seul.</li>
        </ol>
      </section>
    </template>
    <template #sidebar><div class="mt-5 rounded-xl bg-surface p-3 text-xs text-slate-500">Astuce : dans un titre, entourez des mots d'astérisques (<code>*l'Afrique*</code>) pour les mettre en valeur.</div></template>
  </ResourceForm>
</template>
