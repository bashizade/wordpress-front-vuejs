<script setup>
import { onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { ElMessage } from "element-plus";
import PageHeader from "@/components/common/PageHeader.vue";
import { useProductStore } from "@/stores/productStore";
import { productCategoryApi } from "@/api/woocommerce";

const router = useRouter();
const productStore = useProductStore();
const saving = ref(false);
const categories = ref([]);

const form = reactive({
  name: "",
  type: "simple",
  regular_price: "0",
  description: "",
  short_description: "",
  status: "draft",
  manage_stock: false,
  stock_quantity: 0,
  categories: []
});

const loadCategories = async () => {
  categories.value = await productCategoryApi.list({ per_page: 100 });
};

const save = async () => {
  saving.value = true;
  try {
    await productStore.createProduct({
      ...form,
      categories: form.categories.map((id) => ({ id }))
    });
    router.push({ name: "products.list" });
  } catch {
    ElMessage.error("Product create failed");
  } finally {
    saving.value = false;
  }
};

onMounted(loadCategories);
</script>

<template>
  <div>
    <PageHeader title="Create Product" subtitle="Add a WooCommerce product" />
    <div class="panel p-4">
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
            <el-option v-for="category in categories" :key="category.id" :label="category.name" :value="category.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="Short Description">
          <el-input v-model="form.short_description" type="textarea" :rows="3" />
        </el-form-item>
        <el-form-item label="Description">
          <el-input v-model="form.description" type="textarea" :rows="8" />
        </el-form-item>
      </el-form>
      <div class="mt-2 flex justify-end gap-2">
        <el-button @click="router.push({ name: 'products.list' })">Cancel</el-button>
        <el-button type="primary" :loading="saving" @click="save">Create</el-button>
      </div>
    </div>
  </div>
</template>
