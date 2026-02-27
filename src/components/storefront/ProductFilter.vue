<script setup>
import { reactive, watch } from "vue";

const props = defineProps({
  modelValue: { type: Object, required: true },
  categories: { type: Array, default: () => [] }
});

const emit = defineEmits(["update:modelValue", "apply"]);

const local = reactive({
  ...props.modelValue
});

watch(
  () => props.modelValue,
  (next) => Object.assign(local, next),
  { deep: true }
);

const apply = () => {
  emit("update:modelValue", { ...local });
  emit("apply");
};
</script>

<template>
  <div class="panel p-4">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
      <el-input v-model="local.search" placeholder="Search products" clearable @keyup.enter="apply" />

      <el-select v-model="local.category" clearable placeholder="Category">
        <el-option v-for="cat in categories" :key="cat.id" :label="cat.name" :value="cat.id" />
      </el-select>

      <el-input-number v-model="local.min_price" :min="0" controls-position="right" placeholder="Min" />
      <el-input-number v-model="local.max_price" :min="0" controls-position="right" placeholder="Max" />

      <el-select v-model="local.orderBy" placeholder="Sort">
        <el-option label="Newest" value="date" />
        <el-option label="Price low to high" value="price_asc" />
        <el-option label="Price high to low" value="price_desc" />
      </el-select>
    </div>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
      <el-radio-group v-model="local.viewMode" size="small">
        <el-radio-button label="grid">Grid</el-radio-button>
        <el-radio-button label="list">List</el-radio-button>
      </el-radio-group>
      <el-button type="primary" @click="apply">Apply Filters</el-button>
    </div>
  </div>
</template>
