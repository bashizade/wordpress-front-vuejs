import { createApp } from "vue";
import { createPinia } from "pinia";
import ElementPlus from "element-plus";
import "element-plus/dist/index.css";
import * as ElementPlusIconsVue from "@element-plus/icons-vue";
import App from "@/App.vue";
import router from "@/router";
import "@/styles.css";
import { useAuthStore } from "@/stores/authStore";
import i18n from "@/i18n";

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(i18n);
app.use(ElementPlus);

for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(key, component);
}

const authStore = useAuthStore();
await authStore.hydrate();

app.mount("#app");
