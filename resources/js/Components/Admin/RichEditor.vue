<script setup>
import { onBeforeUnmount, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Icon from '@/Components/Ui/Icon.vue';

/** Rich text editor (Tiptap). Output is sanitised again server-side on save. */
const props = defineProps({ disabled: { type: Boolean, default: false } });
const model = defineModel({ type: String, default: '' });
const editor = useEditor({
    content: model.value || '',
    editable: !props.disabled,
    extensions: [StarterKit.configure({ heading: { levels: [2, 3, 4] }, link: false }), Link.configure({ openOnClick: false, protocols: ['mailto', 'tel'] })],
    onUpdate: ({ editor: e }) => { model.value = e.isEmpty ? '' : e.getHTML(); },
});
watch(model, (value) => { if (editor.value && value !== editor.value.getHTML()) editor.value.commands.setContent(value || '', { emitUpdate: false }); });
onBeforeUnmount(() => editor.value?.destroy());

const setLink = () => {
    const previous = editor.value.getAttributes('link').href || '';
    const url = window.prompt('Adresse du lien (laisser vide pour retirer)', previous);
    if (url === null) return;
    if (url === '') editor.value.chain().focus().unsetLink().run();
    else if (/^(https?:|mailto:|tel:|\/)/.test(url)) editor.value.chain().focus().setLink({ href: url }).run();
};
const actions = [
    ['bold', 'Gras', () => editor.value.chain().focus().toggleBold().run(), 'B'],
    ['italic', 'Italique', () => editor.value.chain().focus().toggleItalic().run(), 'I'],
    ['heading2', 'Titre', () => editor.value.chain().focus().toggleHeading({ level: 2 }).run(), 'H2'],
    ['heading3', 'Sous-titre', () => editor.value.chain().focus().toggleHeading({ level: 3 }).run(), 'H3'],
    ['bulletList', 'Liste', () => editor.value.chain().focus().toggleBulletList().run(), 'list'],
    ['orderedList', 'Liste numérotée', () => editor.value.chain().focus().toggleOrderedList().run(), 'list-checks'],
    ['blockquote', 'Citation', () => editor.value.chain().focus().toggleBlockquote().run(), 'quote'],
    ['link', 'Lien', setLink, 'link'],
];
const isActive = (key) => {
    if (!editor.value) return false;
    if (key === 'heading2') return editor.value.isActive('heading', { level: 2 });
    if (key === 'heading3') return editor.value.isActive('heading', { level: 3 });
    return editor.value.isActive(key);
};
</script>

<template>
  <div>
    <div v-if="!disabled" class="editor-toolbar" role="toolbar" aria-label="Mise en forme">
      <button v-for="[key, label, run, glyph] in actions" :key="key" type="button" :title="label" :aria-label="label" :class="{ 'is-active': isActive(key) }" @click="run">
        <Icon v-if="glyph.length > 2" :name="glyph" size="size-4" /><span v-else class="text-xs font-bold">{{ glyph }}</span>
      </button>
      <button type="button" title="Annuler" aria-label="Annuler" @click="editor.chain().focus().undo().run()"><Icon name="arrow-left" size="size-4" /></button>
    </div>
    <EditorContent :editor="editor" class="editor-content" />
  </div>
</template>
