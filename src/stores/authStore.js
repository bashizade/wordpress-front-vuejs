import { computed, ref } from "vue";
import { defineStore } from "pinia";
import { ElMessage } from "element-plus";
import {
  fetchCurrentUser,
  loginWithPasswordRequest,
  requestLoginOtp,
  requestRegisterOtp,
  validateTokenRequest,
  verifyLoginOtpRequest,
  verifyRegisterOtpRequest
} from "@/api/auth";
import { setAxiosToken } from "@/api/http";

const TOKEN_KEY = "wp_jwt_token";
const USER_KEY = "wp_user";
const THEME_KEY = "cms_theme";

const rolePermissions = {
  administrator: ["*"],
  shop_manager: [
    "dashboard:view",
    "products:manage",
    "orders:manage",
    "customers:view",
    "coupons:manage",
    "media:manage",
    "users:manage"
  ],
  editor: [
    "dashboard:view",
    "posts:manage",
    "pages:manage",
    "media:manage",
    "comments:manage",
    "taxonomy:manage",
    "users:manage"
  ],
  author: ["dashboard:view", "posts:manage", "media:manage"],
  customer: ["dashboard:view"]
};

const adminRoles = ["administrator", "shop_manager", "editor", "author", "contributor"];

export const useAuthStore = defineStore("authStore", () => {
  const token = ref(localStorage.getItem(TOKEN_KEY) || "");
  const user = ref(JSON.parse(localStorage.getItem(USER_KEY) || "null"));
  const theme = ref(localStorage.getItem(THEME_KEY) || "light");
  const loading = ref(false);
  const otpPendingMobile = ref("");
  const registerPendingMobile = ref("");
  let tokenHeartbeat = null;

  if (token.value) setAxiosToken(token.value);

  const isAuthenticated = computed(() => Boolean(token.value));
  const username = computed(() => user.value?.name || user.value?.username || "User");
  const role = computed(() => user.value?.roles?.[0] || user.value?.role || "customer");
  const roles = computed(() => user.value?.roles || (role.value ? [role.value] : []));
  const permissions = computed(() => rolePermissions[role.value] || []);
  const isAdmin = computed(() => roles.value.some((item) => adminRoles.includes(item)));
  const isNormalUser = computed(() => !isAdmin.value);

  const persistAuth = () => {
    localStorage.setItem(TOKEN_KEY, token.value || "");
    localStorage.setItem(USER_KEY, JSON.stringify(user.value || null));
  };

  const restoreTheme = () => {
    document.documentElement.classList.toggle("dark", theme.value === "dark");
  };

  const toggleTheme = () => {
    theme.value = theme.value === "dark" ? "light" : "dark";
    localStorage.setItem(THEME_KEY, theme.value);
    restoreTheme();
  };

  const hasPermission = (requiredPermission) => {
    if (!requiredPermission) return true;
    if (permissions.value.includes("*")) return true;
    return permissions.value.includes(requiredPermission);
  };

  const applyAuthPayload = async (payload) => {
    token.value = payload.token;
    setAxiosToken(payload.token);
    user.value = {
      id: payload.user_id,
      name: payload.user_display_name,
      email: payload.user_email,
      username: payload.user_nicename,
      roles: payload.roles || []
    };
    try {
      const me = await fetchCurrentUser();
      user.value = {
        ...user.value,
        ...me
      };
    } catch {
      // continue with payload data
    }
    persistAuth();
    startTokenHeartbeat();
  };

  const login = async ({ username, password }) => {
    loading.value = true;
    try {
      const payload = await loginWithPasswordRequest({ username, password });
      await applyAuthPayload(payload);
      ElMessage.success("ورود موفق");
      return true;
    } catch (error) {
      logout();
      ElMessage.error(error?.response?.data?.message || "ورود ناموفق");
      return false;
    } finally {
      loading.value = false;
    }
  };

  const sendLoginOtp = async (mobile) => {
    loading.value = true;
    try {
      await requestLoginOtp({ mobile });
      otpPendingMobile.value = mobile;
      ElMessage.success("کد تایید ارسال شد");
      return true;
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "ارسال کد ناموفق بود");
      return false;
    } finally {
      loading.value = false;
    }
  };

  const verifyLoginOtp = async ({ mobile, otp }) => {
    loading.value = true;
    try {
      const payload = await verifyLoginOtpRequest({ mobile, otp });
      await applyAuthPayload(payload);
      otpPendingMobile.value = "";
      ElMessage.success("ورود با موبایل انجام شد");
      return true;
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "کد تایید نامعتبر است");
      return false;
    } finally {
      loading.value = false;
    }
  };

  const sendRegisterOtp = async (payload) => {
    loading.value = true;
    try {
      await requestRegisterOtp(payload);
      registerPendingMobile.value = payload.mobile;
      ElMessage.success("کد ثبت نام ارسال شد");
      return true;
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "ارسال کد ثبت نام ناموفق بود");
      return false;
    } finally {
      loading.value = false;
    }
  };

  const verifyRegisterOtp = async ({ mobile, otp }) => {
    loading.value = true;
    try {
      const payload = await verifyRegisterOtpRequest({ mobile, otp });
      await applyAuthPayload(payload);
      registerPendingMobile.value = "";
      ElMessage.success("حساب ایجاد و وارد شدید");
      return true;
    } catch (error) {
      ElMessage.error(error?.response?.data?.message || "تایید ثبت نام ناموفق بود");
      return false;
    } finally {
      loading.value = false;
    }
  };

  const validateToken = async () => {
    if (!token.value) return false;
    try {
      await validateTokenRequest();
      return true;
    } catch {
      logout();
      return false;
    }
  };

  const startTokenHeartbeat = () => {
    if (tokenHeartbeat) clearInterval(tokenHeartbeat);
    tokenHeartbeat = setInterval(async () => {
      if (!token.value) return;
      await validateToken();
    }, 60000);
  };

  const stopTokenHeartbeat = () => {
    if (tokenHeartbeat) clearInterval(tokenHeartbeat);
    tokenHeartbeat = null;
  };

  const hydrate = async () => {
    restoreTheme();
    if (!token.value) return;
    const valid = await validateToken();
    if (!valid) return;
    try {
      const me = await fetchCurrentUser();
      user.value = {
        ...(user.value || {}),
        ...me
      };
      persistAuth();
      startTokenHeartbeat();
    } catch {
      logout();
    }
  };

  const logout = () => {
    stopTokenHeartbeat();
    token.value = "";
    user.value = null;
    otpPendingMobile.value = "";
    registerPendingMobile.value = "";
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    setAxiosToken("");
  };

  const getHomeRoute = () => (isAdmin.value ? { name: "admin.dashboard" } : { name: "user.dashboard" });

  return {
    token,
    user,
    loading,
    theme,
    role,
    roles,
    isAdmin,
    isNormalUser,
    permissions,
    username,
    isAuthenticated,
    otpPendingMobile,
    registerPendingMobile,
    login,
    sendLoginOtp,
    verifyLoginOtp,
    sendRegisterOtp,
    verifyRegisterOtp,
    logout,
    hydrate,
    validateToken,
    toggleTheme,
    hasPermission,
    getHomeRoute,
    startTokenHeartbeat,
    stopTokenHeartbeat
  };
});
