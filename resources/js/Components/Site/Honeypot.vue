<script setup>
/** Anti-spam: honeypot field humans never see (the encrypted timestamp is sent with the form data). */
const model = defineModel();
</script>

<template>
  <div class="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
    <label>Laissez ce champ vide <input v-model="model" type="text" name="website" tabindex="-1" autocomplete="off"></label>
  </div>
</template>
