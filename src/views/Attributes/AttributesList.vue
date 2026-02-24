<script setup>
import { onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { ElMessage } from "element-plus";
import PageHeader from "@/components/common/PageHeader.vue";
import { useAttributeStore } from "@/stores/attributeStore";

const router = useRouter();
const attributeStore = useAttributeStore();
const dialogOpen = ref(false);
const editingId = ref(null);
const form = reactive({
  name: "",
  slug: "",
  type: "select",
  order_by: "menu_order",
  has_archives: false
});

const openCreate = () => {
  editingId.value = null;
  form.name = "";
  form.slug = "";
  form.type = "select";
  form.order_by = "menu_order";
  form.has_archives = false;
  dialogOpen.value = true;
};

const openEditModal = (row) => {
  editingId.value = row.id;
  form.name = row.name;
  form.slug = row.slug;
  form.type = row.type || "select";
  form.order_by = row.order_by || "menu_order";
  form.has_archives = row.has_archives || false;
  dialogOpen.value = true;
};

const save = async () => {
  try {
    if (editingId.value) {
      await attributeStore.updateAttribute(editingId.value, form);
    } else {
      await attributeStore.createAttribute(form);
    }
    dialogOpen.value = false;
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed saving attribute");
  }
};

const remove = async (id) => {
  try {
    await attributeStore.removeAttribute(id);
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed deleting attribute");
  }
};

const onNameInput = () => {
  if (!form.slug) form.slug = attributeStore.slugify(form.name);
};

onMounted(attributeStore.fetchAttributes);
</script>

<template>
  <div>
    <PageHeader title="Global Attributes" subtitle="WooCommerce attribute taxonomies">
      <el-button type="primary" @click="openCreate">Create Attribute</el-button>
    </PageHeader>

    <div class="panel p-4">
      <el-table v-loading="attributeStore.loading" :data="attributeStore.sortedAttributes" stripe>
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column prop="name" label="Name" />
        <el-table-column prop="slug" label="Slug" width="160" />
        <el-table-column prop="type" label="Type" width="120" />
        <el-table-column prop="order_by" label="Sort" width="130" />
        <el-table-column label="Actions" width="360">
          <template #default="{ row }">
            <div class="flex flex-wrap gap-2">
              <el-button size="small" @click="openEditModal(row)">Quick Edit</el-button>
              <el-button size="small" @click="router.push({ name: 'attributes.edit', params: { id: row.id } })">
                Full Edit
              </el-button>
              <el-button size="small" @click="router.push({ name: 'attributes.terms', params: { id: row.id } })">
                Terms
              </el-button>
              <el-popconfirm title="Delete attribute?" @confirm="remove(row.id)">
                <template #reference>
                  <el-button size="small" type="danger">Delete</el-button>
                </template>
              </el-popconfirm>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-dialog v-model="dialogOpen" :title="editingId ? 'Edit Attribute' : 'Create Attribute'" width="640">
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
      <template #footer>
        <el-button @click="dialogOpen = false">Cancel</el-button>
        <el-button type="primary" @click="save">Save</el-button>
      </template>
    </el-dialog>
  </div>
</template>
