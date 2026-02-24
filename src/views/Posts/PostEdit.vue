<script setup>
import { onMounted, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ElMessage } from "element-plus";
import { categoryApi, postApi, tagApi } from "@/api/wordpress";
import PageHeader from "@/components/common/PageHeader.vue";

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const saving = ref(false);
const categories = ref([]);
const tags = ref([]);

const form = reactive({
  title: "",
  content: "",
  excerpt: "",
  status: "draft",
  categories: [],
  tags: []
});

const load = async () => {
  loading.value = true;
  try {
    const [post, cats, tagList] = await Promise.all([
      postApi.get(route.params.id),
      categoryApi.list({ per_page: 100 }),
      tagApi.list({ per_page: 100 })
    ]);

    categories.value = cats.items;
    tags.value = tagList.items;
    form.title = post.title?.rendered || "";
    form.content = post.content?.raw || post.content?.rendered || "";
    form.excerpt = post.excerpt?.raw || post.excerpt?.rendered || "";
    form.status = post.status;
    form.categories = post.categories || [];
    form.tags = post.tags || [];
  } finally {
    loading.value = false;
  }
};

const save = async () => {
  saving.value = true;
  try {
    await postApi.update(route.params.id, form);
    ElMessage.success("Post updated");
    router.push({ name: "posts.list" });
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed to update post");
  } finally {
    saving.value = false;
  }
};

onMounted(load);
</script>

<template>
  <div>
    <PageHeader title="Edit Post" subtitle="Update post content and metadata" />
    <div v-if="loading" class="panel p-4"><el-skeleton :rows="8" animated /></div>
    <div v-else class="panel p-4">
      <el-form label-position="top">
        <el-form-item label="Title">
          <el-input v-model="form.title" />
        </el-form-item>
        <el-form-item label="Excerpt">
          <el-input v-model="form.excerpt" type="textarea" :rows="3" />
        </el-form-item>
        <el-form-item label="Content">
          <el-input v-model="form.content" type="textarea" :rows="10" />
        </el-form-item>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
          <el-form-item label="Status">
            <el-select v-model="form.status">
              <el-option value="draft" label="Draft" />
              <el-option value="publish" label="Publish" />
              <el-option value="pending" label="Pending" />
            </el-select>
          </el-form-item>
          <el-form-item label="Categories">
            <el-select v-model="form.categories" multiple filterable>
              <el-option v-for="item in categories" :key="item.id" :label="item.name" :value="item.id" />
            </el-select>
          </el-form-item>
          <el-form-item label="Tags">
            <el-select v-model="form.tags" multiple filterable>
              <el-option v-for="item in tags" :key="item.id" :label="item.name" :value="item.id" />
            </el-select>
          </el-form-item>
        </div>
      </el-form>
      <div class="mt-2 flex justify-end gap-2">
        <el-button @click="router.push({ name: 'posts.list' })">Cancel</el-button>
        <el-button type="primary" :loading="saving" @click="save">Update</el-button>
      </div>
    </div>
  </div>
</template>
