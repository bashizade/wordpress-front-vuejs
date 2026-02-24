<script setup>
import { reactive, watch } from "vue";
import { ElMessage } from "element-plus";
import { mediaApi } from "@/api/media";

const props = defineProps({
  schema: {
    type: Array,
    default: () => []
  },
  modelValue: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(["update:modelValue"]);

const state = reactive({});

watch(
  () => props.modelValue,
  (value) => {
    Object.keys(state).forEach((k) => delete state[k]);
    props.schema.forEach((field) => {
      state[field.key] = value?.[field.key] ?? (field.type === "checkbox" ? false : "");
    });
  },
  { immediate: true, deep: true }
);

watch(
  state,
  () => {
    emit("update:modelValue", { ...state });
  },
  { deep: true }
);

const uploadFile = async (field, { file }) => {
  try {
    const uploaded = await mediaApi.upload(file, field.label);
    state[field.key] = uploaded.source_url || uploaded.guid?.rendered || "";
    ElMessage.success("Uploaded");
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Upload failed");
  }
};
</script>

<template>
  <div class="space-y-3">
    <div
      v-for="field in schema"
      :key="field.id"
      class="rounded-xl border border-slate-200 p-3 dark:border-slate-700"
    >
      <label class="mb-1 block text-sm font-medium">
        {{ field.label }}
        <span v-if="field.required" class="text-red-500">*</span>
      </label>

      <el-input
        v-if="field.type === 'text' || field.type === 'email'"
        v-model="state[field.key]"
        :type="field.type"
        :placeholder="field.placeholder"
      />
      <el-input-number
        v-else-if="field.type === 'number'"
        v-model="state[field.key]"
        :placeholder="field.placeholder"
      />
      <el-input
        v-else-if="field.type === 'textarea'"
        v-model="state[field.key]"
        type="textarea"
        :rows="3"
        :placeholder="field.placeholder"
      />
      <el-select v-else-if="field.type === 'select'" v-model="state[field.key]" :placeholder="field.placeholder">
        <el-option v-for="opt in field.options || []" :key="opt" :label="opt" :value="opt" />
      </el-select>
      <el-checkbox v-else-if="field.type === 'checkbox'" v-model="state[field.key]">{{ field.label }}</el-checkbox>
      <el-date-picker
        v-else-if="field.type === 'date'"
        v-model="state[field.key]"
        type="date"
        value-format="YYYY-MM-DD"
      />
      <div v-else-if="field.type === 'file'" class="space-y-2">
        <el-upload :show-file-list="false" :http-request="(args) => uploadFile(field, args)">
          <el-button>Upload</el-button>
        </el-upload>
        <a v-if="state[field.key]" :href="state[field.key]" class="text-xs text-sky-600" target="_blank">
          {{ state[field.key] }}
        </a>
      </div>
    </div>
  </div>
</template>
