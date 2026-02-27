<script setup>
const props = defineProps({
  currentPage: { type: Number, default: 1 },
  pageSize: { type: Number, default: 10 },
  total: { type: Number, default: 0 }
});

const emit = defineEmits(["change"]);
</script>

<template>
  <div class="flex justify-center py-4">
    <el-pagination
      background
      layout="prev, pager, next"
      :current-page="props.currentPage"
      :page-size="props.pageSize"
      :total="props.total"
      @current-change="(page) => emit('change', page)"
    />
  </div>
</template>
