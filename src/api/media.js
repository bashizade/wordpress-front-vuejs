import { wpClient } from "@/api/http";

export const mediaApi = {
  list: async (params = {}) => (await wpClient.get("/media", { params })).data,
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
  }
};
