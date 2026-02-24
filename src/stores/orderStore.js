import { computed, ref } from "vue";
import { defineStore } from "pinia";
import { ElMessage } from "element-plus";
import dayjs from "dayjs";
import { orderApi } from "@/api/woocommerce";
import { ordersApi } from "@/api/orders";

export const useOrderStore = defineStore("orderStore", () => {
  const orders = ref([]);
  const myOrders = ref([]);
  const selectedOrder = ref(null);
  const loading = ref(false);
  const stats = ref({
    totalOrders: 0,
    completedOrders: 0,
    totalRevenue: 0,
    todayOrders: 0
  });
  const dailyReports = ref([]);

  const fetchOrders = async (params = { page: 1, per_page: 20 }) => {
    loading.value = true;
    try {
      orders.value = await orderApi.list(params);
      computeStats();
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "Unable to fetch orders");
    } finally {
      loading.value = false;
    }
  };

  const computeStats = () => {
    const today = dayjs().format("YYYY-MM-DD");
    const reportMap = {};
    let totalRevenue = 0;
    let completed = 0;
    let todayOrders = 0;

    orders.value.forEach((order) => {
      const date = dayjs(order.date_created).format("YYYY-MM-DD");
      const amount = Number(order.total || 0);
      totalRevenue += amount;

      if (order.status === "completed") {
        completed += 1;
      }
      if (date === today) {
        todayOrders += 1;
      }
      reportMap[date] = (reportMap[date] || 0) + amount;
    });

    dailyReports.value = Object.entries(reportMap)
      .sort(([a], [b]) => (a > b ? 1 : -1))
      .slice(-14)
      .map(([date, total]) => ({ date, total }));

    stats.value = {
      totalOrders: orders.value.length,
      completedOrders: completed,
      totalRevenue,
      todayOrders
    };
  };

  const revenueSeries = computed(() => dailyReports.value.map((item) => item.total));
  const revenueLabels = computed(() => dailyReports.value.map((item) => item.date));

  const fetchMyOrders = async (customerId, params = {}) => {
    loading.value = true;
    try {
      myOrders.value = await ordersApi.listByCustomer(customerId, params);
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "خطا در دریافت سفارش ها");
    } finally {
      loading.value = false;
    }
    return myOrders.value;
  };

  const fetchOrderDetails = async (orderId) => {
    loading.value = true;
    try {
      selectedOrder.value = await ordersApi.getById(orderId);
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "خطا در دریافت جزئیات سفارش");
    } finally {
      loading.value = false;
    }
    return selectedOrder.value;
  };

  return {
    orders,
    myOrders,
    selectedOrder,
    loading,
    stats,
    dailyReports,
    revenueSeries,
    revenueLabels,
    fetchOrders,
    fetchMyOrders,
    fetchOrderDetails
  };
});
