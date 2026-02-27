<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import PageHeader from "@/components/common/PageHeader.vue";
import { postCustomFieldsApi } from "@/api/postCustomFields";

const loading = ref(false);
const saving = ref(false);
const postTypes = ref([]);
const fields = ref([]);
const dialogOpen = ref(false);
const editingIndex = ref(-1);

const typeOptions = ["text", "textarea", "number", "select", "checkbox", "image", "repeatable"];
const repeatableTypeOptions = ["text", "number", "textarea", "select"];

const form = reactive({
  field_key: "",
  label: "",
  type: "text",
  post_types: [],
  required: false,
  optionsText: "",
  min: null,
  max: null,
  order: 0,
  repeatable_type: "text"
});

const normalizedFields = computed(() =>
  [...fields.value].sort((a, b) => (a.order ?? 0) - (b.order ?? 0))
);

const resetForm = () => {
  form.field_key = "";
  form.label = "";
  form.type = "text";
  form.post_types = [];
  form.required = false;
  form.optionsText = "";
  form.min = null;
  form.max = null;
  form.order = fields.value.length;
  form.repeatable_type = "text";
};

const normalizeKey = (value) =>
  String(value || "")
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9_]+/g, "_")
    .replace(/^_+|_+$/g, "");

const load = async () => {
  loading.value = true;
  try {
    const [loadedFields, loadedTypes] = await Promise.all([
      postCustomFieldsApi.getAllFields(),
      postCustomFieldsApi.getPostTypes()
    ]);
    fields.value = Array.isArray(loadedFields) ? loadedFields : [];
    postTypes.value = Array.isArray(loadedTypes) ? loadedTypes : [];
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed to load field definitions");
  } finally {
    loading.value = false;
  }
};

const openCreate = () => {
  editingIndex.value = -1;
  resetForm();
  dialogOpen.value = true;
};

const openEdit = (row) => {
  editingIndex.value = fields.value.findIndex((item) => item.field_key === row.field_key);
  form.field_key = row.field_key;
  form.label = row.label;
  form.type = row.type;
  form.post_types = Array.isArray(row.post_types) ? [...row.post_types] : [];
  form.required = Boolean(row.required);
  form.optionsText = Array.isArray(row.options) ? row.options.join(",") : "";
  form.min = row.min ?? null;
  form.max = row.max ?? null;
  form.order = row.order ?? 0;
  form.repeatable_type = row.repeatable_type || "text";
  dialogOpen.value = true;
};

const removeField = async (row) => {
  try {
    await ElMessageBox.confirm(`Delete field ${row.field_key}?`, "Confirm", { type: "warning" });
    fields.value = fields.value.filter((item) => item.field_key !== row.field_key);
    await saveAll();
  } catch {
    // canceled
  }
};

const submitField = async () => {
  const fieldKey = normalizeKey(form.field_key || form.label);
  if (!fieldKey || !form.label || !form.post_types.length) {
    ElMessage.error("Field key, label and post types are required");
    return;
  }

  const next = {
    field_key: fieldKey,
    label: form.label.trim(),
    type: form.type,
    post_types: [...form.post_types],
    required: Boolean(form.required),
    options: form.optionsText
      .split(",")
      .map((item) => item.trim())
      .filter(Boolean),
    min: form.min === null || form.min === "" ? null : Number(form.min),
    max: form.max === null || form.max === "" ? null : Number(form.max),
    order: Number(form.order) || 0,
    repeatable_type: form.repeatable_type
  };

  const duplicate = fields.value.find(
    (item, idx) => item.field_key === fieldKey && idx !== editingIndex.value
  );
  if (duplicate) {
    ElMessage.error("Field key already exists");
    return;
  }

  if (editingIndex.value >= 0) {
    fields.value.splice(editingIndex.value, 1, next);
  } else {
    fields.value.push(next);
  }

  dialogOpen.value = false;
  await saveAll();
};

const saveAll = async () => {
  saving.value = true;
  try {
    fields.value = await postCustomFieldsApi.saveAllFields(fields.value);
    ElMessage.success("Field schema saved");
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed to save schema");
    throw error;
  } finally {
    saving.value = false;
  }
};

