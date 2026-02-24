<script setup>
import { onMounted, reactive, ref } from "vue";
import dayjs from "dayjs";
import { ElMessage } from "element-plus";
import PageHeader from "@/components/common/PageHeader.vue";
import { orderApi } from "@/api/woocommerce";

const loading = ref(false);
const list = ref([]);
const filters = reactive({
  page: 1,
  per_page: 10,
  search: "",
  status: ""
});

const fetchOrders = async () => {
  loading.value = true;
  try {
    list.value = await orderApi.list(filters);
  } finally {
    loading.value = false;
  }
};

const updateStatus = async (id, status) => {
  await orderApi.update(id, { status });
  ElMessage.success("Order updated");
  await fetchOrders();
};

const remove = async (id) => {
  await orderApi.remove(id, true);
  ElMessage.success("Order deleted");
  await fetchOrders();
};

onMounted(fetchOrders);
</script>

<template>
  <div>
    <PageHeader title="Orders" subtitle="Manage WooCommerce orders" />

    <div class="panel mb-4 p-4">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <el-input v-model="filters.search" placeholder="Search by customer..." @keyup.enter="fetchOrders" />
        <el-select v-model="filters.status" placeholder="Any status" clearable @change="fetchOrders">
          <el-option label="Pending" value="pending" />
          <el-option label="Processing" value="processing" />
          <el-option label="Completed" value="completed" />
          <el-option label="Cancelled" value="cancelled" />
        </el-select>
        <el-button @click="fetchOrders">Apply Filters</el-button>
      </div>
    </div>

    <div class="panel p-4">
      <el-table v-loading="loading" :data="list" stripe>
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column label="Customer">
          <template #default="{ row }">{{ row.billing?.first_name }} {{ row.billing?.last_name }}</template>
        </el-table-column>
        <el-table-column label="Total" width="120">
          <template #default="{ row }">${{ row.total }}</template>
        </el-table-column>
        <el-table-column prop="status" label="Status" width="120" />
        <el-table-column label="Date" width="160">
          <template #default="{ row }">{{ dayjs(row.date_created).format("YYYY-MM-DD") }}</template>
        </el-table-column>
        <el-table-column label="Actions" width="320">
          <template #default="{ row }">
            <div class="flex flex-wrap gap-2">
              <el-button size="small" @click="updateStatus(row.id, 'processing')">Processing</el-button>
              <el-button size="small" @click="updateStatus(row.id, 'completed')">Complete</el-button>
              <el-popconfirm title="Delete this order?" @confirm="remove(row.id)">
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
          :current-page="filters.page"
          :page-size="filters.per_page"
          :total="filters.page * filters.per_page + (list.length === filters.per_page ? 1 : 0)"
          @current-change="(page) => { filters.page = page; fetchOrders(); }"
        />
      </div>
    </div>
  </div>
</template>
