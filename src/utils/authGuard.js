import { useAuthStore } from "@/stores/authStore";

export const authGuard = async (to) => {
  const authStore = useAuthStore();

  if (!authStore.isAuthenticated && to.meta.requiresAuth) {
    return { name: "login", query: { redirect: to.fullPath } };
  }

  if (to.meta.requiresAuth) {
    const valid = await authStore.validateToken();
    if (!valid) {
      return { name: "login", query: { redirect: to.fullPath } };
    }
  }

  if (to.meta.roles?.length && !to.meta.roles.includes(authStore.role)) {
    return authStore.getHomeRoute();
  }

  if (to.meta.permission && !authStore.hasPermission(to.meta.permission)) {
    return authStore.getHomeRoute();
  }

  if (to.meta.area === "admin" && !authStore.isAdmin) {
    return { name: "user.dashboard" };
  }

  if (to.meta.area === "user" && authStore.isAdmin) {
    return { name: "admin.dashboard" };
  }

  return true;
};
