import { authClient } from "@/api/http";

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || "";

const endpoint = (path) => `${apiBaseUrl}/wp-json/custom-fields/v1${path}`;

export const postCustomFieldsApi = {
  getPostTypes: async () => {
    const response = await authClient.get(endpoint("/post-types"));
    return response.data?.post_types || [];
  },
  getFieldsByPostType: async (postType) => {
    const response = await authClient.get(endpoint("/fields"), { params: { post_type: postType } });
    return response.data?.fields || [];
  },
  getAllFields: async () => {
    const response = await authClient.get(endpoint("/fields/all"));
    return response.data?.fields || [];
  },
  saveAllFields: async (fields) => {
    const response = await authClient.post(endpoint("/fields/all"), { fields });
    return response.data?.fields || [];
  },
  getMeta: async (postId) => {
    const response = await authClient.get(endpoint(`/meta/${postId}`));
    return response.data?.meta || {};
  },
  saveMeta: async (postId, meta) => {
    const response = await authClient.post(endpoint(`/meta/${postId}`), { meta });
    return response.data;
  }
};