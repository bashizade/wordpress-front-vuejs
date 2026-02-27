<script setup>
import { reactive, ref } from "vue";
import { ElMessage } from "element-plus";
import { mediaApi } from "@/api/wordpress";

const model = defineModel({
  type: Object,
  default: () => ({
    id: null,
    url: ""
  })
});

const dialogOpen = ref(false);
const loading = ref(false);
const items = ref([]);
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
    items.value = response.items || [];
    total.value = response.total || 0;
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed to load media");
  } finally {
    loading.value = false;
  }
};

const openDialog = async () => {
  dialogOpen.value = true;
  await fetchMedia();
};

const selectImage = (item) => {
  model.value = {
    id: item.id,
    url: item.source_url || item.media_details?.sizes?.thumbnail?.source_url || ""
  };
  dialogOpen.value = false;
};

const clearImage = () => {
  model.value = {
    id: null,
    url: ""
  };
};

const uploadMedia = async ({ file, onSuccess, onError }) => {
  try {
    const uploaded = await mediaApi.upload(file);
    onSuccess?.(uploaded);
    ElMessage.success("Image uploaded");
    model.value = {
      id: uploaded.id,
      url: uploaded.source_url || ""
    };
    await fetchMedia();
  } catch (error) {
    onError?.(error);
    ElMessage.error(error?.response?.data?.message || "Upload failed");
  }
};
</script>

<template>
  <div class="space-y-2">
    <el-button plain @click="openDialog">Upload / Select Featured Image</el-button>

    <div v-if="model.url" class="space-y-2">
      <el-image :src="model.url" fit="cover" class="h-28 w-28 rounded border" />
      <div class="text-xs text-slate-500">Media ID: {{ model.id }}</div>
      <el-button size="small" type="danger" plain @click="clearImage">Remove</el-button>
    </div>

    <el-dialog v-model="dialogOpen" title="Media Library" width="980px">
      <div class="mb-4 flex flex-wrap items-center gap-3">
        <el-input v-model="filters.search" placeholder="Search media..." @keyup.enter="fetchMedia" />
        <el-button @click="fetchMedia">Search</el-button>
        <el-upload :show-file-list="false" :http-request="uploadMedia" accept="image/*">
          <el-button type="primary">Upload New Image</el-button>
        </el-upload>
      </div>

      <div v-loading="loading" class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-6">
        <button
          v-for="item in items"
          :key="item.id"
          type="button"
          class="rounded border p-2 text-left transition"
          :class="model.id === item.id ? 'border-sky-500 ring-1 ring-sky-500' : 'border-slate-200 hover:border-sky-300'"
          @click="selectImage(item)"
        >
          <img
            :src="item.media_details?.sizes?.thumbnail?.source_url || item.source_url"
            class="h-24 w-full rounded object-cover"
            alt=""
          />
          <p class="mt-2 truncate text-xs">{{ item.title?.rendered || item.slug || `#${item.id}` }}</p>
        </button>
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
    </el-dialog>
  </div>
</template>
