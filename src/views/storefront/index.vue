<script setup>
import { onMounted, ref } from "vue";
import { useBlogStore } from "@/stores/blogStore";
import { useShopStore } from "@/stores/shopStore";
import { useSiteStore } from "@/stores/siteStore";
import SiteShell from "@/components/storefront/SiteShell.vue";
import ProductGrid from "@/components/storefront/ProductGrid.vue";
import BlogCard from "@/components/storefront/BlogCard.vue";
import Loader from "@/components/storefront/Loader.vue";

const shopStore = useShopStore();
const blogStore = useBlogStore();
const siteStore = useSiteStore();

const loading = ref(true);
const heroImages = ref([]);

document.title = "Home | Vue Store";

const load = async () => {
  loading.value = true;
  try {
    await Promise.all([
      siteStore.fetchHome(),
      siteStore.fetchCategories(),
      shopStore.fetchProducts({ featured: true, per_page: 8 }),
      blogStore.fetchPosts({ per_page: 3 })
    ]);

    const media = siteStore.homePage?._embedded?.["wp:featuredmedia"] || [];
    heroImages.value = media.length
      ? media.map((item) => item.source_url)
      : [
          "https://images.unsplash.com/photo-1555529771-7888783a18d3?w=1400",
          "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=1400"
        ];
  } finally {
    loading.value = false;
  }
};

onMounted(load);
</script>

<template>
  <SiteShell>
    <Loader v-if="loading" :rows="8" />

    <template v-else>
      <section class="panel mb-8 overflow-hidden">
        <el-carousel height="420px" indicator-position="outside" motion-blur>
          <el-carousel-item v-for="(item, idx) in heroImages" :key="idx">
            <img :src="item" alt="hero" class="h-full w-full object-cover" />
          </el-carousel-item>
        </el-carousel>
      </section>

      <section class="mb-8 grid gap-4 md:grid-cols-3">
        <div class="panel p-6 md:col-span-2">
          <h2 class="mb-2 text-2xl font-black">Featured Products</h2>
          <p class="text-slate-500">Top picks from our newest collections.</p>
        </div>
        <div class="panel flex items-center justify-center p-6">
          <router-link to="/products" class="rounded bg-sky-600 px-5 py-2 text-white">Shop Now</router-link>
        </div>
      </section>

      <ProductGrid :products="shopStore.products.slice(0, 8)" :loading="false" />

      <section class="mt-10">
        <h2 class="mb-4 text-2xl font-black">Product Categories</h2>
        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
          <div v-for="cat in siteStore.productCategories.slice(0, 10)" :key="cat.id" class="panel p-4">
            <h3 class="font-semibold">{{ cat.name }}</h3>
            <p class="text-sm text-slate-500">{{ cat.count || 0 }} items</p>
          </div>
        </div>
      </section>

      <section class="mt-10 grid gap-4 md:grid-cols-3">
        <div class="panel p-6">
          <h3 class="text-lg font-bold">Fast Delivery</h3>
          <p class="text-slate-500">Nationwide shipping with reliable fulfillment.</p>
        </div>
        <div class="panel p-6">
          <h3 class="text-lg font-bold">Secure Payment</h3>
          <p class="text-slate-500">Checkout is protected with best-practice security.</p>
        </div>
        <div class="panel p-6">
          <h3 class="text-lg font-bold">Premium Support</h3>
          <p class="text-slate-500">Our support team is here to help every day.</p>
        </div>
      </section>

      <section class="mt-12">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-2xl font-black">Latest Blog Posts</h2>
          <router-link to="/blogs" class="text-sky-600">View all</router-link>
        </div>
        <div class="grid gap-4 md:grid-cols-3">
          <BlogCard v-for="post in blogStore.posts" :key="post.id" :post="post" />
        </div>
      </section>
    </template>
  </SiteShell>
</template>
