import { authClient, wpClient } from "@/api/http";

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || "";

const flattenMeta = (meta = {}) => {
  const normalized = {};
  Object.entries(meta).forEach(([key, value]) => {
    if (Array.isArray(value)) {
      normalized[key] = value.length <= 1 ? value[0] ?? "" : value;
    } else {
      normalized[key] = value;
    }
  });
  return normalized;
};

export const userMetaApi = {
  me: async () => {
    try {
      const response = await authClient.get(`${apiBaseUrl}/wp-json/custom-api/v1/user/meta/me`);
      return flattenMeta(response.data?.meta || {});
    } catch {
      const response = await wpClient.get("/users/me", { params: { context: "edit" } });
      return flattenMeta(response.data?.meta || {});
    }
  },
  get: async (userId) => {
    try {
      const response = await authClient.get(`${apiBaseUrl}/wp-json/custom-api/v1/user/meta/${userId}`);
      return flattenMeta(response.data?.meta || response.data || {});
    } catch {
      const response = await wpClient.get(`/users/${userId}`, {
        params: { context: "edit" }
      });
      return flattenMeta(response.data?.meta || {});
    }
  },
  update: async (userId, meta) => {
    try {
      const response = await authClient.post(`${apiBaseUrl}/wp-json/custom-api/v1/user/meta/update`, {
        user_id: userId,
        meta
      });
      return response.data;
    } catch {
      const response = await wpClient.put(`/users/${userId}`, {
        meta
      });
      return response.data;
    }
  },
  updateMe: async (meta) => {
    try {
      const response = await authClient.post(`${apiBaseUrl}/wp-json/custom-api/v1/user/meta/update`, {
        user_id: "me",
        meta
      });
      return response.data;
    } catch {
      const response = await wpClient.put("/users/me", {
        meta
      });
      return response.data;
    }
  }
};
