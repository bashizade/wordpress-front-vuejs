<script setup>
import { computed, onMounted, onUnmounted, ref } from "vue";
import Sidebar from "@/components/Layout/Sidebar.vue";
import Topbar from "@/components/Layout/Topbar.vue";
import { useAuthStore } from "@/stores/authStore";
import { useI18n } from "vue-i18n";

const sidebarOpen = ref(true);
const authStore = useAuthStore();
const { locale } = useI18n();
const contentShiftClass = computed(() => {
  if (locale.value === "fa") return sidebarOpen.value ? "lg:pr-72" : "lg:pr-24";
  return sidebarOpen.value ? "lg:pl-72" : "lg:pl-24";
});

onMounted(() => {
  authStore.startTokenHeartbeat();
});

onUnmounted(() => {
  authStore.stopTokenHeartbeat();
});
</script>

<template>
  <div class="min-h-screen">
    <Sidebar :open="sidebarOpen" />
    <div :class="[contentShiftClass]" class="transition-all duration-300">
      <Topbar v-model:sidebar-open="sidebarOpen" />
      <main class="p-4 md:p-6">
        <router-view />
      </main>
    </div>
  </div>
</template>
