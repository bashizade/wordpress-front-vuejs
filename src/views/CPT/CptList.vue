<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import dayjs from "dayjs";
import { ElMessage, ElMessageBox } from "element-plus";
import PageHeader from "@/components/common/PageHeader.vue";
import { cptPostsApi } from "@/api/cptPosts";
import { customCptApi } from "@/api/customCpt";

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const bulkLoading = ref(false);
const definitions = ref([]);
const selectedRows = ref([]);
const list = ref([]);
const total = ref(0);

const filters = reactive({
  page: 1,
  per_page: 10,
  search: "",
  status: "any"
});

const slug = computed(() => String(route.params.slug || ""));
const cptLabel = computed(() => {
  const found = definitions.value.find((item) => item.slug === slug.value);
  return found?.plural || slug.value;
});

const loadDefinitions = async () => {
  try {
    definitions.value = await customCptApi.list();
  } catch {
    definitions.value = [];
  }
};

const load = async () => {
  if (!slug.value) return;
  loading.value = true;
  try {
    const response = await cptPostsApi.list(slug.value, {
      page: filters.page,
      per_page: filters.per_page,
      search: filters.search,
      status: filters.status
    });
    list.value = response.items;
    total.value = response.total;
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed to load CPT items");
  } finally {
    loading.value = false;
  }
};

const onSearch = async () => {
  filters.page = 1;
  await load();
};

const openCreate = () => {
  router.push({ name: "cpt.new", params: { slug: slug.value } });
};

const openEdit = (id) => {
  router.push({ name: "cpt.edit", params: { slug: slug.value, id } });
};

const removeOne = async (id) => {
  try {
    await ElMessageBox.confirm("Delete this item?", "Confirm", { type: "warning" });
    await cptPostsApi.remove(slug.value, id, true);
    ElMessage.success("Item deleted");
    await load();
  } catch {
    // canceled
  }
};

const removeSelected = async () => {
  if (!selectedRows.value.length) {
    ElMessage.warning("No rows selected");
    return;
  }
  try {
    await ElMessageBox.confirm(`Delete ${selectedRows.value.length} selected items?`, "Confirm", {
      type: "warning"
    });
    bulkLoading.value = true;
    await Promise.all(selectedRows.value.map((row) => cptPostsApi.remove(slug.value, row.id, true)));
    ElMessage.success("Selected items deleted");
    await load();
    selectedRows.value = [];
  } catch {
    // canceled
  } finally {
    bulkLoading.value = false;
  }
};

const setSelectedRows = (rows) => {
  selectedRows.value = rows;
};

watch(
  () => route.params.slug,
  async () => {
    filters.page = 1;
    await load();
  }
);

onMounted(async () => {
  await loadDefinitions();
  await load();
});
</script>

<template>
  <div>
    <PageHeader :title="`${cptLabel} List`" :subtitle="`Manage ${cptLabel} content`">
      <div class="flex gap-2">
        <el-button type="danger" plain :loading="bulkLoading" @click="removeSelected">Delete Selected</el-button>
        <el-button type="primary" @click="openCreate">Add New</el-button>
      </div>
    </PageHeader>

    <div class="panel mb-4 p-4">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <el-input v-model="filters.search" placeholder="Search..." clearable @keyup.enter="onSearch" />
        <el-select v-model="filters.status" @change="onSearch">
          <el-option label="Any Status" value="any" />
          <el-option label="Published" value="publish" />
          <el-option label="Draft" value="draft" />
          <el-option label="Pending" value="pending" />
        </el-select>
        <el-button @click="onSearch">Apply Filters</el-button>
      </div>
    </div>

    <div class="panel p-4">
      <el-table v-loading="loading" :data="list" stripe @selection-change="setSelectedRows">
        <el-table-column type="selection" width="50" />
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column label="Title">
          <template #default="{ row }">
            <span v-html="row.title?.rendered || '(no title)'" />
          </template>
        </el-table-column>
        <el-table-column prop="status" label="Status" width="120" />
        <el-table-column label="Date" width="180">
          <template #default="{ row }">{{ dayjs(row.date).format("YYYY-MM-DD HH:mm") }}</template>
        </el-table-column>
        <el-table-column label="Actions" width="220">
          <template #default="{ row }">
            <div class="flex gap-2">
              <el-button size="small" @click="openEdit(row.id)">Edit</el-button>
              <el-button size="small" type="danger" @click="removeOne(row.id)">Delete</el-button>
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
          @current-change="(page) => { filters.page = page; load(); }"
        />
      </div>
    </div>
  </div>
</template>
