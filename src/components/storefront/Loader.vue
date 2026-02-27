<script setup>
defineProps({
  rows: { type: Number, default: 4 }
});
</script>

<template>
  <div class="panel p-4">
    <el-skeleton :rows="rows" animated />
  </div>
</template>
