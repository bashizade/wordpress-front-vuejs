<script setup>
const props = defineProps({
  post: { type: Object, required: true }
});

const image = props.post._embedded?.["wp:featuredmedia"]?.[0]?.source_url || "https://via.placeholder.com/960x640?text=Blog";
</script>

<template>
  <article class="panel overflow-hidden transition duration-300 hover:-translate-y-1 hover:shadow-xl">
    <router-link :to="`/blog/${post.slug || post.id}`" class="block">
      <img :src="image" :alt="post.title?.rendered" class="h-56 w-full object-cover" loading="lazy" />
    </router-link>

    <div class="space-y-3 p-4">
      <router-link :to="`/blog/${post.slug || post.id}`" class="line-clamp-2 text-lg font-semibold" v-html="post.title?.rendered" />
      <p class="line-clamp-3 text-sm text-slate-500" v-html="post.excerpt?.rendered" />
      <router-link :to="`/blog/${post.slug || post.id}`" class="text-sm font-semibold text-sky-600">Read more</router-link>
    </div>
  </article>
</template>
