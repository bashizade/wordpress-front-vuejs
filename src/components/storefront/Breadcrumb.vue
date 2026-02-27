<script setup>
import { computed } from "vue";
import { useRoute } from "vue-router";

const props = defineProps({
  items: { type: Array, default: () => [] }
});

const route = useRoute();
const builtItems = computed(() =>
  props.items.length
    ? props.items
    : [{ label: "Home", to: "/" }, { label: route.meta?.title || route.name || "Page" }]
);
</script>

<template>
  <nav class="mb-4 text-sm text-slate-500">
    <ol class="flex flex-wrap items-center gap-2">
      <li v-for="(item, index) in builtItems" :key="`${item.label}_${index}`" class="flex items-center gap-2">
        <router-link v-if="item.to" :to="item.to" class="hover:text-sky-600">{{ item.label }}</router-link>
        <span v-else class="font-semibold text-slate-700 dark:text-slate-200">{{ item.label }}</span>
        <span v-if="index < builtItems.length - 1">/</span>
      </li>
    </ol>
  </nav>
</template>
