<script setup>
import { computed } from "vue";
import { useRoute } from "vue-router";
import { useAuthStore } from "@/stores/authStore";
import { useI18n } from "vue-i18n";

const props = defineProps({
  open: { type: Boolean, default: true }
});

const route = useRoute();
const authStore = useAuthStore();
const { t, locale } = useI18n();

const menuItems = computed(() => [
  { label: t("menu.dashboard"), to: { name: "admin.dashboard" }, permission: "dashboard:view", icon: "DataBoard" },
  { label: t("menu.posts"), to: { name: "posts.list" }, permission: "posts:manage", icon: "Document" },
  { label: t("menu.pages"), to: { name: "pages.list" }, permission: "pages:manage", icon: "Tickets" },
  { label: t("menu.media"), to: { name: "media.list" }, permission: "media:manage", icon: "Picture" },
  { label: t("menu.taxonomy"), to: { name: "taxonomy.list" }, permission: "taxonomy:manage", icon: "Collection" },
  { label: t("menu.comments"), to: { name: "comments.list" }, permission: "comments:manage", icon: "ChatLineSquare" },
  { label: t("menu.products"), to: { name: "products.list" }, permission: "products:manage", icon: "Goods" },
  { label: t("menu.attributes"), to: { name: "attributes.list" }, permission: "products:manage", icon: "Grid" },
  { label: t("menu.orders"), to: { name: "orders.list" }, permission: "orders:manage", icon: "Tickets" },
  { label: t("menu.customers"), to: { name: "customers.list" }, permission: "customers:view", icon: "User" },
  { label: t("menu.coupons"), to: { name: "coupons.list" }, permission: "coupons:manage", icon: "Discount" },
  { label: t("menu.users"), to: { name: "users.list" }, permission: "users:manage", icon: "Avatar" },
  { label: t("menu.customFields"), to: { name: "users.custom-fields" }, permission: "users:manage", icon: "EditPen" },
  { label: t("menu.postCustomFields"), to: { name: "post-custom-fields.manager" }, permission: "users:manage", icon: "Files" }
]);

const visibleItems = computed(() =>
  menuItems.value.filter((item) => authStore.hasPermission(item.permission))
);

const isActive = (item) => route.name === item.to.name;
</script>

<template>
  <aside
    class="fixed top-0 z-20 h-screen border-slate-700/20 bg-slate-950 text-slate-100 transition-all duration-300"
    :class="[locale === 'fa' ? 'right-0 border-l' : 'left-0 border-r', open ? 'w-72' : 'w-24']"
  >
    <div class="flex h-full flex-col">
      <div class="border-b border-slate-100/10 px-4 py-5">
        <h1 class="text-lg font-bold tracking-wider">{{ open ? t("app.name") : "CMS" }}</h1>
      </div>
      <nav class="flex-1 space-y-2 overflow-y-auto p-3">
        <router-link
          v-for="item in visibleItems"
          :key="item.label"
          :to="item.to"
          class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors hover:bg-sky-500/20"
          :class="{ 'bg-sky-500/30': isActive(item) }"
        >
          <el-icon><component :is="item.icon" /></el-icon>
          <span v-if="open">{{ item.label }}</span>
        </router-link>
      </nav>
    </div>
  </aside>
</template>
