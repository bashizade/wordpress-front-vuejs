<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { useAuthStore } from "@/stores/authStore";
import { setLocale } from "@/i18n";

const sidebarOpen = defineModel("sidebarOpen", { type: Boolean, default: true });

const authStore = useAuthStore();
const router = useRouter();
const { t, locale } = useI18n();

const localeLabel = computed(() => (locale.value === "fa" ? "FA" : "EN"));

const onLogout = () => {
  authStore.logout();
  router.push({ name: "login" });
};

const switchLanguage = (next) => {
  setLocale(next);
};
</script>

<template>
  <header class="sticky top-0 z-10 border-b border-slate-300/50 bg-white/70 backdrop-blur dark:bg-slate-900/70">
    <div class="flex items-center justify-between px-4 py-3 md:px-6">
      <div class="flex items-center gap-3">
        <el-button circle @click="sidebarOpen = !sidebarOpen">
          <el-icon><Operation /></el-icon>
        </el-button>
        <span class="text-sm text-slate-500">{{ t("topbar.welcome") }}, {{ authStore.username }}</span>
      </div>
      <div class="flex items-center gap-3">
        <el-dropdown trigger="click">
          <el-button>{{ t("topbar.language") }}: {{ localeLabel }}</el-button>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item @click="switchLanguage('fa')">{{ t("topbar.fa") }}</el-dropdown-item>
              <el-dropdown-item @click="switchLanguage('en')">{{ t("topbar.en") }}</el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
        <el-button @click="authStore.toggleTheme()">
          <el-icon><Moon /></el-icon>
          {{ authStore.theme === "dark" ? t("topbar.themeDark") : t("topbar.themeLight") }}
        </el-button>
        <el-dropdown trigger="click">
          <el-button>
            <el-icon><UserFilled /></el-icon>
            {{ authStore.role }}
          </el-button>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item @click="onLogout">{{ t("topbar.logout") }}</el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </div>
  </header>
</template>
