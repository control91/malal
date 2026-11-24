<script setup>
import { useId } from 'vue';

/** Decorative ring made of rotated ellipses (original artwork). */
const uid = useId();
const angles = Array.from({ length: 24 }, (_, i) => i * 7.5);
</script>

<template>
  <svg viewBox="0 0 400 400" class="size-full" aria-hidden="true">
    <defs>
      <linearGradient :id="`ring-${uid}`" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="var(--brand-accent)" /><stop offset=".5" stop-color="var(--brand-primary)" /><stop offset="1" stop-color="#7c3aed" /></linearGradient>
      <radialGradient :id="`glow-${uid}`"><stop offset="0" stop-color="var(--brand-primary)" stop-opacity=".45" /><stop offset="1" stop-color="var(--brand-primary)" stop-opacity="0" /></radialGradient>
    </defs>
    <circle cx="200" cy="200" r="190" :fill="`url(#glow-${uid})`" />
    <g fill="none" :stroke="`url(#ring-${uid})`" stroke-width="1.6" class="origin-center motion-safe:animate-[spin-slow_40s_linear_infinite]" style="transform-box: fill-box">
      <ellipse v-for="a in angles" :key="a" cx="200" cy="200" rx="150" ry="56" :transform="`rotate(${a} 200 200)`" />
    </g>
  </svg>
</template>
