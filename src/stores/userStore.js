import { reactive, ref } from "vue";
import { defineStore } from "pinia";
import { ElMessage } from "element-plus";
import { usersApi } from "@/api/users";
import { userMetaApi } from "@/api/userMeta";
import { customerApi } from "@/api/woocommerce";
import i18n from "@/i18n";

export const useUserStore = defineStore("userStore", () => {
  const users = ref([]);
  const currentUser = ref(null);
  const currentMeta = ref({});
  const customerProfile = ref(null);
  const loading = ref(false);
  const saving = ref(false);
  const filters = reactive({
    page: 1,
    per_page: 10,
    search: "",
    orderby: "registered_date",
    order: "desc"
  });
  const pagination = reactive({
    total: 0,
    totalPages: 0
  });

  const fetchUsers = async () => {
    loading.value = true;
    try {
      const response = await usersApi.list(filters);
      users.value = response.items;
      pagination.total = response.total;
      pagination.totalPages = response.totalPages;
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || i18n.global.t("users.fetchError"));
    } finally {
      loading.value = false;
    }
  };

  const setFilters = async (next) => {
    Object.assign(filters, next);
    await fetchUsers();
  };

  const fetchUser = async (id) => {
    currentUser.value = await usersApi.get(id);
    currentMeta.value = await userMetaApi.get(id);
    return { user: currentUser.value, meta: currentMeta.value };
  };

  const fetchMe = async () => {
    currentUser.value = await usersApi.me();
    currentMeta.value = await userMetaApi.me();
    return { user: currentUser.value, meta: currentMeta.value };
  };

  const updateMe = async ({ user, meta }) => {
    saving.value = true;
    try {
      if (user && Object.keys(user).length) {
        await usersApi.updateMe(user);
      }
      if (meta) {
        await userMetaApi.updateMe(meta);
      }
      await fetchMe();
      ElMessage.success("پروفایل ذخیره شد");
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "خطا در ذخیره پروفایل");
      throw error;
    } finally {
      saving.value = false;
    }
  };

  const fetchCustomerProfile = async () => {
    if (!currentUser.value?.id) await fetchMe();
    try {
      customerProfile.value = await customerApi.get(currentUser.value.id);
    } catch {
      const customers = await customerApi.list({
        email: currentUser.value.email,
        per_page: 1
      });
      customerProfile.value = customers?.[0] || {
        id: currentUser.value.id,
        billing: {},
        shipping: {}
      };
    }
    return customerProfile.value;
  };

  const updateAddresses = async (payload) => {
    if (!currentUser.value?.id) await fetchMe();
    if (customerProfile.value?.id) {
      customerProfile.value = await customerApi.update(customerProfile.value.id, payload);
    } else {
      customerProfile.value = await customerApi.create({
        email: currentUser.value.email,
        first_name: currentUser.value.first_name || "",
        last_name: currentUser.value.last_name || "",
        ...payload
      });
    }
    ElMessage.success("آدرس ها ذخیره شد");
    return customerProfile.value;
  };

  const createUser = async ({ user, meta }) => {
    saving.value = true;
    try {
      const created = await usersApi.create(user);
      if (meta && Object.keys(meta).length > 0) {
        await userMetaApi.update(created.id, meta);
      }
      ElMessage.success(i18n.global.t("users.saved"));
      await fetchUsers();
      return created;
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || i18n.global.t("users.saveError"));
      throw error;
    } finally {
      saving.value = false;
    }
  };

  const updateUser = async (id, { user, meta }) => {
    saving.value = true;
    try {
      const updated = await usersApi.update(id, user);
      if (meta) {
        await userMetaApi.update(id, meta);
      }
      ElMessage.success(i18n.global.t("users.saved"));
      await fetchUsers();
      return updated;
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || i18n.global.t("users.saveError"));
      throw error;
    } finally {
      saving.value = false;
    }
  };

  const removeUser = async (id) => {
    await usersApi.remove(id, null, true);
    ElMessage.success(i18n.global.t("users.deleted"));
    await fetchUsers();
  };

  return {
    users,
    currentUser,
    currentMeta,
    customerProfile,
    loading,
    saving,
    filters,
    pagination,
    fetchUsers,
    setFilters,
    fetchUser,
    fetchMe,
    updateMe,
    fetchCustomerProfile,
    updateAddresses,
    createUser,
    updateUser,
    removeUser
  };
});
