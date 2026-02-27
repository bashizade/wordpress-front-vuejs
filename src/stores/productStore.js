import { computed, ref } from "vue";
import { defineStore } from "pinia";
import { ElMessage } from "element-plus";
import { productApi } from "@/api/woocommerce";

export const useProductStore = defineStore("productStore", () => {
  const list = ref([]);
  const currentProduct = ref(null);
  const loading = ref(false);
  const detailLoading = ref(false);
  const filters = ref({
    page: 1,
    per_page: 10,
    search: "",
    status: "publish"
  });
  const pagination = ref({
    total: 0
  });

  const hasProducts = computed(() => list.value.length > 0);
  const productAttributes = computed(() => currentProduct.value?.attributes || []);

  const fetchProducts = async () => {
    loading.value = true;
    try {
      const data = await productApi.list(filters.value);
      list.value = data;
      if (filters.value.page === 1 && data.length < filters.value.per_page) {
        pagination.value.total = data.length;
      } else if (data.length === filters.value.per_page) {
        pagination.value.total = filters.value.page * filters.value.per_page + 1;
      }
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "Unable to fetch products");
    } finally {
      loading.value = false;
    }
  };

  const fetchProduct = async (id) => {
    detailLoading.value = true;
    try {
      currentProduct.value = await productApi.get(id);
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "Unable to fetch product");
      throw error;
    } finally {
      detailLoading.value = false;
    }
    return currentProduct.value;
  };

  const createProduct = async (payload) => {
    try {
      const created = await productApi.create(payload);
      ElMessage.success("Product created");
      await fetchProducts();
      return created;
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "Create failed");
      throw error;
    }
  };

  const updateProduct = async (id, payload) => {
    try {
      currentProduct.value = await productApi.update(id, payload);
      ElMessage.success("Product updated");
      await fetchProducts();
      return currentProduct.value;
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "Update failed");
      throw error;
    }
  };

  const deleteProduct = async (id) => {
    try {
      await productApi.remove(id, true);
      ElMessage.success("Product removed");
      await fetchProducts();
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
    await fetchProducts();
  };

  const updateProductAttributes = async (id, attributes) => {
    const payload = {
      attributes
    };
    currentProduct.value = await productApi.update(id, payload);
    ElMessage.success("Product attributes saved");
    return currentProduct.value;
  };

  return {
    list,
    currentProduct,
    loading,
    detailLoading,
    filters,
    pagination,
    hasProducts,
    productAttributes,
    fetchProducts,
    fetchProduct,
    createProduct,
    updateProduct,
    deleteProduct,
    setFilters,
    updateProductAttributes
  };
});
