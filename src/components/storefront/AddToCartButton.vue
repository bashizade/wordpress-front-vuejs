<script setup>
import { ref } from "vue";
import { ElMessage } from "element-plus";
import { useShopStore } from "@/stores/shopStore";

const props = defineProps({
  productId: { type: Number, required: true },
  variationId: { type: Number, default: 0 },
  quantity: { type: Number, default: 1 },
  variation: { type: Array, default: () => [] },
  disabled: { type: Boolean, default: false }
});

const loading = ref(false);
const shopStore = useShopStore();

const add = async () => {
  if (props.disabled) return;
  loading.value = true;
  try {
    await shopStore.addToCart({
      product_id: props.productId,
      quantity: props.quantity,
      variation_id: props.variationId,
      variation: props.variation
    });
    ElMessage.success("Added to cart");
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Unable to add to cart");
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <el-button type="primary" :loading="loading" :disabled="disabled" @click="add">
    Add to cart
  </el-button>
</template>
