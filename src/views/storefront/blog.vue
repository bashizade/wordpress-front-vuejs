<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { ElMessage } from "element-plus";
import SiteShell from "@/components/storefront/SiteShell.vue";
import Breadcrumb from "@/components/storefront/Breadcrumb.vue";
import Loader from "@/components/storefront/Loader.vue";
import BlogCard from "@/components/storefront/BlogCard.vue";
import { useBlogStore } from "@/stores/blogStore";

const route = useRoute();
const blogStore = useBlogStore();
const related = ref([]);

const form = reactive({
  author_name: "",
  author_email: "",
  content: ""
});

const current = computed(() => blogStore.currentPost || {});

const load = async () => {
  await blogStore.fetchPost(route.params.slug);
  document.title = `${current.value.title?.rendered ? current.value.title.rendered.replace(/<[^>]+>/g, '') : 'Blog'} | Vue Store`;

  const categories = current.value.categories || [];
  if (categories.length) {
    const all = await blogStore.fetchPosts({ categories: categories[0], per_page: 4 });
    related.value = all.filter((item) => item.id !== current.value.id).slice(0, 3);
  } else {
    related.value = [];
  }
};

const submitComment = async () => {
  if (!current.value.id) return;
  try {
    await blogStore.addComment({
      post: current.value.id,
      author_name: form.author_name,
      author_email: form.author_email,
      content: form.content
    });
    form.author_name = "";
    form.author_email = "";
    form.content = "";
    ElMessage.success("Comment submitted");
  } catch (error) {
    ElMessage.error(error?.response?.data?.message || "Failed to submit comment");
  }
};

watch(
  () => route.params.slug,
  () => load()
);

onMounted(load);
</script>

<template>
  <SiteShell>
    <Loader v-if="blogStore.loading" :rows="8" />

    <template v-else>
      <Breadcrumb :items="[{ label: 'Home', to: '/' }, { label: 'Blog', to: '/blogs' }, { label: current.title?.rendered?.replace(/<[^>]+>/g, '') || 'Post' }]" />

      <article class="panel p-6">
        <img
          v-if="current._embedded?.['wp:featuredmedia']?.[0]?.source_url"
          :src="current._embedded['wp:featuredmedia'][0].source_url"
          :alt="current.title?.rendered"
          class="mb-5 h-[420px] w-full rounded object-cover"
        />
        <h1 class="mb-3 text-3xl font-black" v-html="current.title?.rendered" />
        <div class="prose max-w-none" v-html="current.content?.rendered" />
      </article>

      <section class="mt-10">
        <h2 class="mb-4 text-2xl font-black">Comments</h2>
        <div v-if="!blogStore.comments.length" class="panel p-4 text-slate-500">No comments yet.</div>
        <div v-else class="space-y-3">
          <div v-for="comment in blogStore.comments" :key="comment.id" class="panel p-4">
            <p class="text-sm font-semibold">{{ comment.author_name }}</p>
            <p class="text-sm text-slate-600" v-html="comment.content?.rendered || comment.content" />
          </div>
        </div>

        <div class="panel mt-5 p-4">
          <h3 class="mb-3 text-lg font-bold">Leave a comment</h3>
          <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <el-input v-model="form.author_name" placeholder="Name" />
            <el-input v-model="form.author_email" placeholder="Email" />
          </div>
          <el-input v-model="form.content" type="textarea" :rows="4" class="mt-3" placeholder="Comment" />
          <el-button type="primary" class="mt-3" @click="submitComment">Submit</el-button>
        </div>
      </section>

      <section class="mt-10">
        <h2 class="mb-4 text-2xl font-black">Related posts</h2>
        <div class="grid gap-4 md:grid-cols-3">
          <BlogCard v-for="post in related" :key="post.id" :post="post" />
        </div>
      </section>
    </template>
  </SiteShell>
</template>
