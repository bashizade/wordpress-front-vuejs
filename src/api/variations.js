import { wcClient } from "@/api/http";

const cleanParams = (params = {}) => {
  const query = {};
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== "") query[key] = value;
  });
  return query;
};

export const variationsApi = {
  list: async (productId, params = {}) =>
    (
      await wcClient.get(`/products/${productId}/variations`, {
        params: cleanParams(params)
      })
    ).data,
  get: async (productId, variationId) =>
    (await wcClient.get(`/products/${productId}/variations/${variationId}`)).data,
  create: async (productId, payload) =>
    (await wcClient.post(`/products/${productId}/variations`, payload)).data,
  update: async (productId, variationId, payload) =>
    (await wcClient.put(`/products/${productId}/variations/${variationId}`, payload)).data,
  remove: async (productId, variationId, force = true) =>
    (
      await wcClient.delete(`/products/${productId}/variations/${variationId}`, {
        params: { force }
      })
    ).data,
  batch: async (productId, payload) =>
    (await wcClient.post(`/products/${productId}/variations/batch`, payload)).data
};
