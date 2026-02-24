<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import faLocale from "element-plus/es/locale/lang/fa";
import enLocale from "element-plus/es/locale/lang/en";

const { locale } = useI18n();
const epLocale = computed(() => (locale.value === "fa" ? faLocale : enLocale));
const direction = computed(() => (locale.value === "fa" ? "rtl" : "ltr"));
</script>

<template>
  <el-config-provider :locale="epLocale" :direction="direction">
    <router-view />
  </el-config-provider>
</template>
