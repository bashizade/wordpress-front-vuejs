<script setup>
import { computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import PageHeader from "@/components/common/PageHeader.vue";
import { useUserStore } from "@/stores/userStore";
import { useOrderStore } from "@/stores/orderStore";

const router = useRouter();
const userStore = useUserStore();
const orderStore = useOrderStore();

const orderStats = computed(() => ({
  total: orderStore.myOrders.length,
  processing: orderStore.myOrders.filter((item) => item.status === "processing").length,
  completed: orderStore.myOrders.filter((item) => item.status === "completed").length
}));

onMounted(async () => {
  await userStore.fetchMe();
  const customer = await userStore.fetchCustomerProfile();
  if (customer?.id) {
    await orderStore.fetchMyOrders(customer.id);
  }
});
</script>

<template>
  <div>
    <PageHeader title="داشبورد کاربری" subtitle="نمای کلی حساب، سفارش ها و پروفایل" />
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <div class="panel p-4">
        <p class="text-sm text-slate-500">کل سفارش ها</p>
        <p class="text-3xl font-bold">{{ orderStats.total.toLocaleString("fa-IR") }}</p>
      </div>
      <div class="panel p-4">
        <p class="text-sm text-slate-500">در حال پردازش</p>
        <p class="text-3xl font-bold">{{ orderStats.processing.toLocaleString("fa-IR") }}</p>
      </div>
      <div class="panel p-4">
        <p class="text-sm text-slate-500">تکمیل شده</p>
        <p class="text-3xl font-bold">{{ orderStats.completed.toLocaleString("fa-IR") }}</p>
      </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
      <div class="panel p-4">
        <h3 class="mb-2 font-semibold">اطلاعات پروفایل</h3>
        <p>نام کاربری: {{ userStore.currentUser?.username }}</p>
        <p>ایمیل: {{ userStore.currentUser?.email }}</p>
        <el-button class="mt-3" @click="router.push({ name: 'user.profile' })">ویرایش پروفایل</el-button>
      </div>
      <div class="panel p-4">
        <h3 class="mb-2 font-semibold">دسترسی سریع</h3>
        <div class="flex flex-wrap gap-2">
          <el-button @click="router.push({ name: 'user.orders' })">سفارش ها</el-button>
          <el-button @click="router.push({ name: 'user.addresses' })">آدرس ها</el-button>
          <el-button @click="router.push({ name: 'user.profile' })">فیلدهای سفارشی</el-button>
        </div>
      </div>
    </div>
  </div>
</template>
