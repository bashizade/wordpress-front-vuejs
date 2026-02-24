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
const values = reactive({});

const initialize = (source = {}) => {
  Object.keys(values).forEach((key) => delete values[key]);
  props.schema.forEach((field) => {
    values[field.key] = source[field.key] ?? (field.type === "checkbox" ? false : "");
  });
};

watch(
  () => [props.modelValue, props.schema],
  () => {
    initialize(props.modelValue || {});
  },
  { immediate: true, deep: true }
);

watch(
  values,
  () => {
    emit("update:modelValue", { ...values });
  },
  { deep: true }
);

const onUpload = async (field, { file }) => {
  try {
    const uploaded = await mediaApi.upload(file, field.label);
    values[field.key] = uploaded.source_url || uploaded.guid?.rendered || "";
    ElMessage.success("آپلود انجام شد");
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "خطا در آپلود");
  }
};
</script>

<template>
  <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
    <div
      v-for="field in schema"
      :key="field.key"
      class="rounded-xl border border-slate-200 p-3 dark:border-slate-700"
    >
      <label class="mb-1 block text-sm">
        {{ field.label }}
        <span v-if="field.required" class="text-red-500">*</span>
      </label>

      <el-input
        v-if="field.type === 'text' || field.type === 'email'"
        v-model="values[field.key]"
        :type="field.type"
        :placeholder="field.placeholder"
      />
      <el-input-number v-else-if="field.type === 'number'" v-model="values[field.key]" class="w-full" />
      <el-input v-else-if="field.type === 'textarea'" v-model="values[field.key]" type="textarea" :rows="3" />
      <el-select v-else-if="field.type === 'select'" v-model="values[field.key]" class="w-full">
        <el-option v-for="option in field.options || []" :key="option" :label="option" :value="option" />
      </el-select>
      <el-checkbox v-else-if="field.type === 'checkbox'" v-model="values[field.key]">{{ field.label }}</el-checkbox>
      <el-date-picker
        v-else-if="field.type === 'date'"
        v-model="values[field.key]"
        type="date"
        value-format="YYYY-MM-DD"
        class="w-full"
      />
      <div v-else-if="field.type === 'file'" class="space-y-2">
        <el-upload :show-file-list="false" :http-request="(args) => onUpload(field, args)">
          <el-button>آپلود فایل</el-button>
        </el-upload>
        <a v-if="values[field.key]" :href="values[field.key]" target="_blank" class="text-xs text-sky-600">
          {{ values[field.key] }}
        </a>
      </div>
    </div>
  </div>
</template>
