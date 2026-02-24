<script setup>
import { onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import PageHeader from "@/components/common/PageHeader.vue";
import TableSkeleton from "@/components/common/TableSkeleton.vue";
import { useProductStore } from "@/stores/productStore";

const router = useRouter();
const productStore = useProductStore();
const quickDialog = ref(false);
const editingId = ref(null);
const quickForm = reactive({
  name: "",
  regular_price: "0",
  status: "publish"
});

const load = async () => {
  await productStore.fetchProducts();
};

const onSearch = async () => {
  await productStore.setFilters({ page: 1 });
};

const openQuickEdit = (product) => {
  editingId.value = product.id;
  quickForm.name = product.name;
  quickForm.regular_price = product.regular_price || "0";
  quickForm.status = product.status || "draft";
  quickDialog.value = true;
};

const saveQuickEdit = async () => {
  await productStore.updateProduct(editingId.value, quickForm);
  quickDialog.value = false;
};

onMounted(load);
</script>

<template>
  <div>
    <PageHeader title="Products" subtitle="Manage WooCommerce products">
      <router-link :to="{ name: 'products.create' }">
        <el-button type="primary">Create Product</el-button>
      </router-link>
    </PageHeader>
    <div class="panel mb-4 p-4">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <el-input
          v-model="productStore.filters.search"
          clearable
          placeholder="Search products..."
          @keyup.enter="onSearch"
        />
        <el-select v-model="productStore.filters.status" @change="onSearch">
          <el-option label="Published" value="publish" />
          <el-option label="Draft" value="draft" />
          <el-option label="Private" value="private" />
        </el-select>
        <el-button @click="onSearch">Apply Filters</el-button>
      </div>
    </div>
    <TableSkeleton v-if="productStore.loading" />
    <div v-else class="panel p-4">
      <el-table :data="productStore.list" stripe>
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column prop="name" label="Name" />
        <el-table-column label="Price" width="120">
          <template #default="{ row }">${{ row.price || row.regular_price || "0.00" }}</template>
        </el-table-column>
        <el-table-column prop="stock_status" label="Stock" width="130" />
        <el-table-column prop="status" label="Status" width="110" />
        <el-table-column label="Actions" width="280">
          <template #default="{ row }">
            <div class="flex gap-2">
              <router-link :to="{ name: 'products.edit', params: { id: row.id } }">
                <el-button size="small">Edit Page</el-button>
              </router-link>
              <router-link :to="{ name: 'products.edit', params: { id: row.id }, query: { tab: 'variations' } }">
                <el-button size="small">Variations</el-button>
              </router-link>
              <el-button size="small" @click="openQuickEdit(row)">Quick Edit</el-button>
              <el-popconfirm title="Delete this product?" @confirm="productStore.deleteProduct(row.id)">
                <template #reference>
                  <el-button size="small" type="danger">Delete</el-button>
                </template>
              </el-popconfirm>
            </div>
          </template>
        </el-table-column>
      </el-table>
      <div class="mt-4 flex justify-end">
        <el-pagination
          background
          layout="prev, pager, next"
          :current-page="productStore.filters.page"
          :page-size="productStore.filters.per_page"
          :total="productStore.pagination.total"
          @current-change="(page) => productStore.setFilters({ page })"
        />
      </div>
    </div>

    <el-dialog v-model="quickDialog" title="Quick Edit Product" width="520">
      <el-form label-position="top">
        <el-form-item label="Name">
          <el-input v-model="quickForm.name" />
        </el-form-item>
        <el-form-item label="Regular Price">
          <el-input v-model="quickForm.regular_price" />
        </el-form-item>
        <el-form-item label="Status">
          <el-select v-model="quickForm.status">
            <el-option label="Publish" value="publish" />
            <el-option label="Draft" value="draft" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="quickDialog = false">Cancel</el-button>
        <el-button type="primary" @click="saveQuickEdit">Save</el-button>
      </template>
    </el-dialog>
  </div>
</template>
