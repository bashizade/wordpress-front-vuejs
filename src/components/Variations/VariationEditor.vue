<script setup>
import { computed, reactive, watch } from "vue";
import VariationImagePicker from "@/components/Variations/VariationImagePicker.vue";

const open = defineModel("open", { type: Boolean, default: false });

const props = defineProps({
  variation: {
    type: Object,
    default: null
  },
  attributes: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(["save"]);

const form = reactive({
  id: null,
  attributes: [],
  sku: "",
  regular_price: "",
  sale_price: "",
  manage_stock: false,
  stock_quantity: null,
  low_stock_amount: null,
  backorders: "no",
  weight: "",
  dimensions: {
    length: "",
    width: "",
    height: ""
  },
  description: "",
  virtual: false,
  downloadable: false,
  status: "publish",
  image: {
    image_id: null,
    gallery_ids: []
  }
});

const variationEnabledAttributes = computed(() => props.attributes.filter((item) => item.variation));

const normalizeAttributes = (source = []) =>
  source.map((item) => ({
    id: item.id || undefined,
    name: item.name,
    option: item.option || ""
  }));

const getAttributeOptionRef = (attribute) => {
  let target = form.attributes.find((item) => item.name === attribute.name);
  if (!target) {
    target = {
      id: attribute.id || undefined,
      name: attribute.name,
      option: ""
    };
    form.attributes.push(target);
  }
  return target;
};

const hydrate = () => {
  const v = props.variation || {};
  form.id = v.id || null;
  form.attributes = normalizeAttributes(v.attributes || variationEnabledAttributes.value.map((item) => ({
    id: item.id,
    name: item.name,
    option: ""
  })));
  form.sku = v.sku || "";
  form.regular_price = v.regular_price || "";
  form.sale_price = v.sale_price || "";
  form.manage_stock = Boolean(v.manage_stock);
  form.stock_quantity = v.stock_quantity;
  form.low_stock_amount = v.low_stock_amount;
  form.backorders = v.backorders || "no";
  form.weight = v.weight || "";
  form.dimensions = {
    length: v.dimensions?.length || "",
    width: v.dimensions?.width || "",
    height: v.dimensions?.height || ""
  };
  form.description = v.description || "";
  form.virtual = Boolean(v.virtual);
  form.downloadable = Boolean(v.downloadable);
  form.status = v.status || "publish";
  form.image = {
    image_id: v.image?.id || null,
    gallery_ids: (v.meta_data || [])
      .find((item) => item.key === "_variation_gallery_image_ids")
      ?.value?.split(",")
      ?.map((id) => Number(id))
      ?.filter(Boolean) || []
  };
};

watch(
  () => [props.variation, open.value, props.attributes],
  () => {
    if (open.value) hydrate();
  },
  { deep: true, immediate: true }
);

const submit = () => {
  const payload = {
    attributes: form.attributes,
    sku: form.sku,
    regular_price: String(form.regular_price || ""),
    sale_price: String(form.sale_price || ""),
    manage_stock: form.manage_stock,
    stock_quantity: form.manage_stock ? Number(form.stock_quantity || 0) : null,
    low_stock_amount: form.manage_stock ? Number(form.low_stock_amount || 0) : null,
    backorders: form.backorders,
    weight: String(form.weight || ""),
    dimensions: form.dimensions,
    description: form.description,
    virtual: form.virtual,
    downloadable: form.downloadable,
    status: form.status,
    image: form.image.image_id ? { id: form.image.image_id } : null,
    meta_data: [
      {
        key: "_variation_gallery_image_ids",
        value: form.image.gallery_ids.join(",")
      }
    ]
  };
  emit("save", {
    id: form.id,
    payload
  });
  open.value = false;
};
</script>

<template>
  <el-dialog v-model="open" :title="form.id ? 'Edit Variation' : 'Create Variation'" width="980">
    <el-form label-position="top">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <el-form-item label="SKU">
          <el-input v-model="form.sku" />
        </el-form-item>
        <el-form-item label="Regular Price">
          <el-input v-model="form.regular_price" />
        </el-form-item>
        <el-form-item label="Sale Price">
          <el-input v-model="form.sale_price" />
        </el-form-item>
      </div>

      <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <el-form-item label="Variation Status">
          <el-select v-model="form.status">
            <el-option label="Publish" value="publish" />
            <el-option label="Private" value="private" />
            <el-option label="Draft" value="draft" />
          </el-select>
        </el-form-item>
        <el-form-item label="Manage Stock">
          <el-switch v-model="form.manage_stock" />
        </el-form-item>
        <el-form-item label="Backorders">
          <el-select v-model="form.backorders">
            <el-option label="Do not allow" value="no" />
            <el-option label="Allow, notify customer" value="notify" />
            <el-option label="Allow" value="yes" />
          </el-select>
        </el-form-item>
      </div>

      <div v-if="form.manage_stock" class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <el-form-item label="Stock Quantity">
          <el-input-number v-model="form.stock_quantity" :min="0" />
        </el-form-item>
        <el-form-item label="Low Stock Threshold">
          <el-input-number v-model="form.low_stock_amount" :min="0" />
        </el-form-item>
      </div>

      <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <el-form-item label="Weight">
          <el-input v-model="form.weight" />
        </el-form-item>
        <el-form-item label="Length">
          <el-input v-model="form.dimensions.length" />
        </el-form-item>
        <el-form-item label="Width">
          <el-input v-model="form.dimensions.width" />
        </el-form-item>
      </div>
      <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <el-form-item label="Height">
          <el-input v-model="form.dimensions.height" />
        </el-form-item>
        <el-form-item label="Virtual">
          <el-switch v-model="form.virtual" />
        </el-form-item>
        <el-form-item label="Downloadable">
          <el-switch v-model="form.downloadable" />
        </el-form-item>
      </div>

      <el-form-item label="Description">
        <el-input v-model="form.description" type="textarea" :rows="3" />
      </el-form-item>

      <div class="rounded border border-slate-200 p-3">
        <h4 class="mb-3 font-semibold">Variation Attributes</h4>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div v-for="attr in variationEnabledAttributes" :key="attr.name">
            <label class="mb-1 block text-xs text-slate-500">{{ attr.name }}</label>
            <el-select
              v-model="getAttributeOptionRef(attr).option"
              filterable
              allow-create
              default-first-option
            >
              <el-option v-for="option in attr.options || []" :key="option" :label="option" :value="option" />
            </el-select>
          </div>
        </div>
      </div>

      <div class="mt-4">
        <VariationImagePicker v-model="form.image" />
      </div>
    </el-form>
    <template #footer>
      <el-button @click="open = false">Cancel</el-button>
      <el-button type="primary" @click="submit">Save Variation</el-button>
    </template>
  </el-dialog>
</template>
