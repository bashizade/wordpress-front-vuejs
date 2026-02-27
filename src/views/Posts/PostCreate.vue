<script setup>
import { onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { ElMessage } from "element-plus";
import { categoryApi, tagApi } from "@/api/wordpress";
import { usePostStore } from "@/stores/postStore";
import PageHeader from "@/components/common/PageHeader.vue";
import PostEditorDynamicFields from "@/components/CustomFields/PostEditorDynamicFields.vue";

const router = useRouter();
const postStore = usePostStore();
const saving = ref(false);
const metaRef = ref(null);
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

const loadTerms = async () => {
  const [cats, tagList] = await Promise.all([
    categoryApi.list({ per_page: 100 }),
    tagApi.list({ per_page: 100 })
  ]);
  categories.value = cats.items;
  tags.value = tagList.items;
};

const save = async () => {
  saving.value = true;
  try {
    const created = await postStore.createPost(form);
    if (created?.id) {
      await metaRef.value?.saveMeta?.(created.id);
    }
    router.push({ name: "posts.list" });
  } catch {
    ElMessage.error("Failed to create post");
  } finally {
    saving.value = false;
  }
};

onMounted(loadTerms);
</script>

<template>
  <div>
    <PageHeader title="Create Post" subtitle="Add a new WordPress post" />
    <div class="panel p-4">
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
        <PostEditorDynamicFields ref="metaRef" post-type="post" />
      </el-form>
      <div class="mt-2 flex justify-end gap-2">
        <el-button @click="router.push({ name: 'posts.list' })">Cancel</el-button>
        <el-button type="primary" :loading="saving" @click="save">Create</el-button>
      </div>
    </div>
  </div>
</template>
