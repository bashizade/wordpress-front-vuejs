<script setup>
import { computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/authStore";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const items = computed(() => [
  { label: "داشبورد", to: { name: "user.dashboard" }, icon: "DataBoard" },
  { label: "پروفایل", to: { name: "user.profile" }, icon: "User" },
  { label: "سفارش های من", to: { name: "user.orders" }, icon: "Tickets" },
  { label: "آدرس ها", to: { name: "user.addresses" }, icon: "Location" }
]);

const onLogout = () => {
  authStore.logout();
  router.push({ name: "login" });
};
</script>

<template>
  <aside class="fixed right-0 top-0 z-20 h-screen w-72 border-l border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="border-b border-slate-200 px-4 py-5 dark:border-slate-700">
      <h2 class="text-lg font-bold">پنل کاربری</h2>
      <p class="text-xs text-slate-500">{{ authStore.username }}</p>
    </div>
    <nav class="space-y-2 p-3">
      <router-link
        v-for="item in items"
        :key="item.label"
        :to="item.to"
        class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-800"
        :class="{ 'bg-slate-100 dark:bg-slate-800': route.name === item.to.name }"
      >
        <el-icon><component :is="item.icon" /></el-icon>
        <span>{{ item.label }}</span>
      </router-link>
      <el-button class="mt-2 w-full" type="danger" plain @click="onLogout">خروج</el-button>
    </nav>
  </aside>
</template>
