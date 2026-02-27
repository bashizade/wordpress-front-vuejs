import { authClient } from "@/api/http";

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || "";

export const adminMenuApi = {
  getMenu: async () => {
    const response = await authClient.get(`${apiBaseUrl}/wp-json/custom-admin/v1/menu`);
    return response.data?.items || [];
  }
};
