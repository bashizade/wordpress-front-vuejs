import { computed, ref } from "vue";
import { defineStore } from "pinia";
import { ElMessage } from "element-plus";
import { variationsApi } from "@/api/variations";
import { productApi } from "@/api/woocommerce";

const asNumber = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
};

const comboKey = (attributes = []) =>
  attributes
    .map((item) => `${(item.name || "").toLowerCase()}:${String(item.option || "").toLowerCase()}`)
    .sort()
    .join("|");

const buildCombinations = (matrixAttributes) => {
  const groups = matrixAttributes.filter((item) => item.variation && item.options?.length > 0);
  if (!groups.length) return [];
  const recurse = (index, acc) => {
    if (index === groups.length) return [acc];
    const next = [];
    groups[index].options.forEach((option) => {
      next.push(
        ...recurse(index + 1, [
          ...acc,
          {
            id: groups[index].id || undefined,
            name: groups[index].name,
            option
          }
        ])
      );
    });
    return next;
  };
  return recurse(0, []);
};

export const useVariationStore = defineStore("variationStore", () => {
  const productId = ref(null);
  const product = ref(null);
  const variations = ref([]);
  const loading = ref(false);

  const variationCount = computed(() => variations.value.length);

  const loadProductContext = async (id) => {
    productId.value = Number(id);
    product.value = await productApi.get(productId.value);
    return product.value;
  };

  const fetchVariations = async (params = {}) => {
    if (!productId.value) return [];
    loading.value = true;
    try {
      variations.value = await variationsApi.list(productId.value, {
        per_page: 100,
        orderby: "menu_order",
        ...params
      });
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "Failed loading variations");
    } finally {
      loading.value = false;
    }
    return variations.value;
  };

  const createVariation = async (payload) => {
    const created = await variationsApi.create(productId.value, payload);
    ElMessage.success("Variation created");
    await fetchVariations();
    return created;
  };

  const updateVariation = async (variationId, payload) => {
    const updated = await variationsApi.update(productId.value, variationId, payload);
    ElMessage.success("Variation updated");
    await fetchVariations();
    return updated;
  };

  const removeVariation = async (variationId) => {
    await variationsApi.remove(productId.value, variationId, true);
    ElMessage.success("Variation deleted");
    await fetchVariations();
  };

  const removeAllVariations = async () => {
    if (!variations.value.length) return;
    const deletes = variations.value.map((item) => ({ id: item.id }));
    await variationsApi.batch(productId.value, { delete: deletes });
    ElMessage.success("All variations deleted");
    await fetchVariations();
  };

  const autoGenerateVariations = async (attributes, defaults = {}) => {
    const combos = buildCombinations(attributes);
    const existingKeys = new Set(variations.value.map((item) => comboKey(item.attributes)));
    const createPayload = combos
      .filter((combo) => !existingKeys.has(comboKey(combo)))
      .map((combo, index) => ({
        attributes: combo,
        regular_price: defaults.regular_price || "",
        sale_price: defaults.sale_price || "",
        manage_stock: defaults.manage_stock ?? false,
        stock_quantity: defaults.stock_quantity ?? null,
        menu_order: variations.value.length + index
      }));

    if (!createPayload.length) {
      ElMessage.info("No new combinations to generate");
      return;
    }
    await variationsApi.batch(productId.value, { create: createPayload });
    ElMessage.success(`${createPayload.length} variations generated`);
    await fetchVariations();
  };

  const applyBulk = async (action, payload = {}) => {
    const updates = variations.value.map((variation) => {
      const next = {};

      if (action === "set_regular_price") next.regular_price = String(payload.value || "");
      if (action === "set_sale_price") next.sale_price = String(payload.value || "");
      if (action === "stock_set") {
        next.manage_stock = true;
        next.stock_quantity = asNumber(payload.value);
      }
      if (action === "price_percent") {
        const current = asNumber(variation.regular_price);
        const adjusted = current + (current * asNumber(payload.value)) / 100;
        next.regular_price = adjusted.toFixed(2);
      }
      if (action === "name_from_attributes") {
        next.description = variation.attributes.map((attr) => `${attr.name}: ${attr.option}`).join(" | ");
      }

      return { id: variation.id, ...next };
    });

    await variationsApi.batch(productId.value, { update: updates });
    ElMessage.success("Bulk update applied");
    await fetchVariations();
  };

  const reorderVariations = async (orderedList) => {
    const updates = orderedList.map((item, index) => ({
      id: item.id,
      menu_order: index
    }));
    await variationsApi.batch(productId.value, { update: updates });
    variations.value = orderedList.map((item, index) => ({ ...item, menu_order: index }));
    ElMessage.success("Variation order updated");
  };

  const setProductAttributes = async (attributes) => {
    if (!productId.value) return;
    product.value = await productApi.update(productId.value, {
      attributes
    });
    ElMessage.success("Product attributes updated");
    return product.value;
  };

  return {
    productId,
    product,
    variations,
    loading,
    variationCount,
    loadProductContext,
    fetchVariations,
    createVariation,
    updateVariation,
    removeVariation,
    removeAllVariations,
    autoGenerateVariations,
    applyBulk,
    reorderVariations,
    setProductAttributes
  };
});
