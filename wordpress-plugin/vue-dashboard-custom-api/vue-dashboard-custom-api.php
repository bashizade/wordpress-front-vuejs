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
