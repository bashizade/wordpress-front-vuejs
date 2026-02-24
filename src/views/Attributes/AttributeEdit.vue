<script setup>
import { onMounted, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ElMessage } from "element-plus";
import PageHeader from "@/components/common/PageHeader.vue";
import { useAttributeStore } from "@/stores/attributeStore";

const route = useRoute();
const router = useRouter();
const attributeStore = useAttributeStore();
const loading = ref(true);
const saving = ref(false);
const form = reactive({
  name: "",
  slug: "",
  type: "select",
  order_by: "menu_order",
  has_archives: false
});

const load = async () => {
  loading.value = true;
  try {
    const attribute = await attributeStore.fetchAttribute(route.params.id);
    form.name = attribute.name;
    form.slug = attribute.slug;
    form.type = attribute.type || "select";
    form.order_by = attribute.order_by || "menu_order";
    form.has_archives = attribute.has_archives || false;
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed loading attribute");
    router.push({ name: "attributes.list" });
  } finally {
    loading.value = false;
  }
};

const save = async () => {
  saving.value = true;
  try {
    await attributeStore.updateAttribute(route.params.id, form);
    router.push({ name: "attributes.list" });
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed saving attribute");
  } finally {
    saving.value = false;
  }
};

const onNameInput = () => {
  if (!form.slug) form.slug = attributeStore.slugify(form.name);
};

onMounted(load);
</script>

<template>
  <div>
    <PageHeader title="Edit Attribute" subtitle="Configure taxonomy behavior" />
    <div v-if="loading" class="panel p-4"><el-skeleton :rows="8" animated /></div>
    <div v-else class="panel p-4">
      <el-form label-position="top">
        <el-form-item label="Name">
          <el-input v-model="form.name" @input="onNameInput" />
        </el-form-item>
        <el-form-item label="Slug">
          <el-input v-model="form.slug" />
        </el-form-item>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
          <el-form-item label="Type">
            <el-select v-model="form.type">
              <el-option label="Select" value="select" />
            </el-select>
          </el-form-item>
          <el-form-item label="Default Sort Order">
            <el-select v-model="form.order_by">
              <el-option label="Custom ordering" value="menu_order" />
              <el-option label="Name" value="name" />
              <el-option label="Name numeric" value="name_num" />
              <el-option label="Term ID" value="id" />
            </el-select>
          </el-form-item>
          <el-form-item label="Archives">
            <el-switch v-model="form.has_archives" />
          </el-form-item>
        </div>
      </el-form>
      <div class="mt-2 flex justify-end gap-2">
        <el-button @click="router.push({ name: 'attributes.list' })">Cancel</el-button>
        <el-button type="primary" :loading="saving" @click="save">Save</el-button>
      </div>
    </div>
  </div>
</template>
