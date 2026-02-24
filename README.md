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
