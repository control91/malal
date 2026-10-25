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
      <ul class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <li v-for="member in section.data" :key="member.id" class="card text-center" data-reveal>
          <img v-if="member.photo" :src="member.photo" :alt="member.name" class="mx-auto size-28 rounded-full object-cover ring-4 ring-primary/20" loading="lazy" width="112" height="112">
          <span v-else class="mx-auto grid size-28 place-items-center rounded-full bg-gradient-to-br from-primary to-accent font-display text-3xl text-white">{{ initials(member.name) }}</span>
          <h3 class="mt-5 font-display text-lg">{{ member.name }}</h3>
          <p class="text-sm font-medium text-primary-400">{{ member.role }}</p>
          <p v-if="member.bio" class="mt-3 line-clamp-4 text-sm leading-relaxed text-slate-400">{{ member.bio }}</p>
          <a v-if="member.linkedin_url" :href="member.linkedin_url" target="_blank" rel="noopener" class="mt-4 inline-flex text-slate-400 hover:text-primary-400"><Icon name="brand-linkedin" label="LinkedIn" /></a>
        </li>
      </ul>
    </div>
  </section>
</template>
