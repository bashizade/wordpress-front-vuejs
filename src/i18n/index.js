import { createI18n } from "vue-i18n";
import fa from "@/i18n/fa.json";
import en from "@/i18n/en.json";

const LOCALE_KEY = "cms_locale";

const locale = localStorage.getItem(LOCALE_KEY) || "fa";

const i18n = createI18n({
  legacy: false,
  globalInjection: true,
  locale,
  fallbackLocale: "en",
  messages: {
    fa,
    en
  }
});

export const setLocale = (nextLocale) => {
  i18n.global.locale.value = nextLocale;
  localStorage.setItem(LOCALE_KEY, nextLocale);
  const isRtl = nextLocale === "fa";
  document.documentElement.setAttribute("dir", isRtl ? "rtl" : "ltr");
  document.documentElement.setAttribute("lang", nextLocale);
};

setLocale(locale);

export default i18n;
