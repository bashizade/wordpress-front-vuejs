# Vue.js WordPress + WooCommerce Dashboard

Admin + User dashboard built with Vue 3 + Vite, connected to WordPress and WooCommerce REST APIs.

## Tech Stack
- Vue 3 (Composition API)
- Vite
- Pinia
- Vue Router
- Axios
- Element Plus
- TailwindCSS (+ RTL plugin)
- Vue I18n
- Chart.js

## Key Features
- JWT authentication
- RBAC routing:
  - Admin users -> `/admin`
  - Normal users/customers -> `/dashboard`
- Full admin CMS for WP + Woo entities
- Woo attributes + variations manager
- User management + dynamic custom user fields
- User dashboard:
  - Profile
  - Dynamic meta fields
  - Order history/details
  - Address management
- Persian + RTL support

## Important Files
- `src/router/index.js`
- `src/stores/authStore.js`
- `src/stores/userStore.js`
- `src/stores/customFieldStore.js`
- `src/stores/orderStore.js`
- `src/api/users.js`
- `src/api/userMeta.js`
- `src/api/orders.js`
- `src/api/customFields.js`
- `src/views/Register.vue`
- `src/views/Login.vue`
- `src/views/user/DashboardHome.vue`
- `src/views/user/UserProfile.vue`
- `src/views/user/UserOrders.vue`
- `src/views/user/OrderDetails.vue`
- `src/views/user/UserAddresses.vue`
- `src/components/Users/DynamicMetaFields.vue`
- `wordpress-plugin/vue-dashboard-custom-api/vue-dashboard-custom-api.php`

## WordPress Prerequisites
Install and activate:
1. WooCommerce
2. JWT Authentication for WP-API
3. Custom plugin from this repo:
   - `wordpress-plugin/vue-dashboard-custom-api/vue-dashboard-custom-api.php`

Add to `wp-config.php`:
```php
define('JWT_AUTH_SECRET_KEY', 'your-very-strong-secret');
define('JWT_AUTH_CORS_ENABLE', true);
define('CUSTOM_AUTH_IPPANEL_API_KEY', 'your_ippanel_api_key');
define('CUSTOM_AUTH_IPPANEL_SENDER', 'your_sender_number');
```

## Plugin Endpoints Added
- `POST /wp-json/custom-auth/v1/login`
- `POST /wp-json/custom-auth/v1/login/otp`
- `POST /wp-json/custom-auth/v1/login/verify`
- `POST /wp-json/custom-auth/v1/register/otp`
- `POST /wp-json/custom-auth/v1/register/verify`
- `POST /wp-json/custom-auth/v1/token/validate`
- `GET /wp-json/custom-api/v1/custom-fields`
- `POST /wp-json/custom-api/v1/custom-fields`
- `GET /wp-json/custom-api/v1/user/meta/{id}`
- `GET /wp-json/custom-api/v1/user/meta/me`
- `POST /wp-json/custom-api/v1/user/meta/update`
- `GET /wp-json/custom-fields/v1/fields?post_type={slug}`
- `GET /wp-json/custom-fields/v1/fields/all`
- `POST /wp-json/custom-fields/v1/fields/all`
- `GET /wp-json/custom-fields/v1/post-types`
- `GET /wp-json/custom-fields/v1/meta/{post_id}`
- `POST /wp-json/custom-fields/v1/meta/{post_id}`
- `GET /wp-json/custom-cpt/v1/list`
- `POST /wp-json/custom-cpt/v1/create`
- `POST /wp-json/custom-cpt/v1/update/{slug}`
- `DELETE /wp-json/custom-cpt/v1/delete/{slug}`
- `POST /wp-json/custom-cpt/v1/reorder`
- `GET /wp-json/custom-admin/v1/menu`

## Dynamic CPT Builder
- Admin page: `/admin/settings/cpt-builder`
- Dynamic CPT list: `/admin/cpt/{slug}/list`
- Dynamic CPT editor: `/admin/cpt/{slug}/new` and `/admin/cpt/{slug}/edit/{id}`
- CPT definitions are stored in WordPress option: `vue_dashboard_dynamic_cpt_definitions`
- Dynamic CPTs are registered on `init` with `show_in_rest` support for automatic REST exposure.

## Storefront (Public Website)

### Added Pages
- `/` -> `src/views/storefront/index.vue`
- `/products` -> `src/views/storefront/products.vue`
- `/product/:slug` -> `src/views/storefront/product.vue`
- `/cart` -> `src/views/storefront/cart.vue`
- `/checkout` -> `src/views/storefront/checkout.vue`
- `/blogs` -> `src/views/storefront/blogs.vue`
- `/blog/:slug` -> `src/views/storefront/blog.vue`
- `/contact` -> `src/views/storefront/contact.vue`
- `/about` -> `src/views/storefront/about.vue`

### Storefront Structure
- `src/services/api.js`
- `src/stores/shopStore.js`
- `src/stores/blogStore.js`
- `src/stores/siteStore.js`
- `src/components/storefront/`
  - `SiteShell.vue`
  - `Loader.vue`
  - `Breadcrumb.vue`
  - `Pagination.vue`
  - `RatingStars.vue`
  - `AddToCartButton.vue`
  - `ProductCard.vue`
  - `ProductGrid.vue`
  - `ProductFilter.vue`
  - `BlogCard.vue`

### API Service Methods
Implemented in `src/services/api.js`:
- `getProducts()`
- `getProduct(idOrSlug)`
- `getPosts()`
- `getPost(idOrSlug)`
- `getCart()`
- `addToCart()`
- `updateCart()`
- `removeFromCart()`
- `checkout()`
- `getPage(slug)`

Additional helpers:
- `getProductVariations()`
- `getCategories()`
- `getProductCategories()`
- `getComments()`
- `addComment()`
- `sendContact()`
- `getContactSettings()`

### Example JSON Responses
`GET /wp-json/wc/v3/products`
```json
[
  {
    "id": 101,
    "name": "Running Shoes",
    "slug": "running-shoes",
    "price": "89.00",
    "regular_price": "99.00",
    "sale_price": "89.00",
    "on_sale": true,
    "stock_status": "instock",
    "images": [{ "id": 1, "src": "https://site.com/uploads/shoes.jpg" }],
    "attributes": [{ "name": "Size", "options": ["41", "42"] }]
  }
]
```

`GET /wp-json/wp/v2/posts?_embed=1`
```json
[
  {
    "id": 501,
    "slug": "summer-style-guide",
    "title": { "rendered": "Summer Style Guide" },
    "excerpt": { "rendered": "<p>Quick tips...</p>" },
    "content": { "rendered": "<p>Full content...</p>" },
    "_embedded": {
      "wp:featuredmedia": [{ "source_url": "https://site.com/uploads/blog.jpg" }]
    }
  }
]
```

## Environment
Copy `.env.example` to `.env`:
```env
VITE_API_BASE_URL=https://your-site.com
VITE_WP_API_PATH=/wp-json/wp/v2
VITE_WC_API_PATH=/wp-json/wc/v3
VITE_JWT_TOKEN_PATH=/wp-json/jwt-auth/v1/token
VITE_JWT_VALIDATE_PATH=/wp-json/jwt-auth/v1/token/validate
VITE_WC_CONSUMER_KEY=ck_xxx
VITE_WC_CONSUMER_SECRET=cs_xxx
```

## Run
```bash
npm install
npm run dev
```
