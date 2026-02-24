<script setup>
import { onMounted, reactive, ref } from "vue";
import dayjs from "dayjs";
import { ElMessage } from "element-plus";
import PageHeader from "@/components/common/PageHeader.vue";
import { commentApi } from "@/api/wordpress";

const loading = ref(false);
const list = ref([]);
const total = ref(0);
const filters = reactive({
  page: 1,
  per_page: 10,
  search: "",
  status: "all"
});

const fetchComments = async () => {
  loading.value = true;
  try {
    const data = await commentApi.list(filters);
    list.value = data.items;
    total.value = data.total;
  } finally {
    loading.value = false;
  }
};

const setStatus = async (id, status) => {
  await commentApi.update(id, { status });
  ElMessage.success("Comment updated");
  await fetchComments();
};

const remove = async (id) => {
  await commentApi.remove(id, true);
  ElMessage.success("Comment deleted");
  await fetchComments();
};

onMounted(fetchComments);
</script>

<template>
  <div>
    <PageHeader title="Comments" subtitle="Moderate website comments" />

    <div class="panel mb-4 p-4">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <el-input v-model="filters.search" placeholder="Search comments..." @keyup.enter="fetchComments" />
        <el-select v-model="filters.status" @change="fetchComments">
          <el-option label="All" value="all" />
          <el-option label="Approved" value="approve" />
          <el-option label="Hold" value="hold" />
          <el-option label="Spam" value="spam" />
        </el-select>
        <el-button @click="fetchComments">Apply Filters</el-button>
      </div>
    </div>

    <div class="panel p-4">
      <el-table v-loading="loading" :data="list" stripe>
        <el-table-column prop="id" label="ID" width="90" />
        <el-table-column label="Author" width="180">
          <template #default="{ row }">{{ row.author_name }}</template>
        </el-table-column>
        <el-table-column label="Comment">
          <template #default="{ row }">
            <div v-html="row.content?.rendered" class="prose max-w-none text-sm" />
          </template>
        </el-table-column>
        <el-table-column label="Date" width="170">
          <template #default="{ row }">{{ dayjs(row.date).format("YYYY-MM-DD") }}</template>
        </el-table-column>
        <el-table-column label="Actions" width="260">
          <template #default="{ row }">
            <div class="flex flex-wrap gap-2">
              <el-button size="small" @click="setStatus(row.id, 'approve')">Approve</el-button>
              <el-button size="small" @click="setStatus(row.id, 'hold')">Hold</el-button>
              <el-popconfirm title="Delete comment?" @confirm="remove(row.id)">
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
          @current-change="(page) => { filters.page = page; fetchComments(); }"
        />
      </div>
    </div>
  </div>
</template>
