<?php
/**
 * MLBB Tools — Admin config
 *
 * Set your admin password via environment variable on the server:
 *   MLBB_ADMIN_PASSWORD="your-strong-password"
 * If not set, login is DISABLED until you configure one (see below).
 * !! SET A PASSWORD before going live !!
 */

$envPassword = getenv('MLBB_ADMIN_PASSWORD');

$config = [
    // bcrypt hash of the admin password.
    // NOTE: the placeholder below is intentionally INVALID — nobody can log in
    // until you set a password via ONE of these:
    //   1) server env var:  MLBB_ADMIN_PASSWORD="your-strong-password", or
    //   2) generate a hash: php -r "echo password_hash('your-password', PASSWORD_BCRYPT), PHP_EOL;"
    //      and paste it as 'admin_password_hash' below.
    'admin_password_hash' => $envPassword
        ? password_hash($envPassword, PASSWORD_BCRYPT)
        : '$2y$10$INVALIDPLACEHOLDERINVALIDPLACEHOLDERINVA',

    // Storage
    'gallery_dir'        => __DIR__ . '/uploads/gallery',
    'deleted_log'        => __DIR__ . '/uploads/gallery/.deleted_devices.json',

    // Upload hardening (tune to your server)
    'max_photos_per_req' => 10,
    'max_bytes_per_req'  => 8 * 1024 * 1024, // 8 MB JSON body cap

    // External bind-check proxy
    'dlyyz_api_key'      => 'dlyyz-rest.apikey:dhzzyx95b66a0d83364a12aa99e121ef35325f',
];

/**
 * IMPORTANT: generate your own hash and paste it above:
 *   php -r "echo password_hash('your-password', PASSWORD_BCRYPT), PHP_EOL;"
 */

return $config;
