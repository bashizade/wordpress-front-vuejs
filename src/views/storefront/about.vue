<script setup>
import { onMounted, ref } from "vue";
import SiteShell from "@/components/storefront/SiteShell.vue";
import Breadcrumb from "@/components/storefront/Breadcrumb.vue";
import Loader from "@/components/storefront/Loader.vue";
import { useSiteStore } from "@/stores/siteStore";
import { useBlogStore } from "@/stores/blogStore";

document.title = "About Us | Vue Store";

const siteStore = useSiteStore();
const blogStore = useBlogStore();
const loading = ref(true);

onMounted(async () => {
  try {
    await siteStore.fetchAbout();
    await blogStore.fetchPosts({ per_page: 3 });
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <SiteShell>
    <Breadcrumb :items="[{ label: 'Home', to: '/' }, { label: 'About' }]" />

    <Loader v-if="loading" :rows="8" />

    <template v-else>
      <section class="panel p-6">
        <h1 class="mb-4 text-3xl font-black">About Us</h1>
        <div class="prose max-w-none" v-html="siteStore.aboutPage?.content?.rendered || '<p>No content available.</p>'" />
      </section>

      <section class="mt-8 grid gap-4 md:grid-cols-3">
        <div class="panel p-4">
          <h2 class="mb-2 text-lg font-black">Our Mission</h2>
          <p class="text-slate-500">Deliver reliable products and a premium eCommerce experience.</p>
        </div>
        <div class="panel p-4">
          <h2 class="mb-2 text-lg font-black">Our Vision</h2>
          <p class="text-slate-500">Build a trusted digital commerce brand for long-term growth.</p>
        </div>
        <div class="panel p-4">
          <h2 class="mb-2 text-lg font-black">Company History</h2>
          <p class="text-slate-500">A growing team focused on quality, service, and innovation.</p>
        </div>
      </section>

      <section class="mt-8">
        <h2 class="mb-4 text-2xl font-black">Team & Stories</h2>
        <div class="grid gap-4 md:grid-cols-3">
          <div v-for="post in blogStore.posts" :key="post.id" class="panel p-4">
            <h3 class="line-clamp-2 font-semibold" v-html="post.title?.rendered" />
            <p class="mt-2 line-clamp-3 text-sm text-slate-500" v-html="post.excerpt?.rendered" />
          </div>
        </div>
      </section>
    </template>
  </SiteShell>
</template>
