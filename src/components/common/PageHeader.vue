<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: "" }
});
</script>

<template>
  <div class="mb-4 flex items-end justify-between">
    <div>
      <h2 class="title">{{ title }}</h2>
      <p class="text-sm text-slate-500">{{ subtitle }}</p>
    </div>
    <slot />
  </div>
</template>
