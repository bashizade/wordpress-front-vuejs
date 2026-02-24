<script setup>
import { onMounted, reactive, ref } from "vue";
import dayjs from "dayjs";
import { ElMessage } from "element-plus";
import PageHeader from "@/components/common/PageHeader.vue";
import { pageApi } from "@/api/wordpress";

const loading = ref(false);
const list = ref([]);
const total = ref(0);
const filters = reactive({
  page: 1,
  per_page: 10,
  search: ""
});
const dialogOpen = ref(false);
const editingId = ref(null);
const form = reactive({
  title: "",
  content: "",
  status: "draft"
});

const fetchPages = async () => {
  loading.value = true;
  try {
    const response = await pageApi.list({
      page: filters.page,
      per_page: filters.per_page,
      search: filters.search,
      status: "any"
    });
    list.value = response.items;
    total.value = response.total;
  } finally {
    loading.value = false;
  }
};

const openCreate = () => {
  editingId.value = null;
  form.title = "";
  form.content = "";
  form.status = "draft";
  dialogOpen.value = true;
};

const openEdit = (row) => {
  editingId.value = row.id;
  form.title = row.title?.rendered || "";
  form.content = row.content?.rendered || "";
  form.status = row.status;
  dialogOpen.value = true;
};

const save = async () => {
  if (editingId.value) {
    await pageApi.update(editingId.value, form);
    ElMessage.success("Page updated");
  } else {
    await pageApi.create(form);
    ElMessage.success("Page created");
  }
  dialogOpen.value = false;
  await fetchPages();
};

const remove = async (id) => {
  await pageApi.remove(id, true);
  ElMessage.success("Page deleted");
  await fetchPages();
};

onMounted(fetchPages);
</script>

<template>
  <div>
    <PageHeader title="Pages" subtitle="Manage WordPress pages">
      <el-button type="primary" @click="openCreate">Create Page</el-button>
    </PageHeader>

    <div class="panel mb-4 p-4">
      <div class="flex gap-3">
        <el-input v-model="filters.search" placeholder="Search pages..." @keyup.enter="fetchPages" />
        <el-button @click="fetchPages">Search</el-button>
      </div>
    </div>

    <div class="panel p-4">
      <el-table v-loading="loading" :data="list" stripe>
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column label="Title">
          <template #default="{ row }">
            <span v-html="row.title?.rendered" />
          </template>
        </el-table-column>
        <el-table-column prop="status" label="Status" width="120" />
        <el-table-column label="Date" width="170">
          <template #default="{ row }">{{ dayjs(row.date).format("YYYY-MM-DD") }}</template>
        </el-table-column>
        <el-table-column label="Actions" width="220">
          <template #default="{ row }">
            <div class="flex gap-2">
              <el-button size="small" @click="openEdit(row)">Edit</el-button>
              <el-popconfirm title="Delete this page?" @confirm="remove(row.id)">
                <template #reference>
                  <el-button size="small" type="danger">Delete</el-button>
                </template>
              </el-popconfirm>
            </div>
          </template>
        </el-table-column>
      </el-table>
      <div class="mt-4 flex justify-end">
        <el-pagination
          background
          layout="prev, pager, next"
          :current-page="filters.page"
          :page-size="filters.per_page"
          :total="total"
          @current-change="(page) => { filters.page = page; fetchPages(); }"
        />
      </div>
    </div>

    <el-dialog v-model="dialogOpen" :title="editingId ? 'Edit Page' : 'Create Page'" width="720">
      <el-form label-position="top">
        <el-form-item label="Title">
          <el-input v-model="form.title" />
        </el-form-item>
        <el-form-item label="Content">
          <el-input v-model="form.content" type="textarea" :rows="8" />
        </el-form-item>
        <el-form-item label="Status">
          <el-select v-model="form.status">
            <el-option value="draft" label="Draft" />
            <el-option value="publish" label="Publish" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogOpen = false">Cancel</el-button>
        <el-button type="primary" @click="save">Save</el-button>
      </template>
    </el-dialog>
  </div>
</template>
