<script setup>
import { onMounted, reactive } from "vue";
import SiteShell from "@/components/storefront/SiteShell.vue";
import Breadcrumb from "@/components/storefront/Breadcrumb.vue";
import BlogCard from "@/components/storefront/BlogCard.vue";
import Pagination from "@/components/storefront/Pagination.vue";
import Loader from "@/components/storefront/Loader.vue";
import { useBlogStore } from "@/stores/blogStore";
import { useSiteStore } from "@/stores/siteStore";

document.title = "Blog | Vue Store";

const blogStore = useBlogStore();
const siteStore = useSiteStore();

const filters = reactive({
  page: 1,
  per_page: 9,
  categories: ""
});

const load = async () => {
  await blogStore.fetchPosts({
    page: filters.page,
    per_page: filters.per_page,
    categories: filters.categories || undefined
  });
};

onMounted(async () => {
  await siteStore.fetchCategories();
  await load();
});
</script>

<template>
  <SiteShell>
    <Breadcrumb :items="[{ label: 'Home', to: '/' }, { label: 'Blog' }]" />

    <div class="panel mb-4 p-4">
      <div class="flex flex-wrap items-center gap-3">
        <el-select v-model="filters.categories" clearable placeholder="Category" class="w-full max-w-xs" @change="load">
          <el-option v-for="cat in siteStore.postCategories" :key="cat.id" :label="cat.name" :value="cat.id" />
        </el-select>
      </div>
    </div>

    <Loader v-if="blogStore.loading" :rows="6" />
    <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <BlogCard v-for="post in blogStore.posts" :key="post.id" :post="post" />
    </div>

    <Pagination
      :current-page="filters.page"
      :page-size="filters.per_page"
      :total="blogStore.total"
      @change="(page) => { filters.page = page; load(); }"
    />
  </SiteShell>
</template>
