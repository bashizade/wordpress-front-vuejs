<script setup>
import { computed } from "vue";
import RatingStars from "@/components/storefront/RatingStars.vue";
import AddToCartButton from "@/components/storefront/AddToCartButton.vue";

const props = defineProps({
  product: { type: Object, required: true }
});

const image = computed(
  () => props.product.images?.[0]?.src || "https://via.placeholder.com/640x640?text=No+Image"
);
const onSale = computed(() => Boolean(props.product.on_sale));
const outOfStock = computed(() => props.product.stock_status === "outofstock");
</script>

<template>
  <article class="panel overflow-hidden transition duration-300 hover:-translate-y-1 hover:shadow-xl">
    <router-link :to="`/product/${product.slug || product.id}`" class="block">
      <div class="relative">
        <img :src="image" :alt="product.name" class="h-56 w-full object-cover" loading="lazy" />
        <span v-if="onSale" class="absolute left-2 top-2 rounded bg-rose-600 px-2 py-1 text-xs text-white">Sale</span>
        <span v-if="outOfStock" class="absolute right-2 top-2 rounded bg-slate-700 px-2 py-1 text-xs text-white">
          Out of stock
        </span>
      </div>
    </router-link>

    <div class="space-y-2 p-4">
      <router-link :to="`/product/${product.slug || product.id}`" class="line-clamp-2 text-base font-semibold">
        {{ product.name }}
      </router-link>
      <RatingStars :rating="Number(product.average_rating || 0)" />
      <div class="flex items-center gap-2">
        <span class="text-lg font-bold text-sky-600">{{ product.price_html ? '' : product.price }}</span>
        <span v-if="product.regular_price && product.sale_price" class="text-sm text-slate-400 line-through">
          {{ product.regular_price }}
        </span>
      </div>
      <AddToCartButton :product-id="product.id" :disabled="outOfStock" />
    </div>
  </article>
</template>
