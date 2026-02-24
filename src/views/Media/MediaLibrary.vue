<script setup>
import { onMounted, reactive, ref } from "vue";
import { ElMessage } from "element-plus";
import PageHeader from "@/components/common/PageHeader.vue";
import { mediaApi } from "@/api/wordpress";

const loading = ref(false);
const list = ref([]);
const total = ref(0);
const filters = reactive({
  page: 1,
  per_page: 24,
  search: ""
});

const fetchMedia = async () => {
  loading.value = true;
  try {
    const response = await mediaApi.list(filters);
    list.value = response.items;
    total.value = response.total;
  } finally {
    loading.value = false;
  }
};

const onUploadRequest = async ({ file, onSuccess, onError }) => {
  try {
    const uploaded = await mediaApi.upload(file);
    onSuccess?.(uploaded);
    ElMessage.success("Media uploaded");
    await fetchMedia();
  } catch (error) {
    onError?.(error);
    ElMessage.error(error?.response?.data?.message || "Upload failed");
  }
};

const remove = async (id) => {
  await mediaApi.remove(id, true);
  ElMessage.success("Media removed");
  await fetchMedia();
};

onMounted(fetchMedia);
</script>

<template>
  <div>
    <PageHeader title="Media Library" subtitle="Upload and manage attachments">
      <el-upload :show-file-list="false" :http-request="onUploadRequest" accept="image/*,application/pdf">
        <el-button type="primary">Upload File</el-button>
      </el-upload>
    </PageHeader>

    <div class="panel mb-4 p-4">
      <div class="flex gap-3">
        <el-input v-model="filters.search" placeholder="Search media..." @keyup.enter="fetchMedia" />
        <el-button @click="fetchMedia">Search</el-button>
      </div>
    </div>

    <div class="panel p-4">
      <el-skeleton v-if="loading" :rows="8" animated />
      <div v-else class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-6">
        <div v-for="item in list" :key="item.id" class="rounded-xl border border-slate-200 p-2">
          <img
            :src="item.media_details?.sizes?.thumbnail?.source_url || item.source_url"
            alt=""
            class="h-24 w-full rounded object-cover"
          />
          <p class="mt-2 truncate text-xs">{{ item.title?.rendered || item.slug }}</p>
          <el-popconfirm title="Delete file?" @confirm="remove(item.id)">
            <template #reference>
              <el-button class="mt-2 w-full" size="small" type="danger">Delete</el-button>
            </template>
          </el-popconfirm>
        </div>
      </div>
      <div class="mt-4 flex justify-end">
        <el-pagination
          background
          layout="prev, pager, next"
          :current-page="filters.page"
          :page-size="filters.per_page"
          :total="total"
          @current-change="(page) => { filters.page = page; fetchMedia(); }"
        />
      </div>
    </div>
  </div>
</template>
