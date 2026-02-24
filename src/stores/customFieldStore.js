import { computed, ref } from "vue";
import { defineStore } from "pinia";
import { ElMessage } from "element-plus";
import i18n from "@/i18n";
import { customFieldsApi } from "@/api/customFields";

const STORAGE_KEY = "user_custom_field_schema_v1";

const defaultSchema = [];

const normalizeKey = (value) =>
  String(value || "")
    .trim()
    .toLowerCase()
    .replace(/[^\w]+/g, "_")
    .replace(/^_+|_+$/g, "");

export const useCustomFieldStore = defineStore("customFieldStore", () => {
  const schema = ref(JSON.parse(localStorage.getItem(STORAGE_KEY) || JSON.stringify(defaultSchema)));

  const sortedSchema = computed(() =>
    [...schema.value].sort((a, b) => (a.order ?? 0) - (b.order ?? 0))
  );

  const persist = () => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(schema.value));
  };

  const fetchSchema = async () => {
    try {
      const remote = await customFieldsApi.getSchema();
      if (Array.isArray(remote)) {
        schema.value = remote.map((item, index) => ({
          ...item,
          id: item.id || Date.now() + index,
          unique: Boolean(item.unique),
          order: item.order ?? index
        }));
        persist();
      }
    } catch {
      schema.value = JSON.parse(localStorage.getItem(STORAGE_KEY) || JSON.stringify(defaultSchema));
    }
    return schema.value;
  };

  const syncSchema = async () => {
    try {
      await customFieldsApi.saveSchema(schema.value);
    } catch {
      // fallback to local storage only
    }
  };

  const addField = (field) => {
    const key = normalizeKey(field.key || field.label);
    if (schema.value.some((item) => item.key === key)) {
      ElMessage.error(i18n.global.t("customFields.fieldKeyExists"));
      return null;
    }
    const next = {
      id: Date.now(),
      label: field.label || "",
      key,
      type: field.type || "text",
      required: Boolean(field.required),
      unique: Boolean(field.unique),
      options: field.options || [],
      placeholder: field.placeholder || "",
      validation: field.validation || "",
      order: schema.value.length
    };
    schema.value.push(next);
    persist();
    syncSchema();
    ElMessage.success(i18n.global.t("customFields.saved"));
    return next;
  };

  const updateField = (id, field) => {
    const idx = schema.value.findIndex((item) => item.id === id);
    if (idx < 0) return;
    const key = normalizeKey(field.key || field.label);
    const hasConflict = schema.value.some((item) => item.id !== id && item.key === key);
    if (hasConflict) {
      ElMessage.error(i18n.global.t("customFields.fieldKeyExists"));
      return;
    }
    schema.value[idx] = {
      ...schema.value[idx],
      ...field,
      key
    };
    persist();
    syncSchema();
    ElMessage.success(i18n.global.t("customFields.saved"));
  };

  const removeField = (id) => {
    schema.value = schema.value.filter((item) => item.id !== id).map((item, index) => ({ ...item, order: index }));
    persist();
    syncSchema();
    ElMessage.success(i18n.global.t("customFields.deleted"));
  };

  const reorderFields = (orderedIds) => {
    const orderMap = new Map(orderedIds.map((id, index) => [id, index]));
    schema.value = schema.value
      .map((item) => ({ ...item, order: orderMap.get(item.id) ?? item.order ?? 0 }))
      .sort((a, b) => a.order - b.order);
    persist();
    syncSchema();
  };

  return {
    schema,
    sortedSchema,
    normalizeKey,
    fetchSchema,
    addField,
    updateField,
    removeField,
    reorderFields
  };
});
