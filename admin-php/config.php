<?php
/**
 * Ketupat MLBB Admin Console - Core Configuration & Supabase REST Engine
 * Highest Access Mode (Service Role / Secret Key Supported)
 */

define('CONFIG_FILE', __DIR__ . '/config.json');

// Initialize secure session for Admin Gatekeeper
if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.use_only_cookies', '1');
    session_start();
}

// Default fallback configuration
$DEFAULT_CONFIG = [
    'supabase_url'        => 'https://uatqaxxfzmpxkeeoeoin.supabase.co',
    'supabase_anon_key'   => 'sb_publishable_tRQMoLxGUIrWGcq2ZfEQRQ_CH3AufnW',
    'supabase_secret_key' => '',
    'dlyyz_api_key'       => 'dlyyz-rest.apikey:dhzzyx95b66a0d83364a12aa99e121ef35325f',
    'admin_pin'           => '123456'
];

/**
 * Load configuration from config.json with fallback defaults
 */
function getAppConfig() {
    global $DEFAULT_CONFIG;
    if (!is_array($DEFAULT_CONFIG)) {
        $DEFAULT_CONFIG = [
            'supabase_url'        => 'https://uatqaxxfzmpxkeeoeoin.supabase.co',
            'supabase_anon_key'   => 'sb_publishable_tRQMoLxGUIrWGcq2ZfEQRQ_CH3AufnW',
            'supabase_secret_key' => '',
            'dlyyz_api_key'       => 'dlyyz-rest.apikey:dhzzyx95b66a0d83364a12aa99e121ef35325f',
            'admin_pin'           => '123456'
        ];
    }
    if (file_exists(CONFIG_FILE)) {
        $raw = file_get_contents(CONFIG_FILE);
        $data = json_decode($raw, true);
        if (is_array($data)) {
            return array_merge($DEFAULT_CONFIG, $data);
        }
    }
    return $DEFAULT_CONFIG;
}

/**
 * Save configuration to config.json
 */
function saveAppConfig($newConfig) {
    $current = getAppConfig();
    $merged = array_merge($current, $newConfig);
    $result = file_put_contents(CONFIG_FILE, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return $result !== false;
}

/**
 * Check if the current user/request is authenticated as admin
 */
function isAdminAuthenticated() {
    // 1. Check active PHP session
    if (!empty($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
        return true;
    }

    // 2. Check 30-day Remember-Me Cookie (Passkey token)
    if (!empty($_COOKIE['ketupat_admin_passkey_token'])) {
        $cookieToken = $_COOKIE['ketupat_admin_passkey_token'];
        $cfg = getAppConfig();
        $secretSalt = ($cfg['supabase_secret_key'] ?? '') . '_passkey_salt_998';
        $expectedToken = hash_hmac('sha256', 'ketupat_webauthn_auth', $secretSalt);
        if (hash_equals($expectedToken, $cookieToken)) {
            $_SESSION['admin_authenticated'] = true;
            return true;
        }
    }

    return false;
}

/**
 * Establish authenticated admin session after FIDO2 Passkey verification
 */
function loginAdminWithPasskey($remember = true) {
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_login_time'] = time();

    if ($remember) {
        $cfg = getAppConfig();
        $secretSalt = ($cfg['supabase_secret_key'] ?? '') . '_passkey_salt_998';
        $token = hash_hmac('sha256', 'ketupat_webauthn_auth', $secretSalt);
        @setcookie('ketupat_admin_passkey_token', $token, [
            'expires' => time() + (86400 * 30), // 30 days
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
        ]);
    }
    return true;
}

/**
 * Legacy PIN login (permanently disabled)
 */
function loginAdmin($passkey, $remember = true) {
    return false;
}

/**
 * Perform logout
 */
function logoutAdmin() {
    $_SESSION['admin_authenticated'] = false;
    unset($_SESSION['admin_authenticated']);
    unset($_SESSION['admin_login_time']);

    @setcookie('ketupat_admin_passkey_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_destroy();
    }
}

/**
 * Execute HTTP Request to Supabase REST API with Highest Access Privileges
 *
 * @param string $endpoint e.g. "users?select=*", "redemptions", "giveaway_entries"
 * @param string $method GET, POST, PATCH, PUT, DELETE
 * @param mixed $body Payload to send
 * @param array $extraHeaders Custom headers (e.g. Prefer: resolution=merge-duplicates)
 * @return array ['success' => bool, 'code' => int, 'data' => mixed, 'error' => string|null]
 */
function supabaseApiRequest($endpoint, $method = 'GET', $body = null, $extraHeaders = []) {
    $config = getAppConfig();
    $baseUrl = rtrim($config['supabase_url'], '/');
    $baseUrl = preg_replace('/\/rest\/v1\/?$/', '', $baseUrl);
    $url = $baseUrl . '/rest/v1/' . ltrim($endpoint, '/');

    // Use Secret Key if set for HIGHEST ACCESS (bypasses RLS), else fallback to publishable key
    $secretKey = trim($config['supabase_secret_key'] ?? '');
    $anonKey   = trim($config['supabase_anon_key'] ?? '');
    $activeKey = !empty($secretKey) ? $secretKey : $anonKey;

    $headers = [
        'apikey: ' . $activeKey,
        'Authorization: Bearer ' . $activeKey,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];

    foreach ($extraHeaders as $k => $v) {
        $headers[] = is_numeric($k) ? $v : "$k: $v";
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    if ($body !== null) {
        $jsonPayload = is_string($body) ? $body : json_encode($body);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return [
            'success' => false,
            'code'    => 0,
            'data'    => null,
            'error'   => 'cURL Connection Error: ' . $curlError
        ];
    }

    $decoded = json_decode($response, true);
    $isSuccess = ($httpCode >= 200 && $httpCode < 300);

    return [
        'success'   => $isSuccess,
        'code'      => $httpCode,
        'data'      => ($decoded !== null) ? $decoded : $response,
        'error'     => !$isSuccess ? ($decoded['message'] ?? $decoded['error'] ?? $response) : null,
        'is_secret' => !empty($secretKey)
    ];
}
