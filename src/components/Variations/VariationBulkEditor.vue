<script setup>
import { reactive } from "vue";

const open = defineModel("open", { type: Boolean, default: false });
const emit = defineEmits(["apply", "delete-all"]);

const form = reactive({
  action: "set_regular_price",
  value: 0
});

const actions = [
  { label: "Set regular price", value: "set_regular_price" },
  { label: "Set sale price", value: "set_sale_price" },
  { label: "Increase/decrease regular price by %", value: "price_percent" },
  { label: "Set stock quantity for all", value: "stock_set" },
  { label: "Update names from attributes", value: "name_from_attributes" }
];

const apply = () => {
  emit("apply", { ...form });
  open.value = false;
};
</script>

<template>
  <el-dialog v-model="open" title="Bulk Variation Tools" width="520">
    <el-form label-position="top">
      <el-form-item label="Bulk Action">
        <el-select v-model="form.action">
          <el-option v-for="item in actions" :key="item.value" :label="item.label" :value="item.value" />
        </el-select>
      </el-form-item>
      <el-form-item label="Value">
        <el-input-number v-model="form.value" :step="1" />
      </el-form-item>
    </el-form>
    <template #footer>
      <div class="flex w-full justify-between">
        <el-popconfirm title="Delete all variations?" @confirm="$emit('delete-all')">
          <template #reference>
            <el-button type="danger">Delete All Variations</el-button>
          </template>
        </el-popconfirm>
        <div class="flex gap-2">
          <el-button @click="open = false">Cancel</el-button>
          <el-button type="primary" @click="apply">Apply</el-button>
        </div>
      </div>
    </template>
  </el-dialog>
</template>
