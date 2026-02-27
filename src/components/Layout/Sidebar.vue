<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { useAuthStore } from "@/stores/authStore";
import { useI18n } from "vue-i18n";
import { adminMenuApi } from "@/api/adminMenu";

const props = defineProps({
  open: { type: Boolean, default: true }
});

const route = useRoute();
const authStore = useAuthStore();
const { t, locale } = useI18n();
const dynamicCptMenus = ref([]);

const staticItems = computed(() => [
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
  { label: t("menu.postCustomFields"), to: { name: "post-custom-fields.manager" }, permission: "users:manage", icon: "Files" },
  { label: t("menu.cptBuilder"), to: { name: "cpt.builder" }, permission: "users:manage", icon: "Setting" }
]);

const resolvedMenu = computed(() => {
  const base = staticItems.value.filter((item) => authStore.hasPermission(item.permission));
  const cptItems = (dynamicCptMenus.value || []).map((item) => ({
    id: item.id,
    label: item.label,
    icon: item.icon || "Document",
    permission: "posts:manage",
    children: (item.children || []).map((child) => ({
      id: child.id,
      label: child.label,
      route: child.route
    }))
  }));
  return [...base, ...cptItems.filter((item) => authStore.hasPermission(item.permission))];
});

const iconFor = (iconName) => {
  const available = [
    "Document",
    "DataBoard",
    "Tickets",
    "Picture",
    "Collection",
    "ChatLineSquare",
    "Goods",
    "Grid",
    "User",
    "Discount",
    "Avatar",
    "EditPen",
    "Files",
    "Setting"
  ];
  return available.includes(iconName) ? iconName : "Document";
};

const isItemActive = (item) => {
  if (item.to?.name && route.name === item.to.name) {
    return true;
  }
  if (item.children?.length) {
    return item.children.some((child) => route.path.startsWith(child.route));
  }
  return false;
};

const isChildActive = (child) => route.path.startsWith(child.route);

const loadDynamicMenu = async () => {
  if (!authStore.isAdmin) {
    dynamicCptMenus.value = [];
    return;
  }
  try {
    dynamicCptMenus.value = await adminMenuApi.getMenu();
  } catch {
    dynamicCptMenus.value = [];
  }
};

onMounted(loadDynamicMenu);
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
        <template v-for="item in resolvedMenu" :key="item.id || item.label">
          <router-link
            v-if="!item.children?.length"
            :to="item.to"
            class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors hover:bg-sky-500/20"
            :class="{ 'bg-sky-500/30': isItemActive(item) }"
          >
            <el-icon><component :is="iconFor(item.icon)" /></el-icon>
            <span v-if="open">{{ item.label }}</span>
          </router-link>

          <div
            v-else
            class="rounded-xl border border-slate-700/40 p-2"
            :class="{ 'bg-sky-500/10': isItemActive(item) }"
          >
            <div class="mb-2 flex items-center gap-3 px-2 py-2 text-sm font-semibold">
              <el-icon><component :is="iconFor(item.icon)" /></el-icon>
              <span v-if="open">{{ item.label }}</span>
            </div>
            <div v-if="open" class="space-y-1">
              <router-link
                v-for="child in item.children"
                :key="child.id"
                :to="child.route"
                class="block rounded-lg px-3 py-2 text-xs transition-colors hover:bg-sky-500/20"
                :class="{ 'bg-sky-500/30': isChildActive(child) }"
              >
                {{ child.label }}
              </router-link>
            </div>
          </div>
        </template>
      </nav>
    </div>
  </aside>
</template>
