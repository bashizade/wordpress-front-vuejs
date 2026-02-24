<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ElMessage } from "element-plus";
import PageHeader from "@/components/common/PageHeader.vue";
import { useAttributeStore } from "@/stores/attributeStore";

const route = useRoute();
const router = useRouter();
const attributeStore = useAttributeStore();
const attribute = ref(null);
const dialogOpen = ref(false);
const editingTermId = ref(null);
const draggingId = ref(null);

const form = reactive({
  name: "",
  slug: "",
  description: "",
  custom_label: "",
  color: "#2563eb",
  price_modifier: 0
});

const terms = computed(() => attributeStore.terms);

const getMeta = (termId) => attributeStore.getTermMeta(route.params.id, termId);

const load = async () => {
  attribute.value = await attributeStore.fetchAttribute(route.params.id);
  await attributeStore.fetchTerms(route.params.id);
};

const openCreate = () => {
  editingTermId.value = null;
  form.name = "";
  form.slug = "";
  form.description = "";
  form.custom_label = "";
  form.color = "#2563eb";
  form.price_modifier = 0;
  dialogOpen.value = true;
};

const openEdit = (row) => {
  const meta = getMeta(row.id);
  editingTermId.value = row.id;
  form.name = row.name;
  form.slug = row.slug;
  form.description = row.description || "";
  form.custom_label = meta.custom_label || "";
  form.color = meta.color || "#2563eb";
  form.price_modifier = meta.price_modifier || 0;
  dialogOpen.value = true;
};

const save = async () => {
  try {
    const payload = {
      name: form.name,
      slug: form.slug || attributeStore.slugify(form.name),
      description: form.description
    };

    if (editingTermId.value) {
      await attributeStore.updateTerm(route.params.id, editingTermId.value, payload);
      attributeStore.setTermMeta(route.params.id, editingTermId.value, {
        custom_label: form.custom_label,
        color: form.color,
        price_modifier: Number(form.price_modifier) || 0
      });
    } else {
      const created = await attributeStore.createTerm(route.params.id, payload);
      attributeStore.setTermMeta(route.params.id, created.id, {
        custom_label: form.custom_label,
        color: form.color,
        price_modifier: Number(form.price_modifier) || 0
      });
    }
    dialogOpen.value = false;
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed saving term");
  }
};

const remove = async (id) => {
  await attributeStore.removeTerm(route.params.id, id);
};

const onDragStart = (id) => {
  draggingId.value = id;
};

const onDrop = async (targetId) => {
  if (!draggingId.value || draggingId.value === targetId) return;
  const cloned = [...terms.value];
  const fromIndex = cloned.findIndex((item) => item.id === draggingId.value);
  const toIndex = cloned.findIndex((item) => item.id === targetId);
  if (fromIndex < 0 || toIndex < 0) return;
  const [moved] = cloned.splice(fromIndex, 1);
  cloned.splice(toIndex, 0, moved);
  await attributeStore.reorderTerms(route.params.id, cloned);
  draggingId.value = null;
};

onMounted(load);
</script>

<template>
  <div>
    <PageHeader
      :title="`Attribute Terms: ${attribute?.name || ''}`"
      subtitle="Terms + custom label + color + price modifiers"
    >
      <div class="flex gap-2">
        <el-button @click="router.push({ name: 'attributes.list' })">Back</el-button>
        <el-button type="primary" @click="openCreate">Add Term</el-button>
      </div>
    </PageHeader>

    <div class="panel p-4">
      <el-alert type="info" :closable="false" class="mb-4">
        Drag rows by handle to reorder terms (menu order).
      </el-alert>

      <el-table :data="terms" row-key="id" stripe>
        <el-table-column label="#" width="70">
          <template #default="{ row }">
            <div
              class="cursor-move rounded border border-slate-300 px-2 py-1 text-center text-xs"
              draggable="true"
              @dragstart="onDragStart(row.id)"
              @dragover.prevent
              @drop.prevent="onDrop(row.id)"
            >
              Drag
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="name" label="Name" />
        <el-table-column prop="slug" label="Slug" />
        <el-table-column label="Custom Label" width="160">
          <template #default="{ row }">
            {{ getMeta(row.id).custom_label || "-" }}
          </template>
        </el-table-column>
        <el-table-column label="Color" width="120">
          <template #default="{ row }">
            <div class="flex items-center gap-2">
              <span
                class="inline-block h-4 w-4 rounded-full border border-slate-300"
                :style="{ backgroundColor: getMeta(row.id).color || '#2563eb' }"
              />
              <span class="text-xs">{{ getMeta(row.id).color || "#2563eb" }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="Price Modifier %" width="140">
          <template #default="{ row }">
            {{ getMeta(row.id).price_modifier || 0 }}%
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="220">
          <template #default="{ row }">
            <div class="flex gap-2">
              <el-button size="small" @click="openEdit(row)">Edit</el-button>
              <el-popconfirm title="Delete term?" @confirm="remove(row.id)">
                <template #reference>
                  <el-button size="small" type="danger">Delete</el-button>
                </template>
              </el-popconfirm>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-dialog v-model="dialogOpen" :title="editingTermId ? 'Edit Term' : 'Create Term'" width="560">
      <el-form label-position="top">
        <el-form-item label="Name">
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item label="Slug">
          <el-input v-model="form.slug" />
        </el-form-item>
        <el-form-item label="Description">
          <el-input v-model="form.description" type="textarea" :rows="3" />
        </el-form-item>
        <el-form-item label="Custom Label">
          <el-input v-model="form.custom_label" placeholder="e.g. Extra Large" />
        </el-form-item>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <el-form-item label="Color">
            <el-color-picker v-model="form.color" />
          </el-form-item>
          <el-form-item label="Price Modifier %">
            <el-input-number v-model="form.price_modifier" :step="1" />
          </el-form-item>
        </div>
      </el-form>
      <template #footer>
        <el-button @click="dialogOpen = false">Cancel</el-button>
        <el-button type="primary" @click="save">Save</el-button>
      </template>
    </el-dialog>
  </div>
</template>
