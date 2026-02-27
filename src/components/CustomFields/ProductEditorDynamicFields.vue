<script setup>
import DynamicPostFieldsForm from "@/components/CustomFields/DynamicPostFieldsForm.vue";
import { ref } from "vue";

const props = defineProps({
  productId: { type: [Number, String], default: null },
  title: { type: String, default: "Product Custom Fields" }
});

const formRef = ref(null);

const load = async () => formRef.value?.load?.();
const saveMeta = async (postId) => formRef.value?.saveMeta?.(postId);
const getMetaPayload = () => formRef.value?.getMetaPayload?.() || {};

defineExpose({
  load,
  saveMeta,
  getMetaPayload
});
</script>

<template>
  <DynamicPostFieldsForm ref="formRef" :post-id="props.productId" post-type="product" :title="props.title" />
</template>
