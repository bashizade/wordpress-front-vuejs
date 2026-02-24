import { wcClient } from "@/api/http";

const buildQuery = (params = {}) => {
  const query = {};
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== "") {
      query[key] = value;
    }
  });
  return query;
};

export const productApi = {
  list: async (params) => (await wcClient.get("/products", { params: buildQuery(params) })).data,
  get: async (id) => (await wcClient.get(`/products/${id}`)).data,
  create: async (payload) => (await wcClient.post("/products", payload)).data,
  update: async (id, payload) => (await wcClient.put(`/products/${id}`, payload)).data,
  remove: async (id, force = true) =>
    (await wcClient.delete(`/products/${id}`, { params: { force } })).data
};

export const productCategoryApi = {
  list: async (params) =>
    (await wcClient.get("/products/categories", { params: buildQuery(params) })).data,
  create: async (payload) => (await wcClient.post("/products/categories", payload)).data,
  update: async (id, payload) => (await wcClient.put(`/products/categories/${id}`, payload)).data,
  remove: async (id, force = true) =>
    (await wcClient.delete(`/products/categories/${id}`, { params: { force } })).data
};

export const orderApi = {
  list: async (params) => (await wcClient.get("/orders", { params: buildQuery(params) })).data,
  get: async (id) => (await wcClient.get(`/orders/${id}`)).data,
  create: async (payload) => (await wcClient.post("/orders", payload)).data,
  update: async (id, payload) => (await wcClient.put(`/orders/${id}`, payload)).data,
  remove: async (id, force = true) =>
    (await wcClient.delete(`/orders/${id}`, { params: { force } })).data
};

export const customerApi = {
  list: async (params) => (await wcClient.get("/customers", { params: buildQuery(params) })).data,
  get: async (id) => (await wcClient.get(`/customers/${id}`)).data,
  create: async (payload) => (await wcClient.post("/customers", payload)).data,
  update: async (id, payload) => (await wcClient.put(`/customers/${id}`, payload)).data,
  remove: async (id, force = true) =>
    (await wcClient.delete(`/customers/${id}`, { params: { force } })).data
};

export const couponApi = {
  list: async (params) => (await wcClient.get("/coupons", { params: buildQuery(params) })).data,
  get: async (id) => (await wcClient.get(`/coupons/${id}`)).data,
  create: async (payload) => (await wcClient.post("/coupons", payload)).data,
  update: async (id, payload) => (await wcClient.put(`/coupons/${id}`, payload)).data,
  remove: async (id, force = true) =>
    (await wcClient.delete(`/coupons/${id}`, { params: { force } })).data
};
