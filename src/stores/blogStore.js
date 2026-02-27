import { ref } from "vue";
import { defineStore } from "pinia";
import { api } from "@/services/api";

export const useBlogStore = defineStore("blogStore", () => {
  const posts = ref([]);
  const total = ref(0);
  const totalPages = ref(0);
  const loading = ref(false);
  const currentPost = ref(null);
  const comments = ref([]);

  const fetchPosts = async (params = {}) => {
    loading.value = true;
    try {
      const data = await api.getPosts(params);
      posts.value = data.items;
      total.value = data.total;
      totalPages.value = data.totalPages;
      return posts.value;
    } finally {
      loading.value = false;
    }
  };

  const fetchPost = async (idOrSlug) => {
    loading.value = true;
    try {
      currentPost.value = await api.getPost(idOrSlug);
      comments.value = currentPost.value?.id ? await api.getComments(currentPost.value.id) : [];
      return currentPost.value;
    } finally {
      loading.value = false;
    }
  };

  const addComment = async (payload) => {
    const created = await api.addComment(payload);
    comments.value = [created, ...comments.value];
    return created;
  };

  return {
    posts,
    total,
    totalPages,
    loading,
    currentPost,
    comments,
    fetchPosts,
    fetchPost,
    addComment
  };
});
