<script setup>
import { computed, onMounted, onUnmounted, ref } from "vue";
import { Line } from "vue-chartjs";
import {
  CategoryScale,
  Chart as ChartJS,
  Legend,
  LineElement,
  LinearScale,
  PointElement,
  Title,
  Tooltip
} from "chart.js";
import PageHeader from "@/components/common/PageHeader.vue";
import { useOrderStore } from "@/stores/orderStore";
import { postApi } from "@/api/wordpress";
import { productApi } from "@/api/woocommerce";

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

const orderStore = useOrderStore();
const loading = ref(true);
const postCount = ref(0);
const productCount = ref(0);
let pollingTimer = null;

const chartData = computed(() => ({
  labels: orderStore.revenueLabels,
  datasets: [
    {
      label: "Revenue",
      backgroundColor: "#0ea5e9",
      borderColor: "#0284c7",
      tension: 0.35,
      data: orderStore.revenueSeries
    }
  ]
}));

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false
};

const loadStats = async () => {
  loading.value = true;
  try {
    await orderStore.fetchOrders({ per_page: 100, page: 1, orderby: "date", order: "desc" });
    const posts = await postApi.list({ per_page: 1, page: 1, status: "any" });
    postCount.value = posts.total;
    const products = await productApi.list({ per_page: 100, page: 1 });
    productCount.value = products.length;
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await loadStats();
  pollingTimer = setInterval(loadStats, 30000);
});

onUnmounted(() => {
  clearInterval(pollingTimer);
});
</script>

<template>
  <div>
    <PageHeader title="Dashboard" subtitle="Realtime commerce + content overview" />
    <div v-if="loading" class="grid grid-cols-1 gap-4 md:grid-cols-4">
      <div v-for="n in 4" :key="n" class="panel p-4"><el-skeleton :rows="2" animated /></div>
    </div>
    <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-4">
      <div class="panel p-4">
        <p class="text-sm text-slate-500">Orders</p>
        <p class="text-3xl font-bold">{{ orderStore.stats.totalOrders }}</p>
      </div>
      <div class="panel p-4">
        <p class="text-sm text-slate-500">Revenue</p>
        <p class="text-3xl font-bold">${{ orderStore.stats.totalRevenue.toFixed(2) }}</p>
      </div>
      <div class="panel p-4">
        <p class="text-sm text-slate-500">Posts</p>
        <p class="text-3xl font-bold">{{ postCount }}</p>
      </div>
      <div class="panel p-4">
        <p class="text-sm text-slate-500">Products (sampled)</p>
        <p class="text-3xl font-bold">{{ productCount }}</p>
      </div>
    </div>

    <div class="panel mt-4 p-4">
      <div class="mb-4 flex items-center justify-between">
        <h3 class="font-semibold">Revenue Trend (14 days)</h3>
        <span class="text-xs text-slate-500">Auto-refresh every 30s</span>
      </div>
      <div class="h-72">
        <Line :data="chartData" :options="chartOptions" />
      </div>
    </div>
  </div>
</template>
