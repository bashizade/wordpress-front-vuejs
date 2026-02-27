<script setup>
import { onMounted, reactive, ref } from "vue";
import SiteShell from "@/components/storefront/SiteShell.vue";
import ProductFilter from "@/components/storefront/ProductFilter.vue";
import ProductGrid from "@/components/storefront/ProductGrid.vue";
import Pagination from "@/components/storefront/Pagination.vue";
import Breadcrumb from "@/components/storefront/Breadcrumb.vue";
import { useShopStore } from "@/stores/shopStore";
import { useSiteStore } from "@/stores/siteStore";

document.title = "Products | Vue Store";

const shopStore = useShopStore();
const siteStore = useSiteStore();

const filters = reactive({
  page: 1,
  per_page: 12,
  search: "",
  category: "",
  min_price: "",
  max_price: "",
  orderBy: "date",
  viewMode: "grid"
});

const total = ref(0);

const apply = async () => {
  const params = {
    page: filters.page,
    per_page: filters.per_page,
    search: filters.search || undefined,
    category: filters.category || undefined,
    min_price: filters.min_price || undefined,
    max_price: filters.max_price || undefined
  };
  if (filters.orderBy === "price_asc") {
    params.orderby = "price";
    params.order = "asc";
  } else if (filters.orderBy === "price_desc") {
    params.orderby = "price";
    params.order = "desc";
  } else {
    params.orderby = "date";
    params.order = "desc";
  }

  const data = await shopStore.fetchProducts(params);
  total.value = filters.page * filters.per_page + (data.length === filters.per_page ? 1 : 0);
};

const onPage = async (page) => {
  filters.page = page;
  await apply();
};

onMounted(async () => {
  await siteStore.fetchCategories();
  await apply();
});
</script>

<template>
  <SiteShell>
    <Breadcrumb :items="[{ label: 'Home', to: '/' }, { label: 'Products' }]" />
    <ProductFilter v-model="filters" :categories="siteStore.productCategories" @apply="apply" />

    <div class="mt-6">
      <ProductGrid :products="shopStore.products" :loading="shopStore.loadingProducts" :view="filters.viewMode" />
      <Pagination :current-page="filters.page" :page-size="filters.per_page" :total="total" @change="onPage" />
    </div>
  </SiteShell>
</template>
