<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Ui/Icon.vue';
import Emphasis from '@/Components/Ui/Emphasis.vue';
import RichText from '@/Components/Ui/RichText.vue';
import SectionHeading from '@/Components/Site/SectionHeading.vue';
import { initials } from '@/format';

defineProps({ section: { type: Object, required: true } });
</script>

<template>
  <section v-if="section.data?.length" class="section" :id="section.anchor || undefined">
    <div class="container-x">
      <SectionHeading :section="section" />
      <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <figure v-for="t in section.data" :key="t.id" class="card flex flex-col" data-reveal>
          <Icon name="quote" size="size-8 text-primary/60" />
          <blockquote class="mt-4 flex-1 text-[0.95rem] leading-relaxed text-slate-300">« {{ t.quote }} »</blockquote>
          <figcaption class="mt-6 flex items-center gap-3 border-t border-white/10 pt-5">
            <img v-if="t.photo" :src="t.photo" alt="" class="size-11 rounded-full object-cover" loading="lazy" width="44" height="44"><span v-else class="grid size-11 place-items-center rounded-full bg-primary/10 text-sm font-bold text-primary-400">{{ initials(t.author_name) }}</span>
            <span><span class="block text-sm font-semibold text-white">{{ t.author_name }}</span><span class="block text-xs text-slate-500">{{ t.author_role }}<template v-if="t.organization"> · {{ t.organization }}</template></span></span>
          </figcaption>
        </figure>
      </div>
    </div>
  </section>
</template>
