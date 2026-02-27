<script setup>
import DynamicPostFieldsForm from "@/components/CustomFields/DynamicPostFieldsForm.vue";
import { ref } from "vue";

const props = defineProps({
  postId: { type: [Number, String], default: null },
  postType: { type: String, default: "post" },
  title: { type: String, default: "Post Custom Fields" }
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
  <DynamicPostFieldsForm ref="formRef" :post-id="props.postId" :post-type="props.postType" :title="props.title" />
</template>