const moveField = async (row, direction) => {
  const list = [...normalizedFields.value];
  const from = list.findIndex((item) => item.field_key === row.field_key);
  const to = from + direction;
  if (from < 0 || to < 0 || to >= list.length) {
    return;
  }
  const [moved] = list.splice(from, 1);
  list.splice(to, 0, moved);
  fields.value = list.map((item, index) => ({ ...item, order: index }));
  await saveAll();
};

onMounted(load);
</script>

<template>
  <div>
    <PageHeader title="Post Type Custom Fields" subtitle="Define dynamic meta fields for any post type">
      <el-button type="primary" @click="openCreate">Add Field</el-button>
    </PageHeader>

    <div class="panel p-4">
      <el-skeleton v-if="loading" :rows="6" animated />

      <el-table v-else :data="normalizedFields" row-key="field_key" stripe>
        <el-table-column label="#" width="70">
          <template #default="{ $index }">{{ $index + 1 }}</template>
        </el-table-column>
        <el-table-column prop="field_key" label="Field Key" min-width="170" />
        <el-table-column prop="label" label="Label" min-width="170" />
        <el-table-column prop="type" label="Type" width="120" />
        <el-table-column label="Post Types" min-width="220">
          <template #default="{ row }">
            <el-tag v-for="type in row.post_types || []" :key="`${row.field_key}_${type}`" class="mr-1 mb-1">
              {{ type }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Required" width="100">
          <template #default="{ row }">{{ row.required ? "Yes" : "No" }}</template>
        </el-table-column>
        <el-table-column label="Actions" width="300" fixed="right">
          <template #default="{ row }">
            <div class="flex gap-2">
              <el-button size="small" @click="moveField(row, -1)">Up</el-button>
              <el-button size="small" @click="moveField(row, 1)">Down</el-button>
              <el-button size="small" @click="openEdit(row)">Edit</el-button>
              <el-button size="small" type="danger" @click="removeField(row)">Delete</el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-dialog v-model="dialogOpen" :title="editingIndex >= 0 ? 'Edit Field' : 'Add Field'" width="760px">
      <el-form label-position="top">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <el-form-item label="Field Key" required>
            <el-input v-model="form.field_key" placeholder="product_color" />
          </el-form-item>
          <el-form-item label="Label" required>
            <el-input v-model="form.label" placeholder="Product Color" />
          </el-form-item>
          <el-form-item label="Type">
            <el-select v-model="form.type">
              <el-option v-for="type in typeOptions" :key="type" :label="type" :value="type" />
            </el-select>
          </el-form-item>
          <el-form-item label="Post Types" required>
            <el-select v-model="form.post_types" multiple filterable>
              <el-option
                v-for="postType in postTypes"
                :key="postType.value"
                :label="postType.label"
                :value="postType.value"
              />
            </el-select>
          </el-form-item>
          <el-form-item v-if="form.type === 'number'" label="Min">
            <el-input-number v-model="form.min" :step="1" controls-position="right" />
          </el-form-item>
          <el-form-item v-if="form.type === 'number'" label="Max">
            <el-input-number v-model="form.max" :step="1" controls-position="right" />
          </el-form-item>
          <el-form-item v-if="form.type === 'select' || form.type === 'repeatable'" label="Options (comma separated)">
            <el-input v-model="form.optionsText" placeholder="red,green,blue" />
          </el-form-item>
          <el-form-item v-if="form.type === 'repeatable'" label="Repeatable Item Type">
            <el-select v-model="form.repeatable_type">
              <el-option
                v-for="itemType in repeatableTypeOptions"
                :key="itemType"
                :label="itemType"
                :value="itemType"
              />
            </el-select>
          </el-form-item>
          <el-form-item label="Order">
            <el-input-number v-model="form.order" :min="0" :step="1" controls-position="right" />
          </el-form-item>
        </div>

        <el-form-item>
          <el-checkbox v-model="form.required">Required</el-checkbox>
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="flex justify-end gap-2">
          <el-button @click="dialogOpen = false">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="submitField">Save Field</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>