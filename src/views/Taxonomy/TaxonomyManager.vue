<script setup>
import { onMounted, reactive, ref } from "vue";
import { ElMessage } from "element-plus";
import PageHeader from "@/components/common/PageHeader.vue";
import { categoryApi, tagApi } from "@/api/wordpress";
import { productCategoryApi } from "@/api/woocommerce";

const activeTab = ref("postCategories");
const postCategories = ref([]);
const postTags = ref([]);
const productCategories = ref([]);
const dialogOpen = ref(false);
const editing = ref(null);
const form = reactive({
  name: "",
  description: ""
});

const fetchAll = async () => {
  const [cats, tags, wcCats] = await Promise.all([
    categoryApi.list({ per_page: 100 }),
    tagApi.list({ per_page: 100 }),
    productCategoryApi.list({ per_page: 100 })
  ]);
  postCategories.value = cats.items;
  postTags.value = tags.items;
  productCategories.value = wcCats;
};

const openCreate = () => {
  editing.value = null;
  form.name = "";
  form.description = "";
  dialogOpen.value = true;
};

const openEdit = (item) => {
  editing.value = item;
  form.name = item.name;
  form.description = item.description || "";
  dialogOpen.value = true;
};

const save = async () => {
  const type = activeTab.value;
  const payload = {
    name: form.name,
    description: form.description
  };

  if (type === "postCategories") {
    if (editing.value) await categoryApi.update(editing.value.id, payload);
    else await categoryApi.create(payload);
  } else if (type === "postTags") {
    if (editing.value) await tagApi.update(editing.value.id, payload);
    else await tagApi.create(payload);
  } else {
    if (editing.value) await productCategoryApi.update(editing.value.id, payload);
    else await productCategoryApi.create(payload);
  }
  ElMessage.success("Saved");
  dialogOpen.value = false;
  await fetchAll();
};

const remove = async (id) => {
  if (activeTab.value === "postCategories") await categoryApi.remove(id, true);
  else if (activeTab.value === "postTags") await tagApi.remove(id, true);
  else await productCategoryApi.remove(id, true);
  ElMessage.success("Deleted");
  await fetchAll();
};

onMounted(fetchAll);
</script>

<template>
  <div>
    <PageHeader title="Taxonomy" subtitle="Post categories, tags, and product categories">
      <el-button type="primary" @click="openCreate">Create</el-button>
    </PageHeader>

    <div class="panel p-4">
      <el-tabs v-model="activeTab">
        <el-tab-pane label="Post Categories" name="postCategories" />
        <el-tab-pane label="Post Tags" name="postTags" />
        <el-tab-pane label="Product Categories" name="productCategories" />
      </el-tabs>

      <el-table
        :data="activeTab === 'postCategories' ? postCategories : activeTab === 'postTags' ? postTags : productCategories"
        stripe
      >
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column prop="name" label="Name" />
        <el-table-column prop="count" label="Count" width="100" />
        <el-table-column label="Actions" width="220">
          <template #default="{ row }">
            <div class="flex gap-2">
              <el-button size="small" @click="openEdit(row)">Edit</el-button>
              <el-popconfirm title="Delete item?" @confirm="remove(row.id)">
                <template #reference>
                  <el-button size="small" type="danger">Delete</el-button>
                </template>
              </el-popconfirm>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-dialog v-model="dialogOpen" :title="editing ? 'Edit Taxonomy' : 'Create Taxonomy'" width="520">
      <el-form label-position="top">
        <el-form-item label="Name">
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item label="Description">
          <el-input v-model="form.description" type="textarea" :rows="4" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogOpen = false">Cancel</el-button>
        <el-button type="primary" @click="save">Save</el-button>
      </template>
    </el-dialog>
  </div>
</template>
