<script setup>
import HeroSection from './HeroSection.vue';
import MarqueeSection from './MarqueeSection.vue';
import DomainsSection from './DomainsSection.vue';
import SolutionsSection from './SolutionsSection.vue';
import FeaturesSection from './FeaturesSection.vue';
import VisionSection from './VisionSection.vue';
import NewsSection from './NewsSection.vue';
import TextSection from './TextSection.vue';
import SplitSection from './SplitSection.vue';
import RoadmapSection from './RoadmapSection.vue';
import TeamSection from './TeamSection.vue';
import PartnersSection from './PartnersSection.vue';
import TestimonialsSection from './TestimonialsSection.vue';
import CaseStudiesSection from './CaseStudiesSection.vue';
import FaqSection from './FaqSection.vue';
import CtaSection from './CtaSection.vue';

defineProps({ sections: { type: Array, required: true } });
const components = {
    hero: HeroSection, marquee: MarqueeSection, domains: DomainsSection, solutions: SolutionsSection, features: FeaturesSection,
    vision: VisionSection, news: NewsSection, text: TextSection, split: SplitSection, roadmap: RoadmapSection, team: TeamSection,
    partners: PartnersSection, testimonials: TestimonialsSection, case_studies: CaseStudiesSection, faq: FaqSection, cta: CtaSection,
};
</script>

<template>
  <template v-for="section in sections" :key="section.id">
    <component :is="components[section.kind]" v-if="components[section.kind]" :section="section" />
  </template>
</template>
