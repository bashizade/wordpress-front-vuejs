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

const parsePagedResponse = (response) => ({
  items: response.data,
  total: Number(response.headers["x-wp-total"] || 0),
  totalPages: Number(response.headers["x-wp-totalpages"] || 0)
});

export const postApi = {
  list: async (params) => {
    const response = await wpClient.get("/posts", { params: buildQuery(params) });
    return parsePagedResponse(response);
  },
  get: async (id) => (await wpClient.get(`/posts/${id}`)).data,
  create: async (payload) => (await wpClient.post("/posts", payload)).data,
  update: async (id, payload) => (await wpClient.post(`/posts/${id}`, payload)).data,
  remove: async (id, force = true) =>
    (await wpClient.delete(`/posts/${id}`, { params: { force } })).data
};

export const pageApi = {
  list: async (params) => {
    const response = await wpClient.get("/pages", { params: buildQuery(params) });
    return parsePagedResponse(response);
  },
  get: async (id) => (await wpClient.get(`/pages/${id}`)).data,
  create: async (payload) => (await wpClient.post("/pages", payload)).data,
  update: async (id, payload) => (await wpClient.post(`/pages/${id}`, payload)).data,
  remove: async (id, force = true) =>
    (await wpClient.delete(`/pages/${id}`, { params: { force } })).data
};

export const mediaApi = {
  list: async (params) => {
    const response = await wpClient.get("/media", { params: buildQuery(params) });
    return parsePagedResponse(response);
  },
  get: async (id) => (await wpClient.get(`/media/${id}`)).data,
  upload: async (file, title = "") => {
    const ext = (file.name?.split(".").pop() || "bin").toLowerCase();
    const safeBase = (file.name || "upload")
      .replace(/\.[^/.]+$/, "")
      .replace(/[^a-zA-Z0-9_-]/g, "_")
      .replace(/_+/g, "_")
      .replace(/^_+|_+$/g, "");
    const safeName = `${safeBase || "upload"}.${ext}`;
    const response = await wpClient.post("/media", file, {
      headers: {
        "Content-Disposition": `attachment; filename="${safeName}"`,
        "Content-Type": file.type || "application/octet-stream"
      }
    });
    const media = response.data;
    if (title) {
      return (await wpClient.post(`/media/${media.id}`, { title })).data;
    }
    return media;
  },
  remove: async (id, force = true) =>
    (await wpClient.delete(`/media/${id}`, { params: { force } })).data
};

export const categoryApi = {
  list: async (params) => {
    const response = await wpClient.get("/categories", { params: buildQuery(params) });
    return parsePagedResponse(response);
  },
  create: async (payload) => (await wpClient.post("/categories", payload)).data,
  update: async (id, payload) => (await wpClient.post(`/categories/${id}`, payload)).data,
  remove: async (id, force = true) =>
    (await wpClient.delete(`/categories/${id}`, { params: { force } })).data
};

export const tagApi = {
  list: async (params) => {
    const response = await wpClient.get("/tags", { params: buildQuery(params) });
    return parsePagedResponse(response);
  },
  create: async (payload) => (await wpClient.post("/tags", payload)).data,
  update: async (id, payload) => (await wpClient.post(`/tags/${id}`, payload)).data,
  remove: async (id, force = true) =>
    (await wpClient.delete(`/tags/${id}`, { params: { force } })).data
};

export const commentApi = {
  list: async (params) => {
    const response = await wpClient.get("/comments", { params: buildQuery(params) });
    return parsePagedResponse(response);
  },
  update: async (id, payload) => (await wpClient.post(`/comments/${id}`, payload)).data,
  remove: async (id, force = true) =>
    (await wpClient.delete(`/comments/${id}`, { params: { force } })).data
};
