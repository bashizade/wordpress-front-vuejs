<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { ElMessage } from "element-plus";
import { mediaApi } from "@/api/wordpress";
import { postCustomFieldsApi } from "@/api/postCustomFields";

const props = defineProps({
  postType: { type: String, required: true },
  postId: { type: [Number, String], default: null },
  title: { type: String, default: "Custom Fields" }
});

const loading = ref(false);
const saving = ref(false);
const fields = ref([]);
const values = reactive({});

const sortedFields = computed(() =>
  [...fields.value].sort((a, b) => (a.order ?? 0) - (b.order ?? 0))
);

const setDefaultValue = (field) => {
  if (Object.prototype.hasOwnProperty.call(values, field.field_key)) {
    return;
  }
  if (field.type === "checkbox") {
    values[field.field_key] = false;
    return;
  }
  if (field.type === "repeatable") {
    values[field.field_key] = [];
    return;
  }
  values[field.field_key] = "";
};

const normalizeIncomingValue = (field, incoming) => {
  if (field.type === "checkbox") {
    return incoming === true || incoming === 1 || incoming === "1";
  }
  if (field.type === "repeatable") {
    if (Array.isArray(incoming)) return incoming;
    if (typeof incoming === "string" && incoming.trim()) {
      try {
        const parsed = JSON.parse(incoming);
        return Array.isArray(parsed) ? parsed : [];
      } catch {
        return [];
      }
    }
    return [];
  }
  return incoming ?? "";
};

const applyMeta = (meta = {}) => {
  sortedFields.value.forEach((field) => {
    const incoming = Object.prototype.hasOwnProperty.call(meta, field.field_key)
      ? meta[field.field_key]
      : undefined;
    values[field.field_key] = normalizeIncomingValue(field, incoming);
  });
};

const load = async () => {
  loading.value = true;
  try {
    fields.value = await postCustomFieldsApi.getFieldsByPostType(props.postType);
    sortedFields.value.forEach((field) => setDefaultValue(field));

    if (props.postId) {
      const meta = await postCustomFieldsApi.getMeta(props.postId);
      applyMeta(meta);
    }
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed to load custom fields");
  } finally {
    loading.value = false;
  }
};

const getMetaPayload = () => {
  const payload = {};
  sortedFields.value.forEach((field) => {
    let value = values[field.field_key];
    if (field.type === "checkbox") {
      value = value ? "1" : "0";
    }
    if (field.type === "number") {
      value = value === "" || value === null ? "" : Number(value);
    }
    if (field.type === "repeatable") {
      value = Array.isArray(value) ? value.filter((item) => String(item ?? "").trim() !== "") : [];
    }
    payload[field.field_key] = value;
  });
  return payload;
};

const saveMeta = async (postId) => {
  const targetId = Number(postId || props.postId);
  if (!targetId) {
    return null;
  }
  saving.value = true;
  try {
    const payload = getMetaPayload();
    const result = await postCustomFieldsApi.saveMeta(targetId, payload);
    ElMessage.success("Custom fields saved");
    return result;
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed to save custom fields");
    throw error;
  } finally {
    saving.value = false;
  }
};

const addRepeatableItem = (fieldKey) => {
  if (!Array.isArray(values[fieldKey])) {
    values[fieldKey] = [];
  }
  values[fieldKey].push("");
};

const removeRepeatableItem = (fieldKey, index) => {
  if (!Array.isArray(values[fieldKey])) {
    return;
  }
  values[fieldKey].splice(index, 1);
};

const uploadImage = async (fieldKey, file) => {
  try {
    const media = await mediaApi.upload(file);
    values[fieldKey] = media.id || media.source_url || "";
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Image upload failed");
  }
};

const resolveImageUrl = (value) => {
  if (!value) {
    return "";
  }
  if (typeof value === "number" || /^\d+$/.test(String(value))) {
    return "";
  }
  return String(value);
};

watch(
  () => props.postId,
  async (next, prev) => {
    if (next && next !== prev) {
      try {
        const meta = await postCustomFieldsApi.getMeta(next);
        applyMeta(meta);
      } catch {
        // no-op
      }
    }
  }
);

onMounted(load);

defineExpose({
  load,
  saveMeta,
  getMetaPayload
});
</script>

<template>
  <div class="panel p-4">
    <div class="mb-3 flex items-center justify-between">
      <h3 class="text-base font-semibold">{{ title }}</h3>
      <el-tag v-if="saving" type="info">Saving...</el-tag>
    </div>

    <el-skeleton v-if="loading" :rows="5" animated />

    <div v-else-if="sortedFields.length === 0" class="text-sm text-slate-500">
      No custom fields assigned to this post type.
    </div>

    <el-form v-else label-position="top">
      <el-form-item
        v-for="field in sortedFields"
        :key="field.field_key"
        :label="field.label"
        :required="field.required"
      >
        <el-input v-if="field.type === 'text'" v-model="values[field.field_key]" />

        <el-input
          v-else-if="field.type === 'textarea'"
          v-model="values[field.field_key]"
          type="textarea"
          :rows="4"
        />

        <el-input-number
          v-else-if="field.type === 'number'"
          v-model="values[field.field_key]"
          :min="field.min ?? undefined"
          :max="field.max ?? undefined"
          controls-position="right"
        />

        <el-select v-else-if="field.type === 'select'" v-model="values[field.field_key]" clearable>
          <el-option
            v-for="option in field.options || []"
            :key="option"
            :label="option"
            :value="option"
          />
        </el-select>

        <el-checkbox v-else-if="field.type === 'checkbox'" v-model="values[field.field_key]">
          Enabled
        </el-checkbox>

        <div v-else-if="field.type === 'image'" class="w-full space-y-2">
          <el-upload
            :show-file-list="false"
            :auto-upload="false"
            accept="image/*"
            :on-change="({ raw }) => uploadImage(field.field_key, raw)"
          >
            <el-button type="primary" plain>Upload Image</el-button>
          </el-upload>
          <el-input v-model="values[field.field_key]" placeholder="Media ID or URL" />
          <el-image
            v-if="resolveImageUrl(values[field.field_key])"
            :src="resolveImageUrl(values[field.field_key])"
            fit="cover"
            class="h-24 w-24 rounded-md border"
          />
        </div>

        <div v-else-if="field.type === 'repeatable'" class="w-full space-y-2">
          <div
            v-for="(item, index) in values[field.field_key]"
            :key="`${field.field_key}_${index}`"
            class="flex items-center gap-2"
          >
            <el-input v-model="values[field.field_key][index]" />
            <el-button type="danger" plain @click="removeRepeatableItem(field.field_key, index)">
              Remove
            </el-button>
          </div>
          <el-button plain @click="addRepeatableItem(field.field_key)">Add item</el-button>
        </div>
      </el-form-item>
    </el-form>
  </div>
</template>