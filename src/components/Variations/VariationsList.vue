<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { ElMessage } from "element-plus";
import VariationEditor from "@/components/Variations/VariationEditor.vue";
import VariationBulkEditor from "@/components/Variations/VariationBulkEditor.vue";
import VariationPriceTable from "@/components/Variations/VariationPriceTable.vue";
import { useVariationStore } from "@/stores/variationStore";
import { useAttributeStore } from "@/stores/attributeStore";

const props = defineProps({
  productId: {
    type: [Number, String],
    required: true
  },
  attributes: {
    type: Array,
    default: () => []
  },
  basePrice: {
    type: [Number, String],
    default: 0
  }
});

const variationStore = useVariationStore();
const attributeStore = useAttributeStore();

const editorOpen = ref(false);
const bulkOpen = ref(false);
const editingVariation = ref(null);
const dragId = ref(null);

const rows = computed(() => variationStore.variations);

const openCreate = () => {
  editingVariation.value = null;
  editorOpen.value = true;
};

const openEdit = (row) => {
  editingVariation.value = row;
  editorOpen.value = true;
};

const saveVariation = async ({ id, payload }) => {
  if (id) {
    await variationStore.updateVariation(id, payload);
  } else {
    await variationStore.createVariation(payload);
  }
};

const removeVariation = async (id) => {
  await variationStore.removeVariation(id);
};

const generateAll = async () => {
  await variationStore.autoGenerateVariations(props.attributes, { manage_stock: false });
};

const applyBulk = async ({ action, value }) => {
  await variationStore.applyBulk(action, { value });
};

const onDragStart = (id) => {
  dragId.value = id;
};

const onDrop = async (targetId) => {
  if (!dragId.value || dragId.value === targetId) return;
  const cloned = [...rows.value];
  const from = cloned.findIndex((item) => item.id === dragId.value);
  const to = cloned.findIndex((item) => item.id === targetId);
  if (from < 0 || to < 0) return;
  const [moved] = cloned.splice(from, 1);
  cloned.splice(to, 0, moved);
  await variationStore.reorderVariations(cloned);
  dragId.value = null;
};

watch(
  () => props.attributes,
  async (attrs) => {
    const ids = attrs.filter((item) => item.id).map((item) => item.id);
    await Promise.all(ids.map((id) => attributeStore.fetchTerms(id)));
  },
  { deep: true, immediate: true }
);

onMounted(async () => {
  await variationStore.loadProductContext(props.productId);
  await variationStore.fetchVariations();
});
</script>

<template>
  <div class="space-y-4">
    <div class="panel sticky top-16 z-10 p-4">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
          <h3 class="font-semibold">Variations Matrix</h3>
          <p class="text-xs text-slate-500">{{ variationStore.variationCount }} variations</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <el-button @click="bulkOpen = true">Bulk Tools</el-button>
          <el-button @click="generateAll">Generate All Combinations</el-button>
          <el-button type="primary" @click="openCreate">Create Variation</el-button>
        </div>
      </div>
    </div>

    <VariationPriceTable :attributes="attributes" :base-price="basePrice" />

    <div class="panel p-4">
      <el-table v-loading="variationStore.loading" :data="rows" row-key="id" stripe>
        <el-table-column label="#" width="80">
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
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column label="Attributes">
          <template #default="{ row }">
            <div class="flex flex-wrap gap-1">
              <el-tag v-for="attr in row.attributes" :key="`${row.id}-${attr.name}`">
                {{ attr.name }}: {{ attr.option }}
              </el-tag>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="sku" label="SKU" width="160" />
        <el-table-column label="Price" width="140">
          <template #default="{ row }">${{ row.regular_price || "0.00" }}</template>
        </el-table-column>
        <el-table-column label="Stock" width="110">
          <template #default="{ row }">{{ row.manage_stock ? row.stock_quantity : "-" }}</template>
        </el-table-column>
        <el-table-column prop="status" label="Status" width="100" />
        <el-table-column label="Actions" width="220">
          <template #default="{ row }">
            <div class="flex gap-2">
              <el-button size="small" @click="openEdit(row)">Edit</el-button>
              <el-popconfirm title="Delete variation?" @confirm="removeVariation(row.id)">
                <template #reference>
                  <el-button size="small" type="danger">Delete</el-button>
                </template>
              </el-popconfirm>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <VariationEditor v-model:open="editorOpen" :variation="editingVariation" :attributes="attributes" @save="saveVariation" />
    <VariationBulkEditor
      v-model:open="bulkOpen"
      @apply="applyBulk"
      @delete-all="variationStore.removeAllVariations().catch(() => ElMessage.error('Delete all failed'))"
    />
  </div>
</template>
