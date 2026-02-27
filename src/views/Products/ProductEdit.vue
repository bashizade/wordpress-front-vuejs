<script setup>
import { onMounted, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ElMessage } from "element-plus";
import PageHeader from "@/components/common/PageHeader.vue";
import { productApi, productCategoryApi } from "@/api/woocommerce";
import ProductAttributes from "@/components/Variations/ProductAttributes.vue";
import VariationsList from "@/components/Variations/VariationsList.vue";
import ProductEditorDynamicFields from "@/components/CustomFields/ProductEditorDynamicFields.vue";

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const saving = ref(false);
const categories = ref([]);
const activeTab = ref("general");
const productAttributes = ref([]);
const metaRef = ref(null);

const form = reactive({
  name: "",
  type: "simple",
  regular_price: "0",
  description: "",
  short_description: "",
  status: "draft",
  categories: []
});

const load = async () => {
  loading.value = true;
  try {
    const [product, categoryList] = await Promise.all([
      productApi.get(route.params.id),
      productCategoryApi.list({ per_page: 100 })
    ]);
    categories.value = categoryList;
    form.name = product.name;
    form.type = product.type;
    form.regular_price = product.regular_price || "0";
    form.description = product.description || "";
    form.short_description = product.short_description || "";
    form.status = product.status || "draft";
    form.categories = (product.categories || []).map((item) => item.id);
    productAttributes.value = product.attributes || [];
  } finally {
    loading.value = false;
  }
};

const save = async () => {
  saving.value = true;
  try {
    await productApi.update(route.params.id, {
      ...form,
      categories: form.categories.map((id) => ({ id }))
    });
    await metaRef.value?.saveMeta?.(route.params.id);
    ElMessage.success("Product updated");
    router.push({ name: "products.list" });
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Update failed");
  } finally {
    saving.value = false;
  }
};

onMounted(load);
onMounted(() => {
  if (route.query.tab && ["general", "attributes", "variations"].includes(route.query.tab)) {
    activeTab.value = route.query.tab;
  }
});
</script>

<template>
  <div>
    <PageHeader title="Edit Product" subtitle="Update WooCommerce product" />
    <div v-if="loading" class="panel p-4"><el-skeleton :rows="8" animated /></div>
    <div v-else class="panel p-4">
      <el-tabs v-model="activeTab">
        <el-tab-pane label="General" name="general">
          <el-form label-position="top">
            <el-form-item label="Name">
              <el-input v-model="form.name" />
            </el-form-item>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
              <el-form-item label="Type">
                <el-select v-model="form.type">
                  <el-option label="Simple" value="simple" />
                  <el-option label="Variable" value="variable" />
                </el-select>
              </el-form-item>
              <el-form-item label="Price">
                <el-input v-model="form.regular_price" />
              </el-form-item>
              <el-form-item label="Status">
                <el-select v-model="form.status">
                  <el-option label="Draft" value="draft" />
                  <el-option label="Publish" value="publish" />
                </el-select>
              </el-form-item>
            </div>
            <el-form-item label="Categories">
              <el-select v-model="form.categories" multiple filterable>
                <el-option
                  v-for="category in categories"
                  :key="category.id"
                  :label="category.name"
                  :value="category.id"
                />
              </el-select>
            </el-form-item>
            <el-form-item label="Short Description">
              <el-input v-model="form.short_description" type="textarea" :rows="3" />
            </el-form-item>
            <el-form-item label="Description">
              <el-input v-model="form.description" type="textarea" :rows="8" />
            </el-form-item>
            <ProductEditorDynamicFields ref="metaRef" :product-id="route.params.id" />
          </el-form>
          <div class="mt-2 flex justify-end gap-2">
            <el-button @click="router.push({ name: 'products.list' })">Cancel</el-button>
            <el-button type="primary" :loading="saving" @click="save">Update</el-button>
          </div>
        </el-tab-pane>

        <el-tab-pane label="Attributes" name="attributes">
          <ProductAttributes v-model="productAttributes" :product-id="route.params.id" />
        </el-tab-pane>

        <el-tab-pane label="Variations" name="variations">
          <el-alert
            v-if="form.type !== 'variable'"
            :closable="false"
            type="warning"
            class="mb-3"
            title="Set product type to Variable first, then save General tab."
          />
          <VariationsList
            v-else
            :product-id="route.params.id"
            :attributes="productAttributes"
            :base-price="form.regular_price"
          />
        </el-tab-pane>
      </el-tabs>
    </div>
  </div>
</template>
