import { wpClient } from "@/api/http";

const cleanParams = (params = {}) => {
  const query = {};
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== "") query[key] = value;
  });
  return query;
};

const parsePaged = (response) => ({
  items: response.data,
  total: Number(response.headers["x-wp-total"] || 0),
  totalPages: Number(response.headers["x-wp-totalpages"] || 0)
});

export const usersApi = {
  list: async (params = {}) => {
    const response = await wpClient.get("/users", {
      params: cleanParams({
        context: "edit",
        ...params
      })
    });
    return parsePaged(response);
  },
  get: async (id) =>
    (
      await wpClient.get(`/users/${id}`, {
        params: { context: "edit" }
      })
    ).data,
  create: async (payload) => (await wpClient.post("/users", payload)).data,
  update: async (id, payload) => (await wpClient.put(`/users/${id}`, payload)).data,
  me: async () => (await wpClient.get("/users/me", { params: { context: "edit" } })).data,
  updateMe: async (payload) => (await wpClient.put("/users/me", payload)).data,
  remove: async (id, reassign = null, force = true) =>
    (
      await wpClient.delete(`/users/${id}`, {
        params: { reassign, force }
      })
    ).data
};
