<script setup>
import { useI18n } from "vue-i18n";

const { t } = useI18n();
const open = defineModel("open", { type: Boolean, default: false });

const props = defineProps({
  username: {
    type: String,
    default: ""
  }
});

const emit = defineEmits(["confirm"]);
</script>

<template>
  <el-dialog v-model="open" :title="t('users.deleteTitle')" width="500">
    <p class="text-sm text-slate-600 dark:text-slate-300">
      {{ t("users.deleteMessage") }}
      <strong>{{ username }}</strong>
    </p>
    <template #footer>
      <el-button @click="open = false">{{ t("common.cancel") }}</el-button>
      <el-button type="danger" @click="$emit('confirm')">{{ t("common.delete") }}</el-button>
    </template>
  </el-dialog>
</template>
