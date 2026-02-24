<script setup>
import { ref } from "vue";
import { ElMessage } from "element-plus";
import { mediaApi } from "@/api/media";

const model = defineModel({
  type: Object,
  default: () => ({
    image_id: null,
    gallery_ids: []
  })
});

const dialogOpen = ref(false);
const loading = ref(false);
const mediaItems = ref([]);

const loadMedia = async () => {
  loading.value = true;
  try {
    mediaItems.value = await mediaApi.list({ per_page: 100, page: 1 });
  } finally {
    loading.value = false;
  }
};

const openDialog = async () => {
  dialogOpen.value = true;
  await loadMedia();
};

const uploadMedia = async ({ file }) => {
  try {
    const uploaded = await mediaApi.upload(file);
    mediaItems.value.unshift(uploaded);
    ElMessage.success("Image uploaded");
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Upload failed");
  }
};

const setFeatured = (id) => {
  model.value = {
    ...model.value,
    image_id: id
  };
};

const toggleGallery = (id) => {
  const exists = model.value.gallery_ids.includes(id);
  const next = exists
    ? model.value.gallery_ids.filter((item) => item !== id)
    : [...model.value.gallery_ids, id];
  model.value = {
    ...model.value,
    gallery_ids: next
  };
};
</script>

<template>
  <div class="space-y-2">
    <div class="flex flex-wrap gap-2">
      <el-tag type="info">Featured: {{ model.image_id || "-" }}</el-tag>
      <el-tag type="success">Gallery: {{ model.gallery_ids.length }}</el-tag>
      <el-button size="small" @click="openDialog">Choose Images</el-button>
    </div>

    <el-dialog v-model="dialogOpen" title="Variation Images" width="900">
      <div class="mb-4">
        <el-upload :show-file-list="false" :http-request="uploadMedia" accept="image/*">
          <el-button type="primary">Upload</el-button>
        </el-upload>
      </div>
      <div v-loading="loading" class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div
          v-for="item in mediaItems"
          :key="item.id"
          class="rounded border p-2"
          :class="{ 'border-sky-500': model.image_id === item.id }"
        >
          <img :src="item.source_url" class="h-28 w-full rounded object-cover" alt="" />
          <div class="mt-2 flex gap-2">
            <el-button size="small" @click="setFeatured(item.id)">Featured</el-button>
            <el-button
              size="small"
              :type="model.gallery_ids.includes(item.id) ? 'success' : 'default'"
              @click="toggleGallery(item.id)"
            >
              Gallery
            </el-button>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>
