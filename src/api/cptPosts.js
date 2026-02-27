import { wpClient } from "@/api/http";

const buildQuery = (params = {}) => {
  const query = {};
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== "") {
      query[key] = value;
    }
  });
  return query;
};

export const cptPostsApi = {
  list: async (slug, params) => {
    const response = await wpClient.get(`/${slug}`, { params: buildQuery(params) });
    return {
      items: response.data,
      total: Number(response.headers["x-wp-total"] || 0),
      totalPages: Number(response.headers["x-wp-totalpages"] || 0)
    };
  },
  get: async (slug, id) => (await wpClient.get(`/${slug}/${id}`, { params: { context: "edit" } })).data,
  create: async (slug, payload) => (await wpClient.post(`/${slug}`, payload)).data,
  update: async (slug, id, payload) => (await wpClient.post(`/${slug}/${id}`, payload)).data,
  remove: async (slug, id, force = true) =>
    (await wpClient.delete(`/${slug}/${id}`, { params: { force } })).data,
  listTerms: async (taxonomyRestBase, params) =>
    (await wpClient.get(`/${taxonomyRestBase}`, { params: buildQuery(params) })).data
};
