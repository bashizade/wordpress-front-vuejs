import { wcClient } from "@/api/http";

const cleanParams = (params = {}) => {
  const query = {};
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== "") query[key] = value;
  });
  return query;
};

export const ordersApi = {
  listByCustomer: async (customerId, params = {}) =>
    (
      await wcClient.get("/orders", {
        params: cleanParams({
          customer: customerId,
          per_page: 20,
          orderby: "date",
          order: "desc",
          ...params
        })
      })
    ).data,
  getById: async (orderId) => (await wcClient.get(`/orders/${orderId}`)).data
};
