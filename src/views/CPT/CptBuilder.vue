<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import PageHeader from "@/components/common/PageHeader.vue";
import { customCptApi } from "@/api/customCpt";

const loading = ref(false);
const saving = ref(false);
const definitions = ref([]);
const taxonomies = ref([]);
const dialogOpen = ref(false);
const editingSlug = ref("");
const draggingSlug = ref("");

const supportOptions = [
  "title",
  "editor",
  "thumbnail",
  "excerpt",
  "custom-fields",
  "revisions",
  "author",
  "comments",
  "page-attributes"
];

const form = reactive({
  slug: "",
  singular: "",
  plural: "",
  icon: "Document",
  supports: ["title", "editor"],
  public: true,
  show_in_rest: true,
  has_archive: true,
  hierarchical: false,
  menu_position: 25,
  taxonomies: []
});

const sortedDefinitions = computed(() =>
  [...definitions.value].sort((a, b) => (a.order ?? 0) - (b.order ?? 0))
);

const resetForm = () => {
  form.slug = "";
  form.singular = "";
  form.plural = "";
  form.icon = "Document";
  form.supports = ["title", "editor"];
  form.public = true;
  form.show_in_rest = true;
  form.has_archive = true;
  form.hierarchical = false;
  form.menu_position = 25;
  form.taxonomies = [];
};

const normalizeSlug = (value) =>
  String(value || "")
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9_]+/g, "_")
    .replace(/^_+|_+$/g, "")
    .slice(0, 20);

const load = async () => {
  loading.value = true;
  try {
    const [items, taxonomyMap] = await Promise.all([customCptApi.list(), customCptApi.getTaxonomies()]);
    definitions.value = items;
    taxonomies.value = Object.values(taxonomyMap || {}).map((item) => ({
      value: item.slug,
      label: item.name
    }));
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed to load CPT definitions");
  } finally {
    loading.value = false;
  }
};

const openCreate = () => {
  editingSlug.value = "";
  resetForm();
  dialogOpen.value = true;
};

const openEdit = (item) => {
  editingSlug.value = item.slug;
  form.slug = item.slug;
  form.singular = item.singular;
  form.plural = item.plural;
  form.icon = item.icon || "Document";
  form.supports = Array.isArray(item.supports) && item.supports.length ? [...item.supports] : ["title", "editor"];
  form.public = Boolean(item.public);
  form.show_in_rest = item.show_in_rest !== false;
  form.has_archive = Boolean(item.has_archive);
  form.hierarchical = Boolean(item.hierarchical);
  form.menu_position = Number(item.menu_position ?? 25);
  form.taxonomies = Array.isArray(item.taxonomies) ? [...item.taxonomies] : [];
  dialogOpen.value = true;
};

const submit = async () => {
  const payload = {
    slug: normalizeSlug(form.slug),
    singular: form.singular.trim(),
    plural: form.plural.trim(),
    icon: form.icon.trim() || "Document",
    supports: form.supports,
    public: form.public,
    show_in_rest: form.show_in_rest,
    has_archive: form.has_archive,
    hierarchical: form.hierarchical,
    menu_position: Number(form.menu_position || 25),
    taxonomies: form.taxonomies
  };

  if (!payload.slug || !payload.singular || !payload.plural) {
    ElMessage.error("Slug, singular and plural labels are required");
    return;
  }

  saving.value = true;
  try {
    if (editingSlug.value) {
      await customCptApi.update(editingSlug.value, payload);
      ElMessage.success("CPT updated");
    } else {
      await customCptApi.create(payload);
      ElMessage.success("CPT created");
    }
    dialogOpen.value = false;
    await load();
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed to save CPT");
  } finally {
    saving.value = false;
  }
};

const remove = async (item) => {
  try {
    await ElMessageBox.confirm(`Delete ${item.plural}?`, "Confirm", { type: "warning" });
    await customCptApi.remove(item.slug);
    ElMessage.success("CPT deleted");
    await load();
  } catch {
    // canceled
  }
};

const onDragStart = (slug) => {
  draggingSlug.value = slug;
};

