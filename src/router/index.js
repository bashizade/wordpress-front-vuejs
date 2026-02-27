import { createRouter, createWebHistory } from "vue-router";
import { authGuard } from "@/utils/authGuard";
import { useAuthStore } from "@/stores/authStore";

const routes = [
  {
    path: "/",
    name: "store.home",
    component: () => import("@/views/storefront/index.vue"),
    meta: { title: "Home" }
  },
  {
    path: "/products",
    name: "store.products",
    component: () => import("@/views/storefront/products.vue"),
    meta: { title: "Products" }
  },
  {
    path: "/product/:slug",
    name: "store.product",
    component: () => import("@/views/storefront/product.vue"),
    meta: { title: "Product" }
  },
  {
    path: "/cart",
    name: "store.cart",
    component: () => import("@/views/storefront/cart.vue"),
    meta: { title: "Cart" }
  },
  {
    path: "/checkout",
    name: "store.checkout",
    component: () => import("@/views/storefront/checkout.vue"),
    meta: { title: "Checkout" }
  },
  {
    path: "/blogs",
    name: "store.blogs",
    component: () => import("@/views/storefront/blogs.vue"),
    meta: { title: "Blogs" }
  },
  {
    path: "/blog/:slug",
    name: "store.blog",
    component: () => import("@/views/storefront/blog.vue"),
    meta: { title: "Blog" }
  },
  {
    path: "/contact",
    name: "store.contact",
    component: () => import("@/views/storefront/contact.vue"),
    meta: { title: "Contact" }
  },
  {
    path: "/about",
    name: "store.about",
    component: () => import("@/views/storefront/about.vue"),
    meta: { title: "About" }
  },
  {
    path: "/login",
    name: "login",
    component: () => import("@/views/Login.vue"),
    meta: { guestOnly: true }
  },
  {
    path: "/register",
    name: "register",
    component: () => import("@/views/Register.vue"),
    meta: { guestOnly: true }
  },
  {
    path: "/admin",
    component: () => import("@/components/Layout/AppLayout.vue"),
    meta: { requiresAuth: true, area: "admin" },
    children: [
      {
        path: "",
        name: "admin.dashboard",
        component: () => import("@/views/Dashboard.vue"),
        meta: { permission: "dashboard:view", area: "admin" }
      },
      {
        path: "posts",
        name: "posts.list",
        component: () => import("@/views/Posts/PostsList.vue"),
        meta: { permission: "posts:manage", area: "admin" }
      },
      {
        path: "posts/create",
        name: "posts.create",
        component: () => import("@/views/Posts/PostCreate.vue"),
        meta: { permission: "posts:manage", area: "admin" }
      },
      {
        path: "posts/:id/edit",
        name: "posts.edit",
        component: () => import("@/views/Posts/PostEdit.vue"),
        meta: { permission: "posts:manage", area: "admin" }
      },
      {
        path: "products",
        name: "products.list",
        component: () => import("@/views/Products/ProductList.vue"),
        meta: { permission: "products:manage", area: "admin" }
      },
      {
        path: "products/create",
        name: "products.create",
        component: () => import("@/views/Products/ProductCreate.vue"),
        meta: { permission: "products:manage", area: "admin" }
      },
      {
        path: "products/:id/edit",
        name: "products.edit",
        component: () => import("@/views/Products/ProductEdit.vue"),
        meta: { permission: "products:manage", area: "admin" }
      },
      {
        path: "product-attributes",
        name: "attributes.list",
        component: () => import("@/views/Attributes/AttributesList.vue"),
        meta: { permission: "products:manage", area: "admin" }
      },
      {
        path: "product-attributes/:id/edit",
        name: "attributes.edit",
        component: () => import("@/views/Attributes/AttributeEdit.vue"),
        meta: { permission: "products:manage", area: "admin" }
      },
      {
        path: "product-attributes/:id/terms",
        name: "attributes.terms",
        component: () => import("@/views/Attributes/AttributeTerms.vue"),
        meta: { permission: "products:manage", area: "admin" }
      },
      {
        path: "pages",
        name: "pages.list",
        component: () => import("@/views/Pages/PagesList.vue"),
        meta: { permission: "pages:manage", area: "admin" }
      },
      {
        path: "media",
        name: "media.list",
        component: () => import("@/views/Media/MediaLibrary.vue"),
        meta: { permission: "media:manage", area: "admin" }
      },
      {
        path: "taxonomy",
        name: "taxonomy.list",
        component: () => import("@/views/Taxonomy/TaxonomyManager.vue"),
        meta: { permission: "taxonomy:manage", area: "admin" }
      },
      {
        path: "comments",
        name: "comments.list",
        component: () => import("@/views/Comments/CommentsList.vue"),
        meta: { permission: "comments:manage", area: "admin" }
      },
      {
        path: "orders",
        name: "orders.list",
        component: () => import("@/views/Orders/OrdersList.vue"),
        meta: { permission: "orders:manage", area: "admin" }
      },
      {
        path: "customers",
        name: "customers.list",
        component: () => import("@/views/Customers/CustomersList.vue"),
        meta: { permission: "customers:view", area: "admin" }
      },
      {
        path: "coupons",
        name: "coupons.list",
        component: () => import("@/views/Coupons/CouponsList.vue"),
        meta: { permission: "coupons:manage", area: "admin" }
      },
      {
        path: "users",
        name: "users.list",
        component: () => import("@/views/Users/UsersList.vue"),
        meta: { permission: "users:manage", area: "admin" }
      },
      {
        path: "users/custom-fields",
        name: "users.custom-fields",
        component: () => import("@/views/Users/CustomFieldsManager.vue"),
        meta: { permission: "users:manage", area: "admin" }
      },
      {
        path: "post-custom-fields",
        name: "post-custom-fields.manager",
        component: () => import("@/views/MetaFields/CustomFieldManager.vue"),
        meta: { permission: "users:manage", area: "admin" }
      },
      {
        path: "settings/cpt-builder",
        name: "cpt.builder",
        component: () => import("@/views/CPT/CptBuilder.vue"),
        meta: { permission: "users:manage", area: "admin" }
      },
      {
        path: "cpt/:slug/list",
        name: "cpt.list",
        component: () => import("@/views/CPT/CptList.vue"),
        meta: { permission: "posts:manage", area: "admin" }
      },
      {
        path: "cpt/:slug/new",
        name: "cpt.new",
        component: () => import("@/views/CPT/CptEditor.vue"),
        meta: { permission: "posts:manage", area: "admin" }
      },
      {
        path: "cpt/:slug/edit/:id",
        name: "cpt.edit",
        component: () => import("@/views/CPT/CptEditor.vue"),
        meta: { permission: "posts:manage", area: "admin" }
      }
    ]
  },
  {
    path: "/dashboard",
    component: () => import("@/components/Layout/UserLayout.vue"),
    meta: { requiresAuth: true, area: "user" },
    children: [
      {
        path: "",
        name: "user.dashboard",
        component: () => import("@/views/user/UserDashboard.vue"),
        meta: { area: "user" }
      },
      {
        path: "profile",
        name: "user.profile",
        component: () => import("@/views/user/UserProfile.vue"),
        meta: { area: "user" }
      },
      {
        path: "orders",
        name: "user.orders",
        component: () => import("@/views/user/UserOrders.vue"),
        meta: { area: "user" }
      },
      {
        path: "orders/:id",
        name: "user.order-details",
        component: () => import("@/views/user/OrderDetails.vue"),
        meta: { area: "user" }
      },
      {
        path: "addresses",
        name: "user.addresses",
        component: () => import("@/views/user/UserAddresses.vue"),
        meta: { area: "user" }
      }
    ]
  },
  {
    path: "/:pathMatch(.*)*",
    name: "not-found",
    component: () => import("@/views/NotFound.vue")
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

router.beforeEach(async (to) => {
  const authStore = useAuthStore();
  if (to.meta.guestOnly) {
    const token = localStorage.getItem("wp_jwt_token");
    if (token) {
      await authStore.hydrate();
      return authStore.getHomeRoute();
    }
    return true;
  }
  return authGuard(to);
});

export default router;
