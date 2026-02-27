<script setup>
import { onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { ElMessage } from "element-plus";
import SiteShell from "@/components/storefront/SiteShell.vue";
import Breadcrumb from "@/components/storefront/Breadcrumb.vue";
import { useShopStore } from "@/stores/shopStore";
import { useAuthStore } from "@/stores/authStore";

document.title = "Checkout | Vue Store";

const router = useRouter();
const shopStore = useShopStore();
const authStore = useAuthStore();
const placing = ref(false);

const formatPrice = (value) => Number(value || 0).toLocaleString();

const form = reactive({
  billing: {
    first_name: "",
    last_name: "",
    email: authStore.user?.email || "",
    phone: "",
    address_1: "",
    city: "",
    state: "",
    postcode: "",
    country: "IR"
  },
  shipping: {
    first_name: "",
    last_name: "",
    address_1: "",
    city: "",
    state: "",
    postcode: "",
    country: "IR"
  },
  payment_method: "cod",
  payment_method_title: "Cash on delivery",
  set_paid: false,
  customer_note: ""
});

const placeOrder = async () => {
  if (!shopStore.cart.length) {
    ElMessage.error("Your cart is empty");
    return;
  }

  placing.value = true;
  try {
    const line_items = shopStore.cart.map((item) => ({
      product_id: item.product_id || item.id,
      variation_id: item.variation_id || 0,
      quantity: Number(item.quantity || 1)
    }));

    const payload = {
      ...form,
      line_items,
      customer_id: authStore.user?.id || 0
    };

    const order = await shopStore.checkout(payload);
    ElMessage.success("Order placed successfully");
    router.push({ path: "/", query: { order: order.id } });
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Checkout failed");
  } finally {
    placing.value = false;
  }
};

onMounted(() => {
  shopStore.fetchCart();
});
</script>

<template>
  <SiteShell>
    <Breadcrumb :items="[{ label: 'Home', to: '/' }, { label: 'Checkout' }]" />

    <div class="grid gap-6 lg:grid-cols-3">
      <div class="panel p-4 lg:col-span-2">
        <h2 class="mb-4 text-xl font-black">Billing Details</h2>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <el-input v-model="form.billing.first_name" placeholder="First name" />
          <el-input v-model="form.billing.last_name" placeholder="Last name" />
          <el-input v-model="form.billing.email" placeholder="Email" />
          <el-input v-model="form.billing.phone" placeholder="Phone" />
          <el-input v-model="form.billing.address_1" placeholder="Address" class="md:col-span-2" />
          <el-input v-model="form.billing.city" placeholder="City" />
          <el-input v-model="form.billing.state" placeholder="State" />
          <el-input v-model="form.billing.postcode" placeholder="Postcode" />
        </div>

        <h3 class="mb-3 mt-6 text-lg font-bold">Shipping Details</h3>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <el-input v-model="form.shipping.first_name" placeholder="First name" />
          <el-input v-model="form.shipping.last_name" placeholder="Last name" />
          <el-input v-model="form.shipping.address_1" placeholder="Address" class="md:col-span-2" />
          <el-input v-model="form.shipping.city" placeholder="City" />
          <el-input v-model="form.shipping.state" placeholder="State" />
          <el-input v-model="form.shipping.postcode" placeholder="Postcode" />
        </div>

        <h3 class="mb-3 mt-6 text-lg font-bold">Payment Method</h3>
        <el-radio-group v-model="form.payment_method">
          <el-radio value="cod">Cash on delivery</el-radio>
          <el-radio value="bacs">Direct bank transfer</el-radio>
        </el-radio-group>
      </div>

      <div class="panel p-4">
        <h2 class="mb-4 text-xl font-black">Order Summary</h2>
        <div class="space-y-2 border-b border-slate-200 pb-3 text-sm">
          <div
            v-for="item in shopStore.cart"
            :key="item.key || `${item.product_id}_${item.variation_id}`"
            class="flex justify-between"
          >
            <span>{{ item._name || item.name || `#${item.product_id || item.id}` }} x {{ item.quantity }}</span>
            <span>{{ formatPrice(item._lineTotal || 0) }}</span>
          </div>
        </div>
        <div class="mt-3 flex justify-between font-semibold">
          <span>Total</span>
          <span>{{ formatPrice(shopStore.cartSubtotal) }}</span>
        </div>
        <el-button type="primary" class="mt-4 w-full" :loading="placing" @click="placeOrder">Place Order</el-button>
      </div>
    </div>
  </SiteShell>
</template>
