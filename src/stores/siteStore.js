import { ref } from "vue";
import { defineStore } from "pinia";
import { api } from "@/services/api";

export const useSiteStore = defineStore("siteStore", () => {
  const homePage = ref(null);
  const aboutPage = ref(null);
  const contactPage = ref(null);
  const postCategories = ref([]);
  const productCategories = ref([]);
  const contactSettings = ref({});

  const fetchHome = async () => {
    homePage.value = await api.getPage("home");
    return homePage.value;
  };

  const fetchAbout = async () => {
    aboutPage.value = await api.getPage("about-us");
    return aboutPage.value;
  };

  const fetchContact = async () => {
    const [page, settings] = await Promise.all([
      api.getPage("contact"),
      api.getContactSettings().catch(() => ({}))
    ]);
    contactPage.value = page;
    contactSettings.value = settings || {};
    return { page, settings };
  };

  const fetchCategories = async () => {
    const [postCats, productCats] = await Promise.all([
      api.getCategories(),
      api.getProductCategories()
    ]);
    postCategories.value = postCats || [];
    productCategories.value = productCats || [];
  };

  return {
    homePage,
    aboutPage,
    contactPage,
    postCategories,
    productCategories,
    contactSettings,
    fetchHome,
    fetchAbout,
    fetchContact,
    fetchCategories
  };
});
