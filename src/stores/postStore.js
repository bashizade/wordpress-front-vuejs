import { computed, ref } from "vue";
import { defineStore } from "pinia";
import { ElMessage } from "element-plus";
import { postApi } from "@/api/wordpress";

export const usePostStore = defineStore("postStore", () => {
  const list = ref([]);
  const total = ref(0);
  const totalPages = ref(0);
  const loading = ref(false);
  const filters = ref({
    page: 1,
    per_page: 10,
    search: "",
    status: "any",
    _embed: true
  });

  const hasData = computed(() => list.value.length > 0);

  const fetchPosts = async () => {
    loading.value = true;
    try {
      const data = await postApi.list(filters.value);
      list.value = data.items;
      total.value = data.total;
      totalPages.value = data.totalPages;
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "Unable to fetch posts");
    } finally {
      loading.value = false;
    }
  };

  const createPost = async (payload) => {
    try {
      const created = await postApi.create(payload);
      ElMessage.success("Post created");
      await fetchPosts();
      return created;
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "Create failed");
      throw error;
    }
  };

  const updatePost = async (id, payload) => {
    try {
      await postApi.update(id, payload);
      ElMessage.success("Post updated");
      await fetchPosts();
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "Update failed");
      throw error;
    }
  };

  const deletePost = async (id) => {
    try {
      await postApi.remove(id, true);
      ElMessage.success("Post deleted");
      await fetchPosts();
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "Delete failed");
      throw error;
    }
  };

  const setFilters = async (nextFilters) => {
    filters.value = {
      ...filters.value,
      ...nextFilters
    };
    await fetchPosts();
  };

  return {
    list,
    total,
    totalPages,
    loading,
    filters,
    hasData,
    fetchPosts,
    createPost,
    updatePost,
    deletePost,
    setFilters
  };
});
