<script setup>
const props = defineProps({
  rating: { type: Number, default: 0 },
  count: { type: Number, default: 5 }
});
</script>

<template>
  <div class="flex items-center gap-1 text-amber-500">
    <span v-for="n in count" :key="n">{{ n <= Math.round(props.rating) ? "?" : "?" }}</span>
    <span class="text-xs text-slate-500">({{ props.rating || 0 }})</span>
  </div>
</template>
