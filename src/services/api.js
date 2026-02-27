import { authClient, wcClient, wpClient } from "@/api/http";

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || "";
const localCartKey = "storefront_cart_v1";

const readLocalCart = () => {
  try {
    const parsed = JSON.parse(localStorage.getItem(localCartKey) || "[]");
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
};

const writeLocalCart = (items) => {
  localStorage.setItem(localCartKey, JSON.stringify(items));
  return items;
};

const toPaged = (response) => ({
  items: response.data,
  total: Number(response.headers["x-wp-total"] || 0),
  totalPages: Number(response.headers["x-wp-totalpages"] || 0)
});

const normalizeCartResponse = (response) => {
  if (response?.data?.items) {
    return response.data;
  }
  if (Array.isArray(response?.data)) {
    return { items: response.data };
  }
  return { items: [] };
};

const safeRequest = async (requestFn, fallback) => {
  try {
    return await requestFn();
  } catch {
    return fallback;
  }
};

export const api = {
  getProducts: async (params = {}) => {
    const response = await wcClient.get("/products", { params });
    return response.data;
  },

  getProduct: async (idOrSlug) => {
    if (/^\d+$/.test(String(idOrSlug))) {
      return (await wcClient.get(`/products/${idOrSlug}`)).data;
    }
    const bySlug = await wcClient.get("/products", { params: { slug: idOrSlug, per_page: 1 } });
    if (!Array.isArray(bySlug.data) || !bySlug.data.length) {
      throw new Error("Product not found");
    }
    return bySlug.data[0];
  },

  getProductVariations: async (productId) => {
    const response = await wcClient.get(`/products/${productId}/variations`, { params: { per_page: 100 } });
    return response.data;
  },

  getPosts: async (params = {}) => {
    const response = await wpClient.get("/posts", {
      params: {
        _embed: true,
        per_page: 10,
        ...params
      }
    });
    return toPaged(response);
  },

  getPost: async (idOrSlug) => {
    if (/^\d+$/.test(String(idOrSlug))) {
      return (await wpClient.get(`/posts/${idOrSlug}`, { params: { _embed: true } })).data;
    }
    const response = await wpClient.get("/posts", { params: { slug: idOrSlug, _embed: true, per_page: 1 } });
    if (!Array.isArray(response.data) || !response.data.length) {
      throw new Error("Post not found");
    }
    return response.data[0];
  },

  getCart: async () => {
    const remote = await safeRequest(async () => {
      const response = await wcClient.get("/cart");
      return normalizeCartResponse(response);
    }, null);
    if (remote) {
      return remote;
    }
    return { items: readLocalCart() };
  },

  addToCart: async ({ product_id, quantity = 1, variation_id = 0, variation = [] }) => {
    const remote = await safeRequest(async () => {
      const response = await wcClient.post("/cart/add-item", {
        product_id,
        quantity,
        variation_id,
        variation
      });
      return normalizeCartResponse(response);
    }, null);
    if (remote) {
      return remote;
    }

    const cart = readLocalCart();
    const existing = cart.find((item) => item.product_id === product_id && item.variation_id === variation_id);
    if (existing) {
      existing.quantity += quantity;
    } else {
      cart.push({ product_id, quantity, variation_id, variation });
    }
    return { items: writeLocalCart(cart) };
  },

  updateCart: async (items) => {
    const remote = await safeRequest(async () => {
      const response = await wcClient.post("/cart/update-item", { items });
      return normalizeCartResponse(response);
    }, null);
    if (remote) {
      return remote;
    }
    return { items: writeLocalCart(items) };
  },

  removeFromCart: async ({ key, product_id, variation_id = 0 }) => {
    const remote = await safeRequest(async () => {
      const response = await wcClient.post("/cart/remove-item", { key, product_id, variation_id });
      return normalizeCartResponse(response);
    }, null);
    if (remote) {
      return remote;
    }

    const cart = readLocalCart().filter(
      (item) => !(item.product_id === product_id && item.variation_id === variation_id)
    );
    return { items: writeLocalCart(cart) };
  },

  checkout: async (payload) => {
    const response = await wcClient.post("/orders", payload);
    writeLocalCart([]);
    return response.data;
  },

  getPage: async (slug) => {
    const response = await wpClient.get("/pages", { params: { slug, per_page: 1, _embed: true } });
    return response.data?.[0] || null;
  },

  getCategories: async () => {
    const response = await wpClient.get("/categories", { params: { per_page: 100 } });
    return response.data;
  },

  getProductCategories: async () => {
    const response = await wcClient.get("/products/categories", { params: { per_page: 100 } });
    return response.data;
  },

  getComments: async (postId) => {
    const response = await wpClient.get("/comments", { params: { post: postId, per_page: 50 } });
    return response.data;
  },

  addComment: async ({ post, author_name, author_email, content }) => {
    const response = await wpClient.post("/comments", { post, author_name, author_email, content });
    return response.data;
  },

  sendContact: async (payload) => {
    const response = await authClient.post(`${apiBaseUrl}/wp-json/custom-api/v1/contact`, payload);
    return response.data;
  },

  getContactSettings: async () => {
    const response = await authClient.get(`${apiBaseUrl}/wp-json/custom-api/v1/site-settings`);
    return response.data;
  }
};
