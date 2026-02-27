import { computed, ref } from "vue";
import { defineStore } from "pinia";
import { api } from "@/services/api";

export const useShopStore = defineStore("shopStore", () => {
  const products = ref([]);
  const total = ref(0);
  const totalPages = ref(0);
  const loadingProducts = ref(false);
  const currentProduct = ref(null);
  const currentVariations = ref([]);
  const loadingProduct = ref(false);
  const cart = ref([]);
  const loadingCart = ref(false);

  const toNumber = (value) => {
    const n = Number(value);
    return Number.isFinite(n) ? n : 0;
  };

  const getProductId = (item) => Number(item.product_id || item.id || item.product?.id || 0);

  const getQuantity = (item) => {
    const qty = toNumber(item.quantity ?? item.qty ?? item.product_quantity ?? 1);
    return qty > 0 ? qty : 1;
  };

  const getUnitPrice = (item) => {
    if (item._unitPrice !== undefined) {
      return toNumber(item._unitPrice);
    }
    const direct = toNumber(item.price ?? item.regular_price ?? item.sale_price);
    if (direct > 0) return direct;

    const pricesPrice = toNumber(item.prices?.price);
    if (pricesPrice > 0) return pricesPrice;

    const lineTotal = toNumber(item.line_total ?? item.line_subtotal ?? item.totals?.line_total);
    const qty = getQuantity(item);
    if (lineTotal > 0 && qty > 0) return lineTotal / qty;

    return toNumber(item._product?.price);
  };

  const getItemName = (item) => {
    return (
      item.name ||
      item.title ||
      item._product?.name ||
      item.product?.name ||
      `#${getProductId(item)}`
    );
  };

  const cartCount = computed(() =>
    cart.value.reduce((sum, item) => sum + getQuantity(item), 0)
  );

  const cartSubtotal = computed(() =>
    cart.value.reduce((sum, item) => {
      const price = getUnitPrice(item);
      const qty = getQuantity(item);
      return sum + price * qty;
    }, 0)
  );

  const hydrateCartItems = async (items = []) => {
    const byProductId = new Map();
    const needsLookup = [];

    items.forEach((item) => {
      const id = getProductId(item);
      if (!id) return;
      if (!item.name || getUnitPrice(item) <= 0) {
        if (!byProductId.has(id)) {
          byProductId.set(id, null);
          needsLookup.push(id);
        }
      }
    });

    if (needsLookup.length) {
      const lookedUp = await Promise.all(
        needsLookup.map(async (id) => {
          try {
            const product = await api.getProduct(id);
            return [id, product];
          } catch {
            return [id, null];
          }
        })
      );
      lookedUp.forEach(([id, product]) => byProductId.set(id, product));
    }

    return items.map((item) => {
      const productId = getProductId(item);
      const product = item._product || byProductId.get(productId) || null;
      const qty = getQuantity(item);
      const unit = getUnitPrice({ ...item, _product: product });
      return {
        ...item,
        _product: product,
        _name: getItemName({ ...item, _product: product }),
        _quantity: qty,
        _unitPrice: unit,
        _lineTotal: unit * qty
      };
    });
  };

  const serializeCartItems = (items = []) =>
    items.map((item) => ({
      key: item.key,
      product_id: getProductId(item),
      variation_id: Number(item.variation_id || 0),
      quantity: getQuantity(item),
      variation: Array.isArray(item.variation) ? item.variation : []
    }));

  const fetchProducts = async (params = {}) => {
    loadingProducts.value = true;
    try {
      const data = await api.getProducts(params);
      products.value = Array.isArray(data) ? data : [];
      if (params.page === 1 && products.value.length < (params.per_page || 12)) {
        total.value = products.value.length;
      }
    } finally {
      loadingProducts.value = false;
    }
    return products.value;
  };

  const fetchProduct = async (idOrSlug) => {
    loadingProduct.value = true;
    try {
      currentProduct.value = await api.getProduct(idOrSlug);
      currentVariations.value = currentProduct.value?.type === "variable"
        ? await api.getProductVariations(currentProduct.value.id)
        : [];
    } finally {
      loadingProduct.value = false;
    }
    return currentProduct.value;
  };

  const fetchCart = async () => {
    loadingCart.value = true;
    try {
      const data = await api.getCart();
      cart.value = await hydrateCartItems(data.items || []);
    } finally {
      loadingCart.value = false;
    }
    return cart.value;
  };

  const addToCart = async (payload) => {
    const data = await api.addToCart(payload);
    cart.value = await hydrateCartItems(data.items || []);
    return cart.value;
  };

  const updateCart = async (items) => {
    const data = await api.updateCart(serializeCartItems(items));
    cart.value = await hydrateCartItems(data.items || []);
    return cart.value;
  };

  const removeFromCart = async (payload) => {
    const data = await api.removeFromCart(payload);
    cart.value = await hydrateCartItems(data.items || []);
    return cart.value;
  };

  const checkout = async (payload) => {
    const order = await api.checkout(payload);
    cart.value = [];
    return order;
  };

  return {
    products,
    total,
    totalPages,
    loadingProducts,
    currentProduct,
    currentVariations,
    loadingProduct,
    cart,
    loadingCart,
    cartCount,
    cartSubtotal,
    fetchProducts,
    fetchProduct,
    fetchCart,
    addToCart,
    updateCart,
    removeFromCart,
    checkout
  };
});
