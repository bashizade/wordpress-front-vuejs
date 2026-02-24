<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { ElMessage } from "element-plus";
import { useAttributeStore } from "@/stores/attributeStore";
import { useProductStore } from "@/stores/productStore";

const props = defineProps({
  productId: {
    type: [Number, String],
    required: true
  },
  modelValue: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(["update:modelValue", "saved"]);

const attributeStore = useAttributeStore();
const productStore = useProductStore();

const rows = ref([]);
const termCache = reactive({});
const dragging = ref(null);

const globalAttributeOptions = computed(() => attributeStore.sortedAttributes);
const nextId = () =>
  (typeof crypto !== "undefined" && crypto.randomUUID
    ? crypto.randomUUID()
    : `${Date.now()}-${Math.random().toString(16).slice(2)}`);

const defaultRow = () => ({
  temp_id: nextId(),
  id: 0,
  name: "",
  position: rows.value.length,
  visible: true,
  variation: true,
  options: [],
  is_global: false
});

const hydrate = () => {
  rows.value = (props.modelValue || []).map((item, index) => ({
    temp_id: nextId(),
    id: item.id || 0,
    name: item.name || "",
    position: item.position ?? index,
    visible: item.visible ?? true,
    variation: item.variation ?? false,
    options: item.options || [],
    is_global: Boolean(item.id)
  }));
  if (!rows.value.length) rows.value.push(defaultRow());
};

const ensureTermsLoaded = async (attributeId) => {
  if (!attributeId || termCache[attributeId]) return;
  termCache[attributeId] = await attributeStore.fetchTerms(attributeId);
};

const onPickGlobalAttribute = async (row) => {
  const selected = globalAttributeOptions.value.find((item) => item.id === row.id);
  if (!selected) return;
  row.name = selected.name;
  row.is_global = true;
  await ensureTermsLoaded(row.id);
  row.options = [];
};

const addRow = () => rows.value.push(defaultRow());

const removeRow = (tempId) => {
  rows.value = rows.value.filter((item) => item.temp_id !== tempId);
  if (!rows.value.length) addRow();
};

const onDragStart = (tempId) => {
  dragging.value = tempId;
};

const onDrop = (targetId) => {
  if (!dragging.value || dragging.value === targetId) return;
  const cloned = [...rows.value];
  const from = cloned.findIndex((item) => item.temp_id === dragging.value);
  const to = cloned.findIndex((item) => item.temp_id === targetId);
  if (from < 0 || to < 0) return;
  const [moved] = cloned.splice(from, 1);
  cloned.splice(to, 0, moved);
  rows.value = cloned.map((item, index) => ({ ...item, position: index }));
  dragging.value = null;
};

const save = async () => {
  try {
    const payload = rows.value
      .filter((item) => item.name && item.options.length > 0)
      .map((item, index) => ({
        id: item.id || 0,
        name: item.name,
        position: index,
        visible: item.visible,
        variation: item.variation,
        options: item.options
      }));
    const product = await productStore.updateProductAttributes(Number(props.productId), payload);
    emit("update:modelValue", product.attributes || payload);
    emit("saved", product.attributes || payload);
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed saving product attributes");
  }
};

onMounted(async () => {
  await attributeStore.fetchAttributes();
  hydrate();
  await Promise.all(rows.value.filter((item) => item.id).map((item) => ensureTermsLoaded(item.id)));
});

watch(
  () => props.modelValue,
  () => {
    hydrate();
  },
  { deep: true }
);
</script>

<template>
  <div class="panel p-4">
    <div class="mb-3 flex items-center justify-between">
      <h3 class="font-semibold">Product Attributes</h3>
      <div class="flex gap-2">
        <el-button @click="addRow">Add Attribute</el-button>
        <el-button type="primary" @click="save">Save Attributes</el-button>
      </div>
    </div>

    <div class="space-y-3">
      <div
        v-for="row in rows"
        :key="row.temp_id"
        class="rounded-xl border border-slate-200 p-3"
        @dragover.prevent
        @drop.prevent="onDrop(row.temp_id)"
      >
        <div class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-5">
          <div>
            <label class="mb-1 block text-xs text-slate-500">Drag</label>
            <div
              class="cursor-move rounded border border-slate-300 px-3 py-2 text-center text-xs"
              draggable="true"
              @dragstart="onDragStart(row.temp_id)"
            >
              Move
            </div>
          </div>
          <div>
            <label class="mb-1 block text-xs text-slate-500">Global Attribute</label>
            <el-select v-model="row.id" clearable @change="onPickGlobalAttribute(row)">
              <el-option label="Local / Custom attribute" :value="0" />
              <el-option
                v-for="item in globalAttributeOptions"
                :key="item.id"
                :label="item.name"
                :value="item.id"
              />
            </el-select>
          </div>
          <div>
            <label class="mb-1 block text-xs text-slate-500">Name</label>
            <el-input v-model="row.name" />
          </div>
          <div class="flex items-end gap-2">
            <el-checkbox v-model="row.variation">Used for variations</el-checkbox>
          </div>
          <div class="flex items-end gap-2">
            <el-checkbox v-model="row.visible">Visible on product page</el-checkbox>
            <el-button type="danger" plain @click="removeRow(row.temp_id)">Remove</el-button>
          </div>
        </div>

        <div>
          <label class="mb-2 block text-xs text-slate-500">Terms / Options</label>
          <el-select
            v-if="row.id"
            v-model="row.options"
            multiple
            filterable
            collapse-tags
            collapse-tags-tooltip
            placeholder="Choose terms"
          >
            <el-option
              v-for="term in termCache[row.id] || []"
              :key="term.id"
              :label="term.name"
              :value="term.name"
            />
          </el-select>
          <el-select
            v-else
            v-model="row.options"
            multiple
            filterable
            allow-create
            default-first-option
            placeholder="Type custom options and press enter"
          />
        </div>
      </div>
    </div>
  </div>
</template>
