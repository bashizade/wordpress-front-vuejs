import { computed, ref } from "vue";
import { defineStore } from "pinia";
import { ElMessage } from "element-plus";
import { attributesApi } from "@/api/attributes";

const TERM_META_KEY = "wc_attribute_term_meta";

const slugify = (value) =>
  String(value || "")
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9\s-]/g, "")
    .replace(/\s+/g, "-")
    .replace(/-+/g, "-");

const loadMeta = () => JSON.parse(localStorage.getItem(TERM_META_KEY) || "{}");
const saveMeta = (data) => localStorage.setItem(TERM_META_KEY, JSON.stringify(data));

export const useAttributeStore = defineStore("attributeStore", () => {
  const attributes = ref([]);
  const terms = ref([]);
  const termsByAttribute = ref({});
  const loading = ref(false);
  const termMeta = ref(loadMeta());
  const selectedAttribute = ref(null);

  const sortedAttributes = computed(() =>
    [...attributes.value].sort((a, b) => a.menu_order - b.menu_order || a.name.localeCompare(b.name))
  );

  const fetchAttributes = async () => {
    loading.value = true;
    try {
      attributes.value = await attributesApi.list({ per_page: 100 });
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "Failed loading attributes");
    } finally {
      loading.value = false;
    }
  };

  const fetchAttribute = async (id) => {
    selectedAttribute.value = await attributesApi.get(id);
    return selectedAttribute.value;
  };

  const createAttribute = async (payload) => {
    const body = {
      ...payload,
      slug: payload.slug || slugify(payload.name)
    };
    const created = await attributesApi.create(body);
    ElMessage.success("Attribute created");
    await fetchAttributes();
    return created;
  };

  const updateAttribute = async (id, payload) => {
    const updated = await attributesApi.update(id, payload);
    ElMessage.success("Attribute updated");
    await fetchAttributes();
    return updated;
  };

  const removeAttribute = async (id) => {
    await attributesApi.remove(id, true);
    ElMessage.success("Attribute deleted");
    await fetchAttributes();
  };

  const fetchTerms = async (attributeId) => {
    terms.value = await attributesApi.listTerms(attributeId, { per_page: 100, orderby: "menu_order" });
    termsByAttribute.value[attributeId] = terms.value;
    return terms.value;
  };

  const createTerm = async (attributeId, payload) => {
    const created = await attributesApi.createTerm(attributeId, {
      ...payload,
      slug: payload.slug || slugify(payload.name)
    });
    ElMessage.success("Term created");
    await fetchTerms(attributeId);
    return created;
  };

  const updateTerm = async (attributeId, termId, payload) => {
    const updated = await attributesApi.updateTerm(attributeId, termId, payload);
    ElMessage.success("Term updated");
    await fetchTerms(attributeId);
    return updated;
  };

  const removeTerm = async (attributeId, termId) => {
    await attributesApi.removeTerm(attributeId, termId, true);
    ElMessage.success("Term deleted");
    await fetchTerms(attributeId);
  };

  const reorderTerms = async (attributeId, reorderedTerms) => {
    const updates = reorderedTerms.map((term, index) =>
      attributesApi.updateTerm(attributeId, term.id, { menu_order: index })
    );
    await Promise.all(updates);
    await fetchTerms(attributeId);
    ElMessage.success("Terms reordered");
  };

  const setTermMeta = (attributeId, termId, payload) => {
    const key = `${attributeId}:${termId}`;
    termMeta.value[key] = {
      ...(termMeta.value[key] || {}),
      ...payload
    };
    saveMeta(termMeta.value);
  };

  const getTermMeta = (attributeId, termId) => termMeta.value[`${attributeId}:${termId}`] || {};

  return {
    attributes,
    terms,
    termsByAttribute,
    loading,
    selectedAttribute,
    termMeta,
    sortedAttributes,
    slugify,
    fetchAttributes,
    fetchAttribute,
    createAttribute,
    updateAttribute,
    removeAttribute,
    fetchTerms,
    createTerm,
    updateTerm,
    removeTerm,
    reorderTerms,
    setTermMeta,
    getTermMeta
  };
});
