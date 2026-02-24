<script setup>
import { computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import dayjs from "dayjs";
import PageHeader from "@/components/common/PageHeader.vue";
import { useUserStore } from "@/stores/userStore";
import { useOrderStore } from "@/stores/orderStore";

const router = useRouter();
const userStore = useUserStore();
const orderStore = useOrderStore();

const statusType = (status) => {
  if (status === "processing") return "warning";
  if (status === "completed") return "success";
  if (status === "on-hold") return "info";
  if (status === "refunded") return "danger";
  return "";
};

const rows = computed(() => orderStore.myOrders);

onMounted(async () => {
  await userStore.fetchMe();
  const customer = await userStore.fetchCustomerProfile();
  if (customer?.id) {
    await orderStore.fetchMyOrders(customer.id, { per_page: 50 });
  }
});
</script>

<template>
  <div>
    <PageHeader title="سفارش های من" subtitle="تاریخچه سفارش های ووکامرس" />
    <div class="panel p-4">
      <el-table v-loading="orderStore.loading" :data="rows" stripe>
        <el-table-column prop="id" label="شماره سفارش" width="130">
          <template #default="{ row }">#{{ row.id.toLocaleString("fa-IR") }}</template>
        </el-table-column>
        <el-table-column label="تاریخ" width="170">
          <template #default="{ row }">{{ dayjs(row.date_created).format("YYYY-MM-DD HH:mm") }}</template>
        </el-table-column>
        <el-table-column label="مبلغ کل" width="140">
          <template #default="{ row }">{{ Number(row.total || 0).toLocaleString("fa-IR") }} {{ row.currency }}</template>
        </el-table-column>
        <el-table-column label="وضعیت" width="140">
          <template #default="{ row }">
            <el-tag :type="statusType(row.status)">{{ row.status }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="تعداد آیتم" width="120">
          <template #default="{ row }">{{ (row.line_items || []).length.toLocaleString("fa-IR") }}</template>
        </el-table-column>
        <el-table-column label="عملیات" width="140">
          <template #default="{ row }">
            <el-button size="small" @click="router.push({ name: 'user.order-details', params: { id: row.id } })">
              جزئیات
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>
  </div>
</template>
