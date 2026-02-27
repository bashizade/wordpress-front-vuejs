<script setup>
import { computed, onMounted } from "vue";
import { useShopStore } from "@/stores/shopStore";

const shopStore = useShopStore();
const year = new Date().getFullYear();
const cartCount = computed(() => shopStore.cartCount);

onMounted(() => {
  shopStore.fetchCart();
});
</script>

<template>
  <div class="min-h-screen">
    <header class="sticky top-0 z-30 border-b border-slate-200/60 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
      <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-4">
        <router-link to="/" class="text-xl font-black tracking-tight">Vue Store</router-link>
        <nav class="flex flex-wrap items-center gap-4 text-sm font-semibold">
          <router-link to="/products">Products</router-link>
          <router-link to="/blogs">Blog</router-link>
          <router-link to="/about">About</router-link>
          <router-link to="/contact">Contact</router-link>
          <router-link to="/cart">Cart ({{ cartCount }})</router-link>
          <router-link to="/login" class="rounded bg-slate-900 px-3 py-1.5 text-white dark:bg-slate-100 dark:text-slate-900">Admin</router-link>
        </nav>
      </div>
    </header>

    <main class="mx-auto w-full max-w-7xl px-4 py-6">
      <slot />
    </main>

    <footer class="mt-10 border-t border-slate-200 py-8 text-center text-sm text-slate-500 dark:border-slate-800">
      <p>© {{ year }} Vue Storefront. All rights reserved.</p>
    </footer>
  </div>
</template>
