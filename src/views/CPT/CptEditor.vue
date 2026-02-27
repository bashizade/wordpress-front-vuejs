<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ElMessage } from "element-plus";
import PageHeader from "@/components/common/PageHeader.vue";
import { cptPostsApi } from "@/api/cptPosts";
import { customCptApi } from "@/api/customCpt";
import { mediaApi } from "@/api/wordpress";
import PostEditorDynamicFields from "@/components/CustomFields/PostEditorDynamicFields.vue";

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const saving = ref(false);
const definitions = ref([]);
const taxonomies = ref([]);
const taxonomyTerms = ref({});
const metaRef = ref(null);

const slug = computed(() => String(route.params.slug || ""));
const id = computed(() => (route.params.id ? Number(route.params.id) : null));
const isCreate = computed(() => !id.value);

const cptDefinition = computed(() => definitions.value.find((item) => item.slug === slug.value) || null);
const supports = computed(() => cptDefinition.value?.supports || ["title", "editor"]);
const titleText = computed(() => `${isCreate.value ? "Create" : "Edit"} ${cptDefinition.value?.singular || slug.value}`);

const form = reactive({
  title: "",
  content: "",
  excerpt: "",
  status: "draft",
  featured_media: null,
  taxonomies: {}
});

const resetForm = () => {
  form.title = "";
  form.content = "";
  form.excerpt = "";
  form.status = "draft";
  form.featured_media = null;
  form.taxonomies = {};
};

const loadDefinitions = async () => {
  try {
    definitions.value = await customCptApi.list();
  } catch {
    definitions.value = [];
  }
};

const loadTaxonomies = async () => {
  taxonomies.value = [];
  taxonomyTerms.value = {};
  try {
    const map = await customCptApi.getTaxonomiesByType(slug.value);
    const items = Object.values(map || {});
    taxonomies.value = items;
    for (const tax of items) {
      const restBase = tax.rest_base || tax.slug;
      if (!restBase) continue;
      const terms = await cptPostsApi.listTerms(restBase, { per_page: 100, hide_empty: false });
      taxonomyTerms.value[tax.slug] = Array.isArray(terms) ? terms : [];
      if (!Array.isArray(form.taxonomies[tax.slug])) {
        form.taxonomies[tax.slug] = [];
      }
    }
  } catch {
    // ignore taxonomy load errors
  }
};

const applyPost = (post) => {
  form.title = post.title?.raw || post.title?.rendered || "";
  form.content = post.content?.raw || post.content?.rendered || "";
  form.excerpt = post.excerpt?.raw || post.excerpt?.rendered || "";
  form.status = post.status || "draft";
  form.featured_media = post.featured_media || null;

  for (const tax of taxonomies.value) {
    form.taxonomies[tax.slug] = Array.isArray(post[tax.slug]) ? [...post[tax.slug]] : [];
  }
};

const load = async () => {
  if (!slug.value) return;
  loading.value = true;
  try {
    await loadDefinitions();
    await loadTaxonomies();
    if (id.value) {
      const post = await cptPostsApi.get(slug.value, id.value);
      applyPost(post);
      await metaRef.value?.load?.();
    } else {
      resetForm();
    }
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed to load item");
  } finally {
    loading.value = false;
  }
};

const uploadFeaturedImage = async (file) => {
  try {
    const media = await mediaApi.upload(file);
    form.featured_media = media.id;
    ElMessage.success("Featured image uploaded");
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Upload failed");
  }
};

const buildPayload = () => {
  const payload = {
    status: form.status
  };
  if (supports.value.includes("title")) payload.title = form.title;
  if (supports.value.includes("editor")) payload.content = form.content;
  if (supports.value.includes("excerpt")) payload.excerpt = form.excerpt;
  if (supports.value.includes("thumbnail")) payload.featured_media = form.featured_media || 0;

  for (const tax of taxonomies.value) {
    payload[tax.slug] = Array.isArray(form.taxonomies[tax.slug]) ? form.taxonomies[tax.slug] : [];
  }
  return payload;
};

const save = async () => {
  saving.value = true;
  try {
    const payload = buildPayload();
    let saved;
    if (isCreate.value) {
      saved = await cptPostsApi.create(slug.value, payload);
      if (saved?.id) {
        await metaRef.value?.saveMeta?.(saved.id);
      }
    } else {
      saved = await cptPostsApi.update(slug.value, id.value, payload);
      await metaRef.value?.saveMeta?.(id.value);
    }
    ElMessage.success("Saved successfully");
    router.push({ name: "cpt.list", params: { slug: slug.value } });
    return saved;
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Save failed");
    return null;
  } finally {
    saving.value = false;
  }
};

watch(
  () => [route.params.slug, route.params.id],
  async () => {
    await load();
  }
);

onMounted(load);
</script>

<template>
  <div>
    <PageHeader :title="titleText" subtitle="Dynamic CPT editor based on supports and taxonomies" />

    <div v-if="loading" class="panel p-4"><el-skeleton :rows="8" animated /></div>

    <div v-else class="panel p-4">
      <el-form label-position="top">
        <el-form-item v-if="supports.includes('title')" label="Title">
          <el-input v-model="form.title" />
        </el-form-item>

        <el-form-item v-if="supports.includes('editor')" label="Content">
          <el-input v-model="form.content" type="textarea" :rows="10" />
        </el-form-item>

        <el-form-item v-if="supports.includes('excerpt')" label="Excerpt">
          <el-input v-model="form.excerpt" type="textarea" :rows="4" />
        </el-form-item>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
          <el-form-item label="Status">
            <el-select v-model="form.status">
              <el-option value="draft" label="Draft" />
              <el-option value="publish" label="Publish" />
              <el-option value="pending" label="Pending" />
            </el-select>
          </el-form-item>

          <el-form-item v-if="supports.includes('thumbnail')" label="Featured Image">
            <div class="space-y-2">
              <el-upload
                :auto-upload="false"
                :show-file-list="false"
                accept="image/*"
                :on-change="({ raw }) => uploadFeaturedImage(raw)"
              >
                <el-button plain>Upload</el-button>
              </el-upload>
              <el-input v-model="form.featured_media" placeholder="Media ID" />
            </div>
          </el-form-item>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <el-form-item v-for="tax in taxonomies" :key="tax.slug" :label="tax.name">
            <el-select v-model="form.taxonomies[tax.slug]" multiple filterable>
              <el-option
                v-for="term in taxonomyTerms[tax.slug] || []"
                :key="term.id"
                :label="term.name"
                :value="term.id"
              />
            </el-select>
          </el-form-item>
        </div>

        <PostEditorDynamicFields ref="metaRef" :post-id="id" :post-type="slug" title="Custom Meta Fields" />
      </el-form>

      <div class="mt-4 flex justify-end gap-2">
        <el-button @click="router.push({ name: 'cpt.list', params: { slug } })">Cancel</el-button>
        <el-button type="primary" :loading="saving" @click="save">Save</el-button>
      </div>
    </div>
  </div>
</template>