const onDrop = async (targetSlug) => {
  if (!draggingSlug.value || draggingSlug.value === targetSlug) {
    return;
  }
  const order = sortedDefinitions.value.map((item) => item.slug);
  const from = order.indexOf(draggingSlug.value);
  const to = order.indexOf(targetSlug);
  if (from < 0 || to < 0) {
    return;
  }
  const [moved] = order.splice(from, 1);
  order.splice(to, 0, moved);

  saving.value = true;
  try {
    definitions.value = await customCptApi.reorder(order);
    ElMessage.success("CPT order saved");
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed to reorder CPTs");
  } finally {
    saving.value = false;
    draggingSlug.value = "";
  }
};

onMounted(load);
</script>

<template>
  <div>
    <PageHeader title="CPT Builder" subtitle="Create and manage dynamic custom post types">
      <el-button type="primary" @click="openCreate">Create CPT</el-button>
    </PageHeader>

    <div class="panel p-4">
      <el-table v-loading="loading" :data="sortedDefinitions" row-key="slug" stripe>
        <el-table-column label="#" width="70">
          <template #default="{ row }">
            <div
              class="cursor-move rounded border border-slate-300 px-2 py-1 text-center text-xs"
              draggable="true"
              @dragstart="onDragStart(row.slug)"
              @dragover.prevent
              @drop.prevent="onDrop(row.slug)"
            >
              Drag
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="slug" label="Slug" width="140" />
        <el-table-column prop="plural" label="Plural Label" min-width="200" />
        <el-table-column prop="singular" label="Singular Label" min-width="200" />
        <el-table-column label="REST" width="100">
          <template #default="{ row }">{{ row.show_in_rest ? "On" : "Off" }}</template>
        </el-table-column>
        <el-table-column label="Public" width="100">
          <template #default="{ row }">{{ row.public ? "Yes" : "No" }}</template>
        </el-table-column>
        <el-table-column label="Actions" width="220" fixed="right">
          <template #default="{ row }">
            <div class="flex gap-2">
              <el-button size="small" @click="openEdit(row)">Edit</el-button>
              <el-button size="small" type="danger" @click="remove(row)">Delete</el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-dialog v-model="dialogOpen" :title="editingSlug ? 'Edit CPT' : 'Create CPT'" width="860px">
      <el-form label-position="top">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
          <el-form-item label="Slug" required>
            <el-input v-model="form.slug" placeholder="portfolio" :disabled="Boolean(editingSlug)" />
          </el-form-item>
          <el-form-item label="Singular Label" required>
            <el-input v-model="form.singular" placeholder="Portfolio Item" />
          </el-form-item>
          <el-form-item label="Plural Label" required>
            <el-input v-model="form.plural" placeholder="Portfolio" />
          </el-form-item>
          <el-form-item label="Icon">
            <el-input v-model="form.icon" placeholder="Document or dashicons-admin-post" />
          </el-form-item>
          <el-form-item label="Menu Position">
            <el-input-number v-model="form.menu_position" :min="1" :max="100" controls-position="right" />
          </el-form-item>
          <el-form-item label="Taxonomies">
            <el-select v-model="form.taxonomies" multiple filterable>
              <el-option v-for="item in taxonomies" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </div>

        <el-form-item label="Supports">
          <el-checkbox-group v-model="form.supports">
            <el-checkbox v-for="support in supportOptions" :key="support" :label="support">{{ support }}</el-checkbox>
          </el-checkbox-group>
        </el-form-item>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <el-form-item>
            <el-switch v-model="form.public" active-text="Public" inactive-text="Private" />
          </el-form-item>
          <el-form-item>
            <el-switch v-model="form.show_in_rest" active-text="REST Enabled" inactive-text="REST Disabled" />
          </el-form-item>
          <el-form-item>
            <el-switch v-model="form.has_archive" active-text="Has Archive" inactive-text="No Archive" />
          </el-form-item>
          <el-form-item>
            <el-switch v-model="form.hierarchical" active-text="Hierarchical" inactive-text="Flat" />
          </el-form-item>
        </div>
      </el-form>

      <template #footer>
        <div class="flex justify-end gap-2">
          <el-button @click="dialogOpen = false">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="submit">Save</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>
