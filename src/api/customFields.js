import { authClient } from "@/api/http";

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || "";

export const customFieldsApi = {
  getSchema: async () => {
    const response = await authClient.get(`${apiBaseUrl}/wp-json/custom-api/v1/custom-fields`);
    return response.data?.schema || [];
  },
  saveSchema: async (schema) => {
    const response = await authClient.post(`${apiBaseUrl}/wp-json/custom-api/v1/custom-fields`, {
      schema
    });
    return response.data;
  }
};
