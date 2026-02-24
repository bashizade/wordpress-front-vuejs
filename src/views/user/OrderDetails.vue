<script setup>
import { onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ElMessage } from "element-plus";
import dayjs from "dayjs";
import PageHeader from "@/components/common/PageHeader.vue";
import { useOrderStore } from "@/stores/orderStore";
import { useUserStore } from "@/stores/userStore";

const route = useRoute();
const router = useRouter();
const orderStore = useOrderStore();
const userStore = useUserStore();

onMounted(async () => {
  await userStore.fetchMe();
  const order = await orderStore.fetchOrderDetails(route.params.id);
  if (order?.customer_id && userStore.currentUser?.id && order.customer_id !== userStore.currentUser.id) {
    ElMessage.error("دسترسی به این سفارش مجاز نیست");
    router.push({ name: "user.orders" });
  }
});
</script>

<template>
  <div>
    <PageHeader title="جزئیات سفارش" subtitle="مشاهده وضعیت، آیتم ها و اطلاعات ارسال">
      <el-button @click="router.push({ name: 'user.orders' })">بازگشت</el-button>
    </PageHeader>
    <div v-if="orderStore.loading" class="panel p-4"><el-skeleton :rows="8" animated /></div>
    <div v-else-if="orderStore.selectedOrder" class="space-y-4">
      <div class="panel p-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
          <p><strong>شماره:</strong> #{{ orderStore.selectedOrder.id.toLocaleString("fa-IR") }}</p>
          <p><strong>تاریخ:</strong> {{ dayjs(orderStore.selectedOrder.date_created).format("YYYY-MM-DD HH:mm") }}</p>
          <p><strong>وضعیت:</strong> {{ orderStore.selectedOrder.status }}</p>
        </div>
      </div>

      <div class="panel p-4">
        <h3 class="mb-2 font-semibold">اقلام سفارش</h3>
        <el-table :data="orderStore.selectedOrder.line_items || []" stripe>
          <el-table-column prop="name" label="محصول" />
          <el-table-column prop="quantity" label="تعداد" width="100" />
          <el-table-column label="قیمت" width="140">
            <template #default="{ row }">{{ Number(row.total || 0).toLocaleString("fa-IR") }}</template>
          </el-table-column>
        </el-table>
      </div>

      <div class="panel p-4">
        <h3 class="mb-2 font-semibold">آدرس ارسال</h3>
        <p>{{ orderStore.selectedOrder.shipping?.first_name }} {{ orderStore.selectedOrder.shipping?.last_name }}</p>
        <p>{{ orderStore.selectedOrder.shipping?.address_1 }}</p>
        <p>{{ orderStore.selectedOrder.shipping?.city }} - {{ orderStore.selectedOrder.shipping?.postcode }}</p>
      </div>
    </div>
  </div>
</template>
