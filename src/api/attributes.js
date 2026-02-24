import { wcClient } from "@/api/http";

const cleanParams = (params = {}) => {
  const query = {};
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== "") {
      query[key] = value;
    }
  });
  return query;
};

export const attributesApi = {
  list: async (params = {}) =>
    (await wcClient.get("/products/attributes", { params: cleanParams(params) })).data,
  get: async (id) => (await wcClient.get(`/products/attributes/${id}`)).data,
  create: async (payload) => (await wcClient.post("/products/attributes", payload)).data,
  update: async (id, payload) => (await wcClient.put(`/products/attributes/${id}`, payload)).data,
  remove: async (id, force = true) =>
    (await wcClient.delete(`/products/attributes/${id}`, { params: { force } })).data,
  listTerms: async (attributeId, params = {}) =>
    (
      await wcClient.get(`/products/attributes/${attributeId}/terms`, {
        params: cleanParams(params)
      })
    ).data,
  getTerm: async (attributeId, termId) =>
    (await wcClient.get(`/products/attributes/${attributeId}/terms/${termId}`)).data,
  createTerm: async (attributeId, payload) =>
    (await wcClient.post(`/products/attributes/${attributeId}/terms`, payload)).data,
  updateTerm: async (attributeId, termId, payload) =>
    (await wcClient.put(`/products/attributes/${attributeId}/terms/${termId}`, payload)).data,
  removeTerm: async (attributeId, termId, force = true) =>
    (
      await wcClient.delete(`/products/attributes/${attributeId}/terms/${termId}`, {
        params: { force }
      })
    ).data
};
