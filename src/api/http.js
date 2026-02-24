import axios from "axios";

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || "";
const wpApiPath = import.meta.env.VITE_WP_API_PATH || "/wp-json/wp/v2";
const wcApiPath = import.meta.env.VITE_WC_API_PATH || "/wp-json/wc/v3";

export const authClient = axios.create({
  baseURL: apiBaseUrl
});

export const wpClient = axios.create({
  baseURL: `${apiBaseUrl}${wpApiPath}`
});

export const wcClient = axios.create({
  baseURL: `${apiBaseUrl}${wcApiPath}`,
  params: {
    consumer_key: import.meta.env.VITE_WC_CONSUMER_KEY || "",
    consumer_secret: import.meta.env.VITE_WC_CONSUMER_SECRET || ""
  }
});

const addAuthHeader = (config) => {
  const token = localStorage.getItem("wp_jwt_token");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
};

wpClient.interceptors.request.use(addAuthHeader);
wcClient.interceptors.request.use(addAuthHeader);
authClient.interceptors.request.use(addAuthHeader);

export const setAxiosToken = (token) => {
  if (!token) {
    delete wpClient.defaults.headers.common.Authorization;
    delete wcClient.defaults.headers.common.Authorization;
    delete authClient.defaults.headers.common.Authorization;
    return;
  }
  const bearer = `Bearer ${token}`;
  wpClient.defaults.headers.common.Authorization = bearer;
  wcClient.defaults.headers.common.Authorization = bearer;
  authClient.defaults.headers.common.Authorization = bearer;
};
