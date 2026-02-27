<script setup>
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { ElMessage } from "element-plus";
import SiteShell from "@/components/storefront/SiteShell.vue";
import Breadcrumb from "@/components/storefront/Breadcrumb.vue";
import Loader from "@/components/storefront/Loader.vue";
import { useShopStore } from "@/stores/shopStore";

const router = useRouter();
const shopStore = useShopStore();
const coupon = ref("");

document.title = "Cart | Vue Store";

const hasCart = computed(() => shopStore.cart.length > 0);
const formatPrice = (value) => {
  const amount = Number(value || 0);
  return amount.toLocaleString();
};

const updateQty = async (item, value) => {
  const next = shopStore.cart.map((row) =>
    row === item ? { ...row, quantity: Math.max(1, Number(value || 1)) } : row
  );
  await shopStore.updateCart(next);
};

const removeItem = async (item) => {
  await shopStore.removeFromCart({
    key: item.key,
    product_id: item.product_id || item.id,
    variation_id: item.variation_id || 0
  });
};

const applyCoupon = () => {
  ElMessage.info(coupon.value ? "Coupon applied (demo)" : "Enter coupon code");
};

onMounted(() => shopStore.fetchCart());
</script>

<template>
  <SiteShell>
    <Breadcrumb :items="[{ label: 'Home', to: '/' }, { label: 'Cart' }]" />

    <Loader v-if="shopStore.loadingCart" :rows="4" />

    <div v-else-if="!hasCart" class="panel p-8 text-center">
      <p class="mb-4 text-slate-500">Your cart is empty.</p>
      <router-link to="/products" class="rounded bg-sky-600 px-4 py-2 text-white">Go to products</router-link>
    </div>

    <div v-else class="grid gap-6 lg:grid-cols-3">
      <div class="panel space-y-3 p-4 lg:col-span-2">
        <div v-for="item in shopStore.cart" :key="item.key || `${item.product_id}_${item.variation_id}`" class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
          <div>
            <p class="font-semibold">{{ item._name || item.name || `#${item.product_id || item.id}` }}</p>
            <p class="text-sm text-slate-500">
              {{ formatPrice(item._unitPrice || item.price || 0) }} × {{ item.quantity }}
            </p>
          </div>
          <div class="flex items-center gap-2">
            <el-input-number :model-value="Number(item.quantity || 1)" :min="1" @change="(v) => updateQty(item, v)" />
            <span class="min-w-16 text-sm font-semibold">{{ formatPrice(item._lineTotal || 0) }}</span>
            <el-button type="danger" plain @click="removeItem(item)">Remove</el-button>
          </div>
        </div>
      </div>

      <div class="panel space-y-4 p-4">
        <h2 class="text-xl font-black">Summary</h2>
        <p>Subtotal: {{ formatPrice(shopStore.cartSubtotal) }}</p>
        <p>Total: {{ formatPrice(shopStore.cartSubtotal) }}</p>
        <div class="flex gap-2">
          <el-input v-model="coupon" placeholder="Coupon code" />
          <el-button @click="applyCoupon">Apply</el-button>
        </div>
        <el-button type="primary" class="w-full" @click="router.push('/checkout')">Proceed to Checkout</el-button>
      </div>
    </div>
  </SiteShell>
</template>
