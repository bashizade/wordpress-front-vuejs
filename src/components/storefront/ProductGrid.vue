<script setup>
import ProductCard from "@/components/storefront/ProductCard.vue";
import Loader from "@/components/storefront/Loader.vue";

const props = defineProps({
  products: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  view: { type: String, default: "grid" }
});
</script>

<template>
  <Loader v-if="loading" :rows="6" />

  <div v-else-if="!products.length" class="panel p-6 text-center text-slate-500">No products found.</div>

  <div
    v-else
    class="grid gap-4"
    :class="view === 'list' ? 'grid-cols-1' : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'"
  >
    <ProductCard v-for="product in products" :key="product.id" :product="product" />
  </div>
</template>
