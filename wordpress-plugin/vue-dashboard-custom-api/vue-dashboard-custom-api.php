<?php
/**
 * Plugin Name: Vue Dashboard Custom Auth API
 * Description: Dual-mode authentication (password + mobile OTP), registration OTP, dynamic user fields, and user meta APIs.
 * Version: 2.0.0
 * Author: Vue Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

const VUE_DASHBOARD_SCHEMA_OPTION = 'vue_dashboard_user_custom_fields_schema';
const VUE_AUTH_OTP_TTL = 180;
const VUE_AUTH_OTP_MAX_ATTEMPTS = 5;
const VUE_AUTH_SMS_COOLDOWN_SECONDS = 60;
const VUE_AUTH_SMS_HOURLY_LIMIT = 5;
const VUE_AUTH_TOKEN_TTL = 604800; // 7 days
const VUE_AUTH_LOGIN_ATTEMPT_LIMIT = 10;
const VUE_AUTH_LOGIN_ATTEMPT_WINDOW = 600;
const VUE_POST_FIELDS_SCHEMA_OPTION = 'vue_dashboard_post_custom_fields_schema';

class Vue_Ippanel_SMS_Service
{
    private $api_key;
    private $sender;
    private $endpoint = 'https://ippanel.com/api/select';

    public function __construct($api_key, $sender)
    {
        $this->api_key = $api_key;
        $this->sender = $sender;
    }

    public function send($mobile, $message)
    {
        if (empty($this->api_key) || empty($this->sender)) {
            return new WP_Error('sms_config', 'IPPANEL API key/sender is not configured', ['status' => 500]);
        }

        $payload = [
            'op' => 'send',
            'uname' => $this->api_key,
            'from' => $this->sender,
            'to' => [$mobile],
            'message' => $message,
        ];

        $response = wp_remote_post($this->endpoint, [
            'timeout' => 15,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        if ($code < 200 || $code >= 300) {
            return new WP_Error('sms_send_failed', $body ?: 'SMS send failed', ['status' => 500]);
        }

        return true;
    }
}

function vue_auth_register_routes()
{
    register_rest_route('custom-auth/v1', '/login', [
        'methods' => 'POST',
        'callback' => 'vue_auth_login_password',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('custom-auth/v1', '/login/otp', [
        'methods' => 'POST',
        'callback' => 'vue_auth_login_otp_send',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('custom-auth/v1', '/login/verify', [
        'methods' => 'POST',
        'callback' => 'vue_auth_login_otp_verify',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('custom-auth/v1', '/register/otp', [
        'methods' => 'POST',
        'callback' => 'vue_auth_register_otp_send',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('custom-auth/v1', '/register/verify', [
        'methods' => 'POST',
        'callback' => 'vue_auth_register_otp_verify',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('custom-auth/v1', '/token/validate', [
        'methods' => 'POST',
        'callback' => 'vue_auth_validate_token_endpoint',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('custom-api/v1', '/custom-fields', [
        'methods' => 'GET',
        'callback' => 'vue_dashboard_get_custom_fields',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('custom-api/v1', '/custom-fields', [
        'methods' => 'POST',
        'callback' => 'vue_dashboard_save_custom_fields',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
    ]);

    register_rest_route('custom-api/v1', '/user/meta/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'vue_dashboard_get_user_meta',
        'permission_callback' => 'vue_dashboard_can_access_user_meta',
    ]);

    register_rest_route('custom-api/v1', '/user/meta/me', [
        'methods' => 'GET',
        'callback' => 'vue_dashboard_get_current_user_meta',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);

    register_rest_route('custom-api/v1', '/user/meta/update', [
        'methods' => 'POST',
        'callback' => 'vue_dashboard_update_user_meta',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);
}
add_action('rest_api_init', 'vue_auth_register_routes');

function vue_auth_get_sms_service()
{
    $api_key = defined('CUSTOM_AUTH_IPPANEL_API_KEY') ? CUSTOM_AUTH_IPPANEL_API_KEY : '';
    $sender = defined('CUSTOM_AUTH_IPPANEL_SENDER') ? CUSTOM_AUTH_IPPANEL_SENDER : '';
    return new Vue_Ippanel_SMS_Service($api_key, $sender);
}

function vue_auth_get_secret_key()
{
    if (defined('JWT_AUTH_SECRET_KEY') && JWT_AUTH_SECRET_KEY) {
        return JWT_AUTH_SECRET_KEY;
    }
    return wp_salt('auth');
}

function vue_auth_base64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function vue_auth_base64url_decode($data)
{
    $padding = strlen($data) % 4;
    if ($padding > 0) {
        $data .= str_repeat('=', 4 - $padding);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function vue_auth_create_token($user)
{
    $iat = time();
    $exp = $iat + VUE_AUTH_TOKEN_TTL;
    $header = ['typ' => 'JWT', 'alg' => 'HS256'];
    $payload = [
        'iss' => get_bloginfo('url'),
        'iat' => $iat,
        'nbf' => $iat,
        'exp' => $exp,
        'data' => [
            'user' => [
                'id' => (int) $user->ID,
                'roles' => array_values((array) $user->roles),
            ],
        ],
    ];

    $segments = [];
    $segments[] = vue_auth_base64url_encode(wp_json_encode($header));
    $segments[] = vue_auth_base64url_encode(wp_json_encode($payload));
    $signing_input = implode('.', $segments);
    $signature = hash_hmac('sha256', $signing_input, vue_auth_get_secret_key(), true);
    $segments[] = vue_auth_base64url_encode($signature);
    return implode('.', $segments);
}

function vue_auth_decode_token($token)
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return new WP_Error('invalid_token', 'Malformed token', ['status' => 401]);
    }

    [$header64, $payload64, $sig64] = $parts;
    $header = json_decode(vue_auth_base64url_decode($header64), true);
    $payload = json_decode(vue_auth_base64url_decode($payload64), true);
    $signature = vue_auth_base64url_decode($sig64);

    if (!is_array($header) || !is_array($payload) || ($header['alg'] ?? '') !== 'HS256') {
        return new WP_Error('invalid_token', 'Invalid token header', ['status' => 401]);
    }

    $expected = hash_hmac('sha256', $header64 . '.' . $payload64, vue_auth_get_secret_key(), true);
    if (!hash_equals($expected, $signature)) {
        return new WP_Error('invalid_token', 'Token signature mismatch', ['status' => 401]);
    }

    $now = time();
    if (($payload['exp'] ?? 0) < $now) {
        return new WP_Error('expired_token', 'Token expired', ['status' => 401]);
    }
    if (($payload['nbf'] ?? 0) > $now) {
        return new WP_Error('invalid_token', 'Token not active yet', ['status' => 401]);
    }

    return $payload;
}

function vue_auth_get_auth_header_token()
{
    $header = '';
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $header = sanitize_text_field(wp_unslash($_SERVER['HTTP_AUTHORIZATION']));
    } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $header = sanitize_text_field(wp_unslash($_SERVER['REDIRECT_HTTP_AUTHORIZATION']));
    }
    if (stripos($header, 'Bearer ') === 0) {
        return trim(substr($header, 7));
    }
    return '';
}

function vue_auth_build_login_response($user)
{
    $token = vue_auth_create_token($user);
    return [
        'token' => $token,
        'user_id' => (int) $user->ID,
        'user_email' => $user->user_email,
        'user_nicename' => $user->user_nicename,
        'user_display_name' => $user->display_name,
        'roles' => array_values((array) $user->roles),
    ];
}

function vue_auth_normalize_mobile($mobile)
{
    $mobile = preg_replace('/\D+/', '', (string) $mobile);
    if (strpos($mobile, '0098') === 0) {
        $mobile = '0' . substr($mobile, 4);
    } elseif (strpos($mobile, '98') === 0) {
        $mobile = '0' . substr($mobile, 2);
    } elseif (strpos($mobile, '9') === 0) {
        $mobile = '0' . $mobile;
    }
    return $mobile;
}

function vue_auth_validate_mobile($mobile)
{
    return (bool) preg_match('/^09\d{9}$/', $mobile);
}

function vue_auth_find_user_by_mobile($mobile)
{
    global $wpdb;
    $meta_keys = ['mobile', 'phone', 'billing_phone'];
    $placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));
    $sql = "
        SELECT u.ID
        FROM {$wpdb->users} u
        INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
        WHERE um.meta_key IN ($placeholders) AND um.meta_value = %s
        LIMIT 1
    ";
    $prepared = $wpdb->prepare($sql, array_merge($meta_keys, [$mobile]));
    $user_id = (int) $wpdb->get_var($prepared); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    return $user_id ? get_user_by('id', $user_id) : null;
}

function vue_auth_hash_otp($otp)
{
    return hash_hmac('sha256', (string) $otp, vue_auth_get_secret_key());
}

function vue_auth_otp_transient_key($purpose, $mobile)
{
    return 'vueauth_otp_' . md5($purpose . ':' . $mobile);
}

function vue_auth_rate_limit_key($purpose, $mobile)
{
    return 'vueauth_rate_' . md5($purpose . ':' . $mobile);
}

function vue_auth_hourly_counter_key($purpose, $mobile)
{
    return 'vueauth_hourly_' . md5($purpose . ':' . $mobile);
}

function vue_auth_check_rate_limit($purpose, $mobile)
{
    $cooldown_key = vue_auth_rate_limit_key($purpose, $mobile);
    if (get_transient($cooldown_key)) {
        return new WP_Error('rate_limited', 'Please wait before requesting another code', ['status' => 429]);
    }

    $hourly_key = vue_auth_hourly_counter_key($purpose, $mobile);
    $hourly_count = (int) get_transient($hourly_key);
    if ($hourly_count >= VUE_AUTH_SMS_HOURLY_LIMIT) {
        return new WP_Error('rate_limited', 'SMS limit exceeded for this number', ['status' => 429]);
    }

    set_transient($cooldown_key, 1, VUE_AUTH_SMS_COOLDOWN_SECONDS);
    set_transient($hourly_key, $hourly_count + 1, HOUR_IN_SECONDS);
    return true;
}

function vue_auth_store_otp_record($purpose, $mobile, $record)
{
    $key = vue_auth_otp_transient_key($purpose, $mobile);
    set_transient($key, $record, VUE_AUTH_OTP_TTL);
}

function vue_auth_get_otp_record($purpose, $mobile)
{
    $key = vue_auth_otp_transient_key($purpose, $mobile);
    return get_transient($key);
}

function vue_auth_delete_otp_record($purpose, $mobile)
{
    $key = vue_auth_otp_transient_key($purpose, $mobile);
    delete_transient($key);
}

function vue_auth_send_otp($purpose, $mobile, $message_prefix, $extra = [])
{
    $check = vue_auth_check_rate_limit($purpose, $mobile);
    if (is_wp_error($check)) {
        return $check;
    }

    $otp = (string) random_int(100000, 999999);
    $record = array_merge($extra, [
        'purpose' => $purpose,
        'mobile' => $mobile,
        'otp_hash' => vue_auth_hash_otp($otp),
        'attempts' => 0,
        'created_at' => time(),
        'expires_at' => time() + VUE_AUTH_OTP_TTL,
    ]);
    vue_auth_store_otp_record($purpose, $mobile, $record);

    $service = vue_auth_get_sms_service();
    $message = $message_prefix . ' ' . $otp . "\n" . 'اعتبار: 3 دقیقه';
    $sent = $service->send($mobile, $message);
    if (is_wp_error($sent)) {
        return $sent;
    }

    return true;
}

function vue_auth_verify_otp($purpose, $mobile, $otp)
{
    $record = vue_auth_get_otp_record($purpose, $mobile);
    if (!$record || !is_array($record)) {
        return new WP_Error('otp_not_found', 'OTP not found or expired', ['status' => 400]);
    }

    if ((int) ($record['expires_at'] ?? 0) < time()) {
        vue_auth_delete_otp_record($purpose, $mobile);
        return new WP_Error('otp_expired', 'OTP expired', ['status' => 400]);
    }

    $attempts = (int) ($record['attempts'] ?? 0);
    if ($attempts >= VUE_AUTH_OTP_MAX_ATTEMPTS) {
        vue_auth_delete_otp_record($purpose, $mobile);
        return new WP_Error('otp_attempts', 'Too many invalid attempts', ['status' => 429]);
    }

    $incoming_hash = vue_auth_hash_otp($otp);
    if (!hash_equals((string) ($record['otp_hash'] ?? ''), $incoming_hash)) {
        $record['attempts'] = $attempts + 1;
        vue_auth_store_otp_record($purpose, $mobile, $record);
        return new WP_Error('otp_invalid', 'Invalid OTP code', ['status' => 400]);
    }

    return $record;
}

function vue_auth_login_password(WP_REST_Request $request)
{
    $username = sanitize_user((string) $request->get_param('username'));
    $password = (string) $request->get_param('password');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';

    if (empty($username) || empty($password)) {
        return new WP_Error('invalid_fields', 'Username and password are required', ['status' => 400]);
    }

    $attempt_key = 'vueauth_login_' . md5($ip . ':' . strtolower($username));
    $attempts = (int) get_transient($attempt_key);
    if ($attempts >= VUE_AUTH_LOGIN_ATTEMPT_LIMIT) {
        return new WP_Error('too_many_attempts', 'Too many login attempts', ['status' => 429]);
    }

    $user = wp_authenticate($username, $password);
    if (is_wp_error($user)) {
        set_transient($attempt_key, $attempts + 1, VUE_AUTH_LOGIN_ATTEMPT_WINDOW);
        return new WP_Error('invalid_credentials', 'Invalid username or password', ['status' => 401]);
    }
    delete_transient($attempt_key);

    return rest_ensure_response(vue_auth_build_login_response($user));
}

function vue_auth_login_otp_send(WP_REST_Request $request)
{
    $mobile = vue_auth_normalize_mobile($request->get_param('mobile'));
    if (!vue_auth_validate_mobile($mobile)) {
        return new WP_Error('invalid_mobile', 'Invalid mobile format', ['status' => 400]);
    }

    $user = vue_auth_find_user_by_mobile($mobile);
    if (!$user) {
        return new WP_Error('user_not_found', 'User not found', ['status' => 404]);
    }

    $sent = vue_auth_send_otp('login', $mobile, 'کد ورود شما:');
    if (is_wp_error($sent)) {
        return $sent;
    }

    return rest_ensure_response(['success' => true, 'message' => 'OTP sent']);
}

function vue_auth_login_otp_verify(WP_REST_Request $request)
{
    $mobile = vue_auth_normalize_mobile($request->get_param('mobile'));
    $otp = preg_replace('/\D+/', '', (string) $request->get_param('otp'));

    if (!vue_auth_validate_mobile($mobile) || strlen($otp) !== 6) {
        return new WP_Error('invalid_fields', 'Invalid mobile or OTP format', ['status' => 400]);
    }

    $verified = vue_auth_verify_otp('login', $mobile, $otp);
    if (is_wp_error($verified)) {
        return $verified;
    }

    $user = vue_auth_find_user_by_mobile($mobile);
    if (!$user) {
        vue_auth_delete_otp_record('login', $mobile);
        return new WP_Error('user_not_found', 'User not found', ['status' => 404]);
    }

    vue_auth_delete_otp_record('login', $mobile);
    return rest_ensure_response(vue_auth_build_login_response($user));
}

function vue_auth_register_otp_send(WP_REST_Request $request)
{
    $username = sanitize_user((string) $request->get_param('username'));
    $email = sanitize_email((string) $request->get_param('email'));
    $password = (string) $request->get_param('password');
    $mobile = vue_auth_normalize_mobile($request->get_param('mobile'));
    $meta = $request->get_param('meta');

    if (empty($username) || empty($email) || empty($password) || empty($mobile)) {
        return new WP_Error('invalid_fields', 'username, email, password, mobile are required', ['status' => 400]);
    }
    if (!is_email($email)) {
        return new WP_Error('invalid_email', 'Invalid email', ['status' => 400]);
    }
    if (!vue_auth_validate_mobile($mobile)) {
        return new WP_Error('invalid_mobile', 'Invalid mobile format', ['status' => 400]);
    }
    if (username_exists($username)) {
        return new WP_Error('username_exists', 'Username already exists', ['status' => 400]);
    }
    if (email_exists($email)) {
        return new WP_Error('email_exists', 'Email already exists', ['status' => 400]);
    }
    if (vue_auth_find_user_by_mobile($mobile)) {
        return new WP_Error('mobile_exists', 'Mobile already exists', ['status' => 400]);
    }

    $meta_payload = [];
    if (is_array($meta)) {
        foreach ($meta as $key => $value) {
            $meta_key = sanitize_key($key);
            if ($meta_key === '') {
                continue;
            }
            $meta_payload[$meta_key] = is_scalar($value) ? sanitize_text_field((string) $value) : $value;
        }
    }

    $pending_data = [
        'username' => $username,
        'email' => $email,
        'password' => $password,
        'mobile' => $mobile,
        'meta' => $meta_payload,
    ];


    $unique_check = vue_dashboard_validate_unique_meta($meta_payload, 0);
    if (is_wp_error($unique_check)) {
        return $unique_check;
    }
    $sent = vue_auth_send_otp('register', $mobile, 'کد ثبت نام شما:', ['pending' => $pending_data]);
    if (is_wp_error($sent)) {
        return $sent;
    }

    return rest_ensure_response(['success' => true, 'message' => 'Registration OTP sent']);
}

function vue_auth_register_otp_verify(WP_REST_Request $request)
{
    $mobile = vue_auth_normalize_mobile($request->get_param('mobile'));
    $otp = preg_replace('/\D+/', '', (string) $request->get_param('otp'));

    if (!vue_auth_validate_mobile($mobile) || strlen($otp) !== 6) {
        return new WP_Error('invalid_fields', 'Invalid mobile or OTP format', ['status' => 400]);
    }

    $verified = vue_auth_verify_otp('register', $mobile, $otp);
    if (is_wp_error($verified)) {
        return $verified;
    }

    $pending = $verified['pending'] ?? null;
    if (!is_array($pending)) {
        vue_auth_delete_otp_record('register', $mobile);
        return new WP_Error('invalid_pending', 'Registration data is missing', ['status' => 400]);
    }

    $unique_check = vue_dashboard_validate_unique_meta($pending['meta'] ?? [], 0);
    if (is_wp_error($unique_check)) {
        return $unique_check;
    }

    $user_id = wp_create_user($pending['username'], $pending['password'], $pending['email']);
    if (is_wp_error($user_id)) {
        return $user_id;
    }

    $user = new WP_User($user_id);
    $user->set_role('customer');

    update_user_meta($user_id, 'mobile', $pending['mobile']);
    if (!empty($pending['meta']) && is_array($pending['meta'])) {
        foreach ($pending['meta'] as $key => $value) {
            $meta_key = sanitize_key($key);
            if ($meta_key === '') {
                continue;
            }
            update_user_meta($user_id, $meta_key, $value);
        }
    }

    vue_auth_delete_otp_record('register', $mobile);
    $created_user = get_user_by('id', $user_id);
    return rest_ensure_response(vue_auth_build_login_response($created_user));
}

function vue_auth_validate_token_endpoint()
{
    $token = vue_auth_get_auth_header_token();
    if (!$token) {
        return new WP_Error('missing_token', 'Authorization token missing', ['status' => 401]);
    }
    $decoded = vue_auth_decode_token($token);
    if (is_wp_error($decoded)) {
        return $decoded;
    }
    return rest_ensure_response([
        'success' => true,
        'user_id' => (int) ($decoded['data']['user']['id'] ?? 0),
    ]);
}

function vue_auth_determine_current_user($user_id)
{
    if ($user_id) {
        return $user_id;
    }
    $token = vue_auth_get_auth_header_token();
    if (!$token) {
        return $user_id;
    }
    $decoded = vue_auth_decode_token($token);
    if (is_wp_error($decoded)) {
        return $user_id;
    }
    $decoded_user_id = (int) ($decoded['data']['user']['id'] ?? 0);
    return $decoded_user_id ?: $user_id;
}
add_filter('determine_current_user', 'vue_auth_determine_current_user', 20);

function vue_auth_wc_allow_customer_order_reads($permission, $context, $object_id, $post_type)
{
    if ($post_type !== 'shop_order' || $context !== 'read') {
        return $permission;
    }
    if ($permission) {
        return $permission;
    }
    if (!is_user_logged_in()) {
        return false;
    }
    if (current_user_can('manage_woocommerce')) {
        return true;
    }

    $current_user_id = get_current_user_id();
    if ($object_id) {
        $order = wc_get_order((int) $object_id);
        if (!$order) {
            return false;
        }
        return ((int) $order->get_customer_id()) === $current_user_id;
    }

    return true;
}
add_filter('woocommerce_rest_check_permissions', 'vue_auth_wc_allow_customer_order_reads', 10, 4);

function vue_auth_wc_limit_order_query_for_customers($args, $request)
{
    if (current_user_can('manage_woocommerce') || !is_user_logged_in()) {
        return $args;
    }
    $args['customer'] = get_current_user_id();
    return $args;
}
add_filter('woocommerce_rest_shop_order_object_query', 'vue_auth_wc_limit_order_query_for_customers', 10, 2);

function vue_dashboard_get_custom_fields()
{
    $schema = get_option(VUE_DASHBOARD_SCHEMA_OPTION, []);
    return rest_ensure_response(['schema' => is_array($schema) ? $schema : []]);
}

function vue_dashboard_get_unique_field_keys()
{
    $schema = get_option(VUE_DASHBOARD_SCHEMA_OPTION, []);
    $keys = [];
    if (!is_array($schema)) {
        return $keys;
    }
    foreach ($schema as $field) {
        if (!is_array($field)) {
            continue;
        }
        $key = sanitize_key($field['key'] ?? '');
        if ($key && !empty($field['unique'])) {
            $keys[] = $key;
        }
    }
    return $keys;
}

function vue_dashboard_is_meta_value_duplicate($meta_key, $meta_value, $exclude_user_id = 0)
{
    global $wpdb;
    if ($meta_value === '' || $meta_value === null) {
        return false;
    }
    $query = "
        SELECT user_id
        FROM {$wpdb->usermeta}
        WHERE meta_key = %s AND meta_value = %s
    ";
    $params = [$meta_key, (string) $meta_value];
    if ($exclude_user_id > 0) {
        $query .= " AND user_id != %d";
        $params[] = $exclude_user_id;
    }
    $query .= " LIMIT 1";
    $prepared = $wpdb->prepare($query, $params);
    $existing_user_id = (int) $wpdb->get_var($prepared); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    return $existing_user_id > 0;
}

function vue_dashboard_validate_unique_meta($meta_payload, $exclude_user_id = 0)
{
    if (!is_array($meta_payload)) {
        return true;
    }
    $unique_keys = vue_dashboard_get_unique_field_keys();
    if (empty($unique_keys)) {
        return true;
    }
    foreach ($unique_keys as $unique_key) {
        if (!array_key_exists($unique_key, $meta_payload)) {
            continue;
        }
        $value = $meta_payload[$unique_key];
        if (is_array($value)) {
            $value = wp_json_encode($value);
        }
        if (vue_dashboard_is_meta_value_duplicate($unique_key, $value, $exclude_user_id)) {
            return new WP_Error(
                'unique_meta_conflict',
                sprintf('Field "%s" must be unique and this value already exists', $unique_key),
                ['status' => 409]
            );
        }
    }
    return true;
}

function vue_dashboard_save_custom_fields(WP_REST_Request $request)
{
    $schema = $request->get_param('schema');
    if (!is_array($schema)) {
        return new WP_Error('invalid_schema', 'Schema must be array', ['status' => 400]);
    }
    update_option(VUE_DASHBOARD_SCHEMA_OPTION, $schema, false);
    return rest_ensure_response(['success' => true, 'schema' => $schema]);
}

function vue_dashboard_can_access_user_meta(WP_REST_Request $request)
{
    $requested_id = (int) $request['id'];
    $current_user_id = get_current_user_id();
    return current_user_can('list_users') || $current_user_id === $requested_id;
}

function vue_dashboard_get_user_meta(WP_REST_Request $request)
{
    $user_id = (int) $request['id'];
    $meta = get_user_meta($user_id);
    return rest_ensure_response(['meta' => $meta]);
}

function vue_dashboard_get_current_user_meta()
{
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('unauthorized', 'Unauthorized', ['status' => 401]);
    }
    return rest_ensure_response(['meta' => get_user_meta($user_id)]);
}

function vue_dashboard_update_user_meta(WP_REST_Request $request)
{
    $target = $request->get_param('user_id');
    $meta = $request->get_param('meta');
    $current_user_id = get_current_user_id();

    if (!is_array($meta)) {
        return new WP_Error('invalid_meta', 'Meta payload must be object', ['status' => 400]);
    }

    $user_id = ($target === 'me' || empty($target)) ? $current_user_id : (int) $target;
    if (!$user_id) {
        return new WP_Error('invalid_user', 'Invalid user', ['status' => 400]);
    }

    if (!current_user_can('list_users') && $current_user_id !== $user_id) {
        return new WP_Error('forbidden', 'Forbidden', ['status' => 403]);
    }

    $unique_check = vue_dashboard_validate_unique_meta($meta, $user_id);
    if (is_wp_error($unique_check)) {
        return $unique_check;
    }

    foreach ($meta as $key => $value) {
        $meta_key = sanitize_key($key);
        if ($meta_key === '') {
            continue;
        }
        update_user_meta($user_id, $meta_key, $value);
    }

    return rest_ensure_response(['success' => true, 'user_id' => $user_id]);
}

function vue_post_fields_register_routes()
{
    register_rest_route('custom-fields/v1', '/fields', [
        'methods' => 'GET',
        'callback' => 'vue_post_fields_rest_get_fields',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);

    register_rest_route('custom-fields/v1', '/fields/all', [
        'methods' => 'GET',
        'callback' => 'vue_post_fields_rest_get_all_fields',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);

    register_rest_route('custom-fields/v1', '/fields/all', [
        'methods' => 'POST',
        'callback' => 'vue_post_fields_rest_save_all_fields',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
    ]);

    register_rest_route('custom-fields/v1', '/post-types', [
        'methods' => 'GET',
        'callback' => 'vue_post_fields_rest_get_post_types',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);

    register_rest_route('custom-fields/v1', '/meta/(?P<post_id>\d+)', [
        'methods' => 'GET',
        'callback' => 'vue_post_fields_rest_get_meta',
        'permission_callback' => 'vue_post_fields_can_read_meta',
    ]);

    register_rest_route('custom-fields/v1', '/meta/(?P<post_id>\d+)', [
        'methods' => 'POST',
        'callback' => 'vue_post_fields_rest_save_meta',
        'permission_callback' => 'vue_post_fields_can_edit_meta',
    ]);
}
add_action('rest_api_init', 'vue_post_fields_register_routes');

function vue_post_fields_get_schema()
{
    $schema = get_option(VUE_POST_FIELDS_SCHEMA_OPTION, []);
    return is_array($schema) ? $schema : [];
}

function vue_post_fields_get_post_types()
{
    $objects = get_post_types(['show_ui' => true], 'objects');
    $post_types = [];
    foreach ($objects as $post_type => $object) {
        if ($post_type === 'attachment') {
            continue;
        }
        $post_types[] = [
            'value' => $post_type,
            'label' => $object->labels->singular_name ?: $post_type,
        ];
    }
    return $post_types;
}

function vue_post_fields_clean_options($options)
{
    if (!is_array($options)) {
        return [];
    }
    $clean = [];
    foreach ($options as $item) {
        $value = sanitize_text_field((string) $item);
        if ($value !== '') {
            $clean[] = $value;
        }
    }
    return array_values(array_unique($clean));
}

function vue_post_fields_validate_definition($field)
{
    $allowed_types = ['text', 'textarea', 'number', 'select', 'checkbox', 'image', 'repeatable'];
    $field_key = sanitize_key($field['field_key'] ?? '');
    $label = sanitize_text_field((string) ($field['label'] ?? ''));
    $type = sanitize_key($field['type'] ?? 'text');
    $post_types = is_array($field['post_types'] ?? null) ? $field['post_types'] : [];

    if ($field_key === '' || $label === '') {
        return new WP_Error('invalid_field', 'field_key and label are required', ['status' => 400]);
    }
    if (!in_array($type, $allowed_types, true)) {
        return new WP_Error('invalid_type', 'Unsupported field type', ['status' => 400]);
    }

    $normalized_post_types = [];
    $existing_post_types = get_post_types([], 'names');
    foreach ($post_types as $post_type) {
        $post_type = sanitize_key($post_type);
        if ($post_type && in_array($post_type, $existing_post_types, true)) {
            $normalized_post_types[] = $post_type;
        }
    }
    if (empty($normalized_post_types)) {
        return new WP_Error('invalid_post_types', 'At least one valid post type is required', ['status' => 400]);
    }

    $normalized = [
        'field_key' => $field_key,
        'label' => $label,
        'type' => $type,
        'post_types' => array_values(array_unique($normalized_post_types)),
        'required' => !empty($field['required']),
        'options' => vue_post_fields_clean_options($field['options'] ?? []),
        'min' => isset($field['min']) ? (float) $field['min'] : null,
        'max' => isset($field['max']) ? (float) $field['max'] : null,
        'order' => isset($field['order']) ? (int) $field['order'] : 0,
        'repeatable_type' => sanitize_key($field['repeatable_type'] ?? 'text'),
    ];

    if (!in_array($normalized['repeatable_type'], ['text', 'number', 'textarea', 'select'], true)) {
        $normalized['repeatable_type'] = 'text';
    }

    return $normalized;
}

function vue_post_fields_normalize_schema($schema)
{
    if (!is_array($schema)) {
        return new WP_Error('invalid_schema', 'Schema must be an array', ['status' => 400]);
    }

    $normalized = [];
    $seen_keys = [];
    foreach ($schema as $index => $field) {
        if (!is_array($field)) {
            continue;
        }
        $validated = vue_post_fields_validate_definition($field);
        if (is_wp_error($validated)) {
            return $validated;
        }
        if (isset($seen_keys[$validated['field_key']])) {
            return new WP_Error('duplicate_key', 'Duplicate field_key found: ' . $validated['field_key'], ['status' => 400]);
        }
        $seen_keys[$validated['field_key']] = true;
        if (!isset($validated['order']) || $validated['order'] <= 0) {
            $validated['order'] = $index;
        }
        $normalized[] = $validated;
    }

    usort($normalized, function ($a, $b) {
        return ((int) $a['order']) <=> ((int) $b['order']);
    });

    return $normalized;
}

function vue_post_fields_get_fields_for_post_type($post_type)
{
    $schema = vue_post_fields_get_schema();
    $fields = [];
    foreach ($schema as $field) {
        if (!is_array($field)) {
            continue;
        }
        $types = is_array($field['post_types'] ?? null) ? $field['post_types'] : [];
        if (in_array($post_type, $types, true)) {
            $fields[] = $field;
        }
    }
    usort($fields, function ($a, $b) {
        return ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0));
    });
    return $fields;
}

function vue_post_fields_contains_php_code($value)
{
    if (is_array($value)) {
        foreach ($value as $item) {
            if (vue_post_fields_contains_php_code($item)) {
                return true;
            }
        }
        return false;
    }
    return is_string($value) && preg_match('/<\?(php|=)?/i', $value);
}

function vue_post_fields_sanitize_single_value($field, $value)
{
    $type = $field['type'] ?? 'text';
    if ($type === 'repeatable') {
        if (!is_array($value)) {
            $value = $value === null || $value === '' ? [] : [(string) $value];
        }
        $sub_field = $field;
        $sub_field['type'] = $field['repeatable_type'] ?? 'text';
        $sanitized_items = [];
        foreach ($value as $item) {
            $item_sanitized = vue_post_fields_sanitize_single_value($sub_field, $item);
            if ($item_sanitized !== '' && $item_sanitized !== null) {
                $sanitized_items[] = $item_sanitized;
            }
        }
        return array_values($sanitized_items);
    }

    if ($type === 'checkbox') {
        return !empty($value) ? '1' : '0';
    }

    if ($type === 'number') {
        if ($value === '' || $value === null) {
            return '';
        }
        return is_numeric($value) ? (string) (0 + $value) : '';
    }

    if ($type === 'textarea') {
        return sanitize_textarea_field((string) $value);
    }

    if ($type === 'select') {
        $sanitized = sanitize_text_field((string) $value);
        $allowed = vue_post_fields_clean_options($field['options'] ?? []);
        if (!empty($allowed) && !in_array($sanitized, $allowed, true)) {
            return '';
        }
        return $sanitized;
    }

    if ($type === 'image') {
        if (is_numeric($value)) {
            return (int) $value;
        }
        return esc_url_raw((string) $value);
    }

    return sanitize_text_field((string) $value);
}

function vue_post_fields_validate_single_value($field, $value)
{
    if (vue_post_fields_contains_php_code($value)) {
        return new WP_Error('invalid_content', 'Field contains disallowed executable content', ['status' => 400]);
    }

    $type = $field['type'] ?? 'text';
    $required = !empty($field['required']);
    $is_empty = ($value === '' || $value === null || (is_array($value) && empty($value)));

    if ($required && $is_empty) {
        return new WP_Error('required_field', sprintf('Field %s is required', $field['field_key']), ['status' => 400]);
    }

    if (($type === 'number') && !$is_empty) {
        if (!is_numeric($value)) {
            return new WP_Error('invalid_number', sprintf('Field %s must be numeric', $field['field_key']), ['status' => 400]);
        }
        $number = (float) $value;
        if (isset($field['min']) && $field['min'] !== null && $number < (float) $field['min']) {
            return new WP_Error('min_number', sprintf('Field %s minimum is %s', $field['field_key'], $field['min']), ['status' => 400]);
        }
        if (isset($field['max']) && $field['max'] !== null && $number > (float) $field['max']) {
            return new WP_Error('max_number', sprintf('Field %s maximum is %s', $field['field_key'], $field['max']), ['status' => 400]);
        }
    }

    if (($type === 'select') && !$is_empty) {
        $allowed = vue_post_fields_clean_options($field['options'] ?? []);
        if (!empty($allowed) && !in_array((string) $value, $allowed, true)) {
            return new WP_Error('invalid_select', sprintf('Field %s has an invalid option', $field['field_key']), ['status' => 400]);
        }
    }

    if ($type === 'repeatable' && !is_array($value) && !$is_empty) {
        return new WP_Error('invalid_repeatable', sprintf('Field %s must be an array', $field['field_key']), ['status' => 400]);
    }

    return true;
}

function vue_post_fields_can_read_meta(WP_REST_Request $request)
{
    $post_id = (int) $request['post_id'];
    return current_user_can('edit_post', $post_id) || current_user_can('read_post', $post_id);
}

function vue_post_fields_can_edit_meta(WP_REST_Request $request)
{
    $post_id = (int) $request['post_id'];
    return current_user_can('edit_post', $post_id);
}

function vue_post_fields_rest_get_fields(WP_REST_Request $request)
{
    $post_type = sanitize_key((string) $request->get_param('post_type'));
    if ($post_type === '') {
        return new WP_Error('missing_post_type', 'post_type is required', ['status' => 400]);
    }
    return rest_ensure_response([
        'post_type' => $post_type,
        'fields' => vue_post_fields_get_fields_for_post_type($post_type),
    ]);
}

function vue_post_fields_rest_get_all_fields()
{
    return rest_ensure_response(['fields' => vue_post_fields_get_schema()]);
}

function vue_post_fields_rest_save_all_fields(WP_REST_Request $request)
{
    $schema = $request->get_param('fields');
    if (!is_array($schema)) {
        $schema = $request->get_param('schema');
    }
    $normalized = vue_post_fields_normalize_schema($schema);
    if (is_wp_error($normalized)) {
        return $normalized;
    }
    update_option(VUE_POST_FIELDS_SCHEMA_OPTION, $normalized, false);
    return rest_ensure_response(['success' => true, 'fields' => $normalized]);
}

function vue_post_fields_rest_get_post_types()
{
    return rest_ensure_response(['post_types' => vue_post_fields_get_post_types()]);
}

function vue_post_fields_rest_get_meta(WP_REST_Request $request)
{
    $post_id = (int) $request['post_id'];
    $post = get_post($post_id);
    if (!$post) {
        return new WP_Error('not_found', 'Post not found', ['status' => 404]);
    }
    $fields = vue_post_fields_get_fields_for_post_type($post->post_type);
    $meta = [];
    foreach ($fields as $field) {
        $key = $field['field_key'];
        $value = get_post_meta($post_id, $key, true);
        if (($field['type'] ?? '') === 'repeatable') {
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $value = $decoded;
                }
            }
            if (!is_array($value)) {
                $value = [];
            }
        }
        $meta[$key] = $value;
    }
    return rest_ensure_response([
        'post_id' => $post_id,
        'post_type' => $post->post_type,
        'meta' => $meta,
    ]);
}

function vue_post_fields_rest_save_meta(WP_REST_Request $request)
{
    $post_id = (int) $request['post_id'];
    $post = get_post($post_id);
    if (!$post) {
        return new WP_Error('not_found', 'Post not found', ['status' => 404]);
    }
    $payload = $request->get_param('meta');
    if (!is_array($payload)) {
        return new WP_Error('invalid_meta', 'meta payload must be object', ['status' => 400]);
    }

    $fields = vue_post_fields_get_fields_for_post_type($post->post_type);
    $allowed_by_key = [];
    foreach ($fields as $field) {
        $allowed_by_key[$field['field_key']] = $field;
    }

    foreach ($allowed_by_key as $field_key => $field) {
        $incoming = array_key_exists($field_key, $payload) ? $payload[$field_key] : null;
        $validation = vue_post_fields_validate_single_value($field, $incoming);
        if (is_wp_error($validation)) {
            return $validation;
        }

        $sanitized = vue_post_fields_sanitize_single_value($field, $incoming);
        if (($field['type'] ?? '') === 'repeatable') {
            update_post_meta($post_id, $field_key, wp_json_encode($sanitized));
        } else {
            update_post_meta($post_id, $field_key, $sanitized);
        }
    }

    return rest_ensure_response(['success' => true, 'post_id' => $post_id]);
}

function vue_post_fields_render_admin_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $schema = vue_post_fields_get_schema();
    $post_types = vue_post_fields_get_post_types();
    ?>
    <div class="wrap">
        <h1>Custom Post Fields Manager</h1>
        <p>Create fields for posts, products, and any custom post type.</p>

        <form method="post" action="">
            <?php wp_nonce_field('vue_post_fields_admin_save', 'vue_post_fields_nonce'); ?>
            <input type="hidden" name="vue_post_fields_action" value="save_schema" />
            <table class="widefat striped" id="vue-post-fields-table">
                <thead>
                    <tr>
                        <th>Field Key</th>
                        <th>Label</th>
                        <th>Type</th>
                        <th>Post Types</th>
                        <th>Required</th>
                        <th>Options (comma)</th>
                        <th>Repeatable Type</th>
                        <th>Order</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schema as $index => $field) : ?>
                        <tr>
                            <td><input type="text" name="fields[<?php echo esc_attr($index); ?>][field_key]" value="<?php echo esc_attr($field['field_key'] ?? ''); ?>" /></td>
                            <td><input type="text" name="fields[<?php echo esc_attr($index); ?>][label]" value="<?php echo esc_attr($field['label'] ?? ''); ?>" /></td>
                            <td>
                                <select name="fields[<?php echo esc_attr($index); ?>][type]">
                                    <?php foreach (['text', 'textarea', 'number', 'select', 'checkbox', 'image', 'repeatable'] as $type) : ?>
                                        <option value="<?php echo esc_attr($type); ?>" <?php selected(($field['type'] ?? 'text'), $type); ?>><?php echo esc_html($type); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select multiple name="fields[<?php echo esc_attr($index); ?>][post_types][]" style="min-width:150px;min-height:80px;">
                                    <?php foreach ($post_types as $post_type) : ?>
                                        <option value="<?php echo esc_attr($post_type['value']); ?>" <?php selected(in_array($post_type['value'], $field['post_types'] ?? [], true), true); ?>>
                                            <?php echo esc_html($post_type['label']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="checkbox" name="fields[<?php echo esc_attr($index); ?>][required]" value="1" <?php checked(!empty($field['required'])); ?> /></td>
                            <td><input type="text" name="fields[<?php echo esc_attr($index); ?>][options]" value="<?php echo esc_attr(implode(',', $field['options'] ?? [])); ?>" /></td>
                            <td>
                                <select name="fields[<?php echo esc_attr($index); ?>][repeatable_type]">
                                    <?php foreach (['text', 'number', 'textarea', 'select'] as $sub_type) : ?>
                                        <option value="<?php echo esc_attr($sub_type); ?>" <?php selected(($field['repeatable_type'] ?? 'text'), $sub_type); ?>><?php echo esc_html($sub_type); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="number" name="fields[<?php echo esc_attr($index); ?>][order]" value="<?php echo esc_attr((int) ($field['order'] ?? $index)); ?>" /></td>
                            <td><button type="button" class="button remove-row">Remove</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button type="button" class="button" id="vue-post-fields-add-row">Add Field</button>
                <button type="submit" class="button button-primary">Save Schema</button>
            </p>
        </form>
    </div>
    <script>
    (function () {
      const tableBody = document.querySelector("#vue-post-fields-table tbody");
      const addBtn = document.getElementById("vue-post-fields-add-row");
      if (!tableBody || !addBtn) return;
      const postTypesHtml = <?php echo wp_json_encode(implode('', array_map(function ($post_type) {
          return '<option value="' . esc_attr($post_type['value']) . '">' . esc_html($post_type['label']) . '</option>';
      }, $post_types))); ?>;

      const buildRow = (idx) => `
        <tr>
          <td><input type="text" name="fields[${idx}][field_key]" value="" /></td>
          <td><input type="text" name="fields[${idx}][label]" value="" /></td>
          <td>
            <select name="fields[${idx}][type]">
              <option value="text">text</option>
              <option value="textarea">textarea</option>
              <option value="number">number</option>
              <option value="select">select</option>
              <option value="checkbox">checkbox</option>
              <option value="image">image</option>
              <option value="repeatable">repeatable</option>
            </select>
          </td>
          <td><select multiple name="fields[${idx}][post_types][]" style="min-width:150px;min-height:80px;">${postTypesHtml}</select></td>
          <td><input type="checkbox" name="fields[${idx}][required]" value="1" /></td>
          <td><input type="text" name="fields[${idx}][options]" value="" /></td>
          <td>
            <select name="fields[${idx}][repeatable_type]">
              <option value="text">text</option>
              <option value="number">number</option>
              <option value="textarea">textarea</option>
              <option value="select">select</option>
            </select>
          </td>
          <td><input type="number" name="fields[${idx}][order]" value="${idx}" /></td>
          <td><button type="button" class="button remove-row">Remove</button></td>
        </tr>
      `;

      addBtn.addEventListener("click", function () {
        const idx = tableBody.querySelectorAll("tr").length;
        tableBody.insertAdjacentHTML("beforeend", buildRow(idx));
      });

      tableBody.addEventListener("click", function (e) {
        if (e.target.classList.contains("remove-row")) {
          e.target.closest("tr")?.remove();
        }
      });
    })();
    </script>
    <?php
}

function vue_post_fields_handle_admin_form_submit()
{
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }
    if (!isset($_POST['vue_post_fields_action']) || $_POST['vue_post_fields_action'] !== 'save_schema') {
        return;
    }
    check_admin_referer('vue_post_fields_admin_save', 'vue_post_fields_nonce');

    $raw_fields = isset($_POST['fields']) ? wp_unslash($_POST['fields']) : [];
    if (!is_array($raw_fields)) {
        $raw_fields = [];
    }
    $prepared = [];
    foreach ($raw_fields as $field) {
        if (!is_array($field)) {
            continue;
        }
        $prepared[] = [
            'field_key' => sanitize_key($field['field_key'] ?? ''),
            'label' => sanitize_text_field((string) ($field['label'] ?? '')),
            'type' => sanitize_key($field['type'] ?? 'text'),
            'post_types' => is_array($field['post_types'] ?? null) ? array_map('sanitize_key', $field['post_types']) : [],
            'required' => !empty($field['required']),
            'options' => vue_post_fields_clean_options(explode(',', (string) ($field['options'] ?? ''))),
            'repeatable_type' => sanitize_key($field['repeatable_type'] ?? 'text'),
            'order' => isset($field['order']) ? (int) $field['order'] : 0,
        ];
    }
    $normalized = vue_post_fields_normalize_schema($prepared);
    if (!is_wp_error($normalized)) {
        update_option(VUE_POST_FIELDS_SCHEMA_OPTION, $normalized, false);
    }
}
add_action('admin_init', 'vue_post_fields_handle_admin_form_submit');

function vue_post_fields_add_admin_page()
{
    add_menu_page(
        'Post Custom Fields',
        'Post Custom Fields',
        'manage_options',
        'vue-post-custom-fields',
        'vue_post_fields_render_admin_page',
        'dashicons-feedback',
        58
    );
}
add_action('admin_menu', 'vue_post_fields_add_admin_page');

function vue_post_fields_register_meta_boxes()
{
    $schema = vue_post_fields_get_schema();
    $post_types = [];
    foreach ($schema as $field) {
        foreach (($field['post_types'] ?? []) as $post_type) {
            $post_types[$post_type] = true;
        }
    }
    foreach (array_keys($post_types) as $post_type) {
        add_meta_box(
            'vue_post_custom_fields_box',
            'Custom Fields',
            'vue_post_fields_render_meta_box',
            $post_type,
            'normal',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'vue_post_fields_register_meta_boxes');

function vue_post_fields_render_field_input($field, $value, $name)
{
    $type = $field['type'] ?? 'text';
    if ($type === 'textarea') {
        echo '<textarea name="' . esc_attr($name) . '" style="width:100%;min-height:80px;">' . esc_textarea((string) $value) . '</textarea>';
        return;
    }
    if ($type === 'number') {
        echo '<input type="number" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '" />';
        return;
    }
    if ($type === 'select') {
        echo '<select name="' . esc_attr($name) . '">';
        echo '<option value="">Select</option>';
        foreach (($field['options'] ?? []) as $option) {
            echo '<option value="' . esc_attr($option) . '" ' . selected((string) $value, (string) $option, false) . '>' . esc_html($option) . '</option>';
        }
        echo '</select>';
        return;
    }
    if ($type === 'checkbox') {
        echo '<label><input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked(!empty($value), true, false) . ' /> Yes</label>';
        return;
    }
    if ($type === 'image') {
        echo '<input type="text" class="regular-text vue-post-image-url" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '" />';
        echo ' <button type="button" class="button vue-post-pick-image">Select Image</button>';
        return;
    }
    if ($type === 'repeatable') {
        $items = [];
        if (is_array($value)) {
            $items = $value;
        } elseif (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        }
        echo '<div class="vue-repeatable-list" data-name="' . esc_attr($name) . '">';
        if (empty($items)) {
            $items = [''];
        }
        foreach ($items as $item) {
            echo '<div style="margin-bottom:6px;display:flex;gap:6px;align-items:center;">';
            echo '<input type="text" name="' . esc_attr($name) . '[]" value="' . esc_attr((string) $item) . '" class="regular-text" />';
            echo '<button type="button" class="button vue-repeatable-remove">-</button>';
            echo '</div>';
        }
        echo '<button type="button" class="button vue-repeatable-add">Add Item</button>';
        echo '</div>';
        return;
    }
    echo '<input type="text" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '" class="regular-text" />';
}

function vue_post_fields_render_meta_box($post)
{
    wp_nonce_field('vue_post_fields_save_meta', 'vue_post_fields_nonce');
    $fields = vue_post_fields_get_fields_for_post_type($post->post_type);
    if (empty($fields)) {
        echo '<p>No custom fields assigned to this post type.</p>';
        return;
    }

    echo '<table class="form-table">';
    foreach ($fields as $field) {
        $key = $field['field_key'];
        $value = get_post_meta($post->ID, $key, true);
        echo '<tr>';
        echo '<th><label for="' . esc_attr($key) . '">' . esc_html($field['label']) . '</label></th>';
        echo '<td>';
        vue_post_fields_render_field_input($field, $value, 'vue_post_fields[' . $key . ']');
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
    ?>
    <script>
    (function () {
      if (typeof wp === "undefined" || !wp.media) return;
      document.querySelectorAll(".vue-post-pick-image").forEach(function (btn) {
        btn.addEventListener("click", function () {
          const input = btn.parentElement.querySelector(".vue-post-image-url");
          const frame = wp.media({ title: "Select image", button: { text: "Use image" }, multiple: false });
          frame.on("select", function () {
            const attachment = frame.state().get("selection").first().toJSON();
            if (input) input.value = attachment.id || attachment.url || "";
          });
          frame.open();
        });
      });
      document.querySelectorAll(".vue-repeatable-list").forEach(function (container) {
        container.addEventListener("click", function (e) {
          if (e.target.classList.contains("vue-repeatable-add")) {
            const row = document.createElement("div");
            row.style.marginBottom = "6px";
            row.style.display = "flex";
            row.style.gap = "6px";
            row.style.alignItems = "center";
            row.innerHTML = '<input type="text" name="' + container.dataset.name + '[]" value="" class="regular-text" /><button type="button" class="button vue-repeatable-remove">-</button>';
            container.insertBefore(row, e.target);
          }
          if (e.target.classList.contains("vue-repeatable-remove")) {
            e.target.parentElement.remove();
          }
        });
      });
    })();
    </script>
    <?php
}

function vue_post_fields_save_post_meta($post_id, $post)
{
    if (!isset($_POST['vue_post_fields_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vue_post_fields_nonce'])), 'vue_post_fields_save_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (!is_object($post)) {
        $post = get_post($post_id);
    }
    if (!$post) {
        return;
    }
    $incoming = isset($_POST['vue_post_fields']) && is_array($_POST['vue_post_fields']) ? wp_unslash($_POST['vue_post_fields']) : [];
    $fields = vue_post_fields_get_fields_for_post_type($post->post_type);
    foreach ($fields as $field) {
        $key = $field['field_key'];
        $value = array_key_exists($key, $incoming) ? $incoming[$key] : null;
        $validation = vue_post_fields_validate_single_value($field, $value);
        if (is_wp_error($validation)) {
            continue;
        }
        $sanitized = vue_post_fields_sanitize_single_value($field, $value);
        if (($field['type'] ?? '') === 'repeatable') {
            update_post_meta($post_id, $key, wp_json_encode($sanitized));
        } else {
            update_post_meta($post_id, $key, $sanitized);
        }
    }
}
add_action('save_post', 'vue_post_fields_save_post_meta', 10, 2);

function vue_post_fields_prepare_custom_fields_for_post($post_id, $post_type)
{
    $fields = vue_post_fields_get_fields_for_post_type($post_type);
    $output = [];
    foreach ($fields as $field) {
        $key = $field['field_key'];
        $value = get_post_meta($post_id, $key, true);
        if (($field['type'] ?? '') === 'repeatable') {
            if (is_string($value) && $value !== '') {
                $decoded = json_decode($value, true);
                $value = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($value)) {
                $value = [];
            }
        }
        $output[$key] = $value;
    }
    return $output;
}

function vue_post_fields_add_to_wc_response($response, $object)
{
    if (!($response instanceof WP_REST_Response)) {
        return $response;
    }
    if (!$object || !method_exists($object, 'get_id')) {
        return $response;
    }
    $product_id = (int) $object->get_id();
    if ($product_id <= 0) {
        return $response;
    }
    $custom_fields = vue_post_fields_prepare_custom_fields_for_post($product_id, 'product');
    $data = $response->get_data();
    $data['custom_fields'] = $custom_fields;
    if (!isset($data['meta_data']) || !is_array($data['meta_data'])) {
        $data['meta_data'] = [];
    }
    foreach ($custom_fields as $key => $value) {
        $data['meta_data'][] = [
            'key' => $key,
            'value' => $value,
        ];
    }
    $response->set_data($data);
    return $response;
}
add_filter('woocommerce_rest_prepare_product_object', 'vue_post_fields_add_to_wc_response', 10, 2);
