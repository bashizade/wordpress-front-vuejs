import { authClient, wpClient } from "@/api/http";

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || "";
const endpoint = (path) => `${apiBaseUrl}/wp-json/custom-cpt/v1${path}`;

export const customCptApi = {
  list: async () => {
    const response = await authClient.get(endpoint("/list"));
    return response.data?.items || [];
  },
  create: async (payload) => {
    const response = await authClient.post(endpoint("/create"), payload);
    return response.data?.item;
  },
  update: async (slug, payload) => {
    const response = await authClient.post(endpoint(`/update/${slug}`), payload);
    return response.data?.item;
  },
  remove: async (slug) => {
    const response = await authClient.delete(endpoint(`/delete/${slug}`));
    return response.data;
  },
  reorder: async (order) => {
    const response = await authClient.post(endpoint("/reorder"), { order });
    return response.data?.items || [];
  },
  getTaxonomies: async () => {
    const response = await wpClient.get("/taxonomies");
    return response.data || {};
  },
  getTaxonomiesByType: async (postType) => {
    const response = await wpClient.get("/taxonomies", { params: { type: postType } });
    return response.data || {};
  }
};
