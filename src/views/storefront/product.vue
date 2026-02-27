<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { ElMessage } from "element-plus";
import SiteShell from "@/components/storefront/SiteShell.vue";
import Loader from "@/components/storefront/Loader.vue";
import Breadcrumb from "@/components/storefront/Breadcrumb.vue";
import ProductGrid from "@/components/storefront/ProductGrid.vue";
import RatingStars from "@/components/storefront/RatingStars.vue";
import AddToCartButton from "@/components/storefront/AddToCartButton.vue";
import { useShopStore } from "@/stores/shopStore";
import { api } from "@/services/api";

const route = useRoute();
const shopStore = useShopStore();

const loadingRelated = ref(false);
const related = ref([]);
const selectedAttributes = ref({});
const selectedVariation = ref(null);

const product = computed(() => shopStore.currentProduct || {});
const variationOptions = computed(() => shopStore.currentVariations || []);
const inStock = computed(() => product.value.stock_status !== "outofstock");

const displayPrice = computed(() => {
  if (selectedVariation.value?.price) {
    return selectedVariation.value.price;
  }
  if (product.value.sale_price) {
    return product.value.sale_price;
  }
  return product.value.price;
});

const resolveVariation = () => {
  if (!variationOptions.value.length) {
    selectedVariation.value = null;
    return;
  }
  const found = variationOptions.value.find((variation) =>
    variation.attributes.every(
      (att) => String(selectedAttributes.value[att.name] || "") === String(att.option || "")
    )
  );
  selectedVariation.value = found || null;
};

const load = async () => {
  await shopStore.fetchProduct(route.params.slug);
  document.title = `${shopStore.currentProduct?.name || "Product"} | Vue Store`;
  selectedAttributes.value = {};
  selectedVariation.value = null;

  if (shopStore.currentProduct?.related_ids?.length) {
    loadingRelated.value = true;
    try {
      const ids = shopStore.currentProduct.related_ids.slice(0, 4);
      const products = await Promise.all(ids.map((itemId) => api.getProduct(itemId)));
      related.value = products.filter(Boolean);
    } catch {
      related.value = [];
    } finally {
      loadingRelated.value = false;
    }
  }
};

watch(selectedAttributes, resolveVariation, { deep: true });
watch(
  () => route.params.slug,
  async () => {
    try {
      await load();
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "Product not found");
    }
  }
);

onMounted(async () => {
  try {
    await load();
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Product not found");
  }
});
</script>

<template>
  <SiteShell>
    <Loader v-if="shopStore.loadingProduct" :rows="8" />

    <template v-else>
      <Breadcrumb :items="[{ label: 'Home', to: '/' }, { label: 'Products', to: '/products' }, { label: product.name }]" />

      <section class="grid gap-6 lg:grid-cols-2">
        <div class="panel overflow-hidden">
          <el-carousel v-if="product.images?.length" height="460px" indicator-position="outside">
            <el-carousel-item v-for="img in product.images" :key="img.id">
              <img :src="img.src" :alt="img.alt || product.name" class="h-full w-full object-cover" />
            </el-carousel-item>
          </el-carousel>
          <img v-else src="https://via.placeholder.com/920x680?text=No+Image" alt="No image" class="h-full w-full" />
        </div>

        <div class="space-y-4">
          <h1 class="text-3xl font-black">{{ product.name }}</h1>
          <RatingStars :rating="Number(product.average_rating || 0)" />
          <div class="text-2xl font-black text-sky-600">{{ displayPrice }}</div>
          <div class="text-sm text-slate-500" v-html="product.short_description || product.description" />

          <div v-if="product.attributes?.length" class="space-y-3">
            <div v-for="attribute in product.attributes" :key="attribute.id || attribute.name" class="panel p-3">
              <p class="mb-2 text-sm font-semibold">{{ attribute.name }}</p>
              <el-select v-model="selectedAttributes[attribute.name]" clearable placeholder="Select option">
                <el-option v-for="option in attribute.options" :key="option" :label="option" :value="option" />
              </el-select>
            </div>
          </div>

          <el-alert v-if="!inStock" type="error" :closable="false" title="Out of stock" />

          <AddToCartButton
            :product-id="product.id"
            :variation-id="selectedVariation?.id || 0"
            :variation="selectedVariation?.attributes || []"
            :disabled="!inStock"
          />
        </div>
      </section>

      <section class="mt-10">
        <h2 class="mb-4 text-2xl font-black">Reviews</h2>
        <el-empty v-if="!product.rating_count" description="No reviews yet" />
        <div v-else class="panel p-4">
          <p>Total ratings: {{ product.rating_count }}</p>
          <RatingStars :rating="Number(product.average_rating || 0)" />
        </div>
      </section>

      <section class="mt-10">
        <h2 class="mb-4 text-2xl font-black">Related products</h2>
        <ProductGrid :products="related" :loading="loadingRelated" />
      </section>
    </template>
  </SiteShell>
</template>
