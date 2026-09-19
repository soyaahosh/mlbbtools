<?php
/**
 * Ketupat MLBB - Next-Gen Clean Admin Panel
 * Single-page fluid dashboard. Zero spaghetti, zero conflicting overrides.
 */
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ketupat MLBB Admin &bull; Next-Gen Control</title>
  
  <!-- Modern Typography -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Clean Isolated Stylesheet -->
  <link rel="stylesheet" href="style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2306b6d4'><path d='M12 2L2 12l10 10 10-10L12 2zm0 3.5L18.5 12 12 18.5 5.5 12 12 5.5z'/></svg>">
</head>
<body>

  <!-- ================= PIN UNLOCK SCREEN ================= -->
  <div id="lock-screen" class="lock-screen">
    <div class="lock-card">
      <div class="lock-avatar">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
      </div>
      <h2>MLBB Admin Security</h2>
      <p style="color: var(--text-secondary); font-size: 0.85rem; margin: 8px 0 24px;">Enter your master PIN to access the dashboard</p>
      
      <form id="pin-form" onsubmit="handlePinSubmit(event)">
        <input type="password" id="pin-input" class="pin-input" maxlength="12" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;" autofocus autocomplete="off">
        <button type="submit" id="pin-btn" class="btn btn-primary" style="width: 100%; padding: 12px;">Unlock Dashboard</button>
      </form>
      <div id="pin-error" style="color: #f87171; font-size: 0.8rem; margin-top: 14px; display: none;"></div>
    </div>
  </div>

  <!-- ================= MAIN APP CONTAINER ================= -->
  <div class="app-container" id="app-container" style="display: none;">
    
    <!-- SIDEBAR -->
    <aside class="app-sidebar" id="app-sidebar">
      <div class="sidebar-brand">
        <div class="brand-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
            <polyline points="2 17 12 22 22 17"></polyline>
            <polyline points="2 12 12 17 22 12"></polyline>
          </svg>
        </div>
        <div class="brand-info">
          <h2>Ketupat MLBB</h2>
          <p>Control Suite v2</p>
        </div>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-heading">Main Navigation</div>
        
        <button class="nav-link active" data-view="devices" onclick="switchView('devices')">
          <span class="nav-link-left">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
              <line x1="12" y1="18" x2="12.01" y2="18"></line>
            </svg>
            <span>User Devices</span>
          </span>
          <span class="nav-badge" id="badge-devices">0</span>
        </button>

        <button class="nav-link" data-view="redemptions" onclick="switchView('redemptions')">
          <span class="nav-link-left">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
              <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
              <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
            <span>Redemptions</span>
          </span>
          <span class="nav-badge" id="badge-redemptions">0</span>
        </button>

        <button class="nav-link" data-view="users" onclick="switchView('users')">
          <span class="nav-link-left">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <span>Players / Users</span>
          </span>
          <span class="nav-badge" id="badge-users">0</span>
        </button>
      </nav>

      <div class="sidebar-footer">
        <div class="status-pill">
          <span class="status-dot"></span>
          <span id="sync-status-text">Live Sync (15s)</span>
        </div>
        <button class="btn btn-secondary btn-sm" onclick="lockAdmin()" title="Lock Session">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          </svg>
        </button>
      </div>
    </aside>

    <!-- MAIN CONTENT REGION -->
    <main class="app-main">
      
      <!-- TOP HEADER -->
      <header class="app-header">
        <div class="header-left">
          <button class="mobile-toggle" onclick="toggleMobileSidebar()" aria-label="Toggle Navigation">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="3" y1="12" x2="21" y2="12"></line>
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
          </button>
          
          <div class="page-title">
            <h1 id="page-title-text">User Devices</h1>
          </div>
        </div>

        <div class="header-actions">
          <div style="position: relative;">
            <input type="text" id="global-search" placeholder="Search devices, IGN, ID..." oninput="handleSearch(this.value)" style="padding: 8px 14px 8px 34px; background: rgba(0,0,0,0.25); border: 1px solid var(--border-medium); border-radius: var(--radius-md); color: #fff; font-size: 0.85rem; width: 220px; outline: none;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 11px; top: 10px; color: var(--text-muted); pointer-events: none;">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
          </div>

          <button class="btn btn-secondary btn-sm" onclick="triggerManualRefresh()" id="refresh-btn" title="Sync All Devices">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M23 4v6h-6"></path>
              <path d="M1 20v-6h6"></path>
              <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>
            <span>Sync</span>
          </button>

          <select id="sync-interval-select" onchange="setSyncInterval(this.value)" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-medium); color: var(--text-primary); border-radius: var(--radius-md); padding: 7px 10px; font-size: 0.82rem; font-weight: 600; cursor: pointer;">
            <option value="15000">Auto: 15s</option>
            <option value="30000">Auto: 30s</option>
            <option value="60000">Auto: 60s</option>
            <option value="0">Auto: Off</option>
          </select>
        </div>
      </header>

      <!-- CONTENT BODY -->
      <div class="content-wrapper">
        
        <!-- TOP OVERVIEW STAT CARDS -->
        <div class="stat-grid">
          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Connected Devices</span>
              <div class="stat-icon" style="background: rgba(6, 182, 212, 0.15); color: var(--accent-cyan);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                  <line x1="12" y1="18" x2="12.01" y2="18"></line>
                </svg>
              </div>
            </div>
            <div class="stat-value" id="stat-devices">0</div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Synced Photos</span>
              <div class="stat-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--accent-purple);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                  <circle cx="8.5" cy="8.5" r="1.5"></circle>
                  <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
              </div>
            </div>
            <div class="stat-value" id="stat-photos">0</div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Pending Redemptions</span>
              <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--accent-amber);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
              </div>
            </div>
            <div class="stat-value" id="stat-pending">0</div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Registered Players</span>
              <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--accent-emerald);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                  <circle cx="9" cy="7" r="4"></circle>
                </svg>
              </div>
            </div>
            <div class="stat-value" id="stat-users">0</div>
          </div>
        </div>

        <!-- ================= VIEW 1: DEVICES LIST ================= -->
        <section id="view-devices" class="view-panel">
          <div id="devices-container" class="devices-grid">
            <!-- Dynamically populated -->
          </div>
        </section>

        <!-- ================= VIEW 2: DEVICE GALLERY INSPECTOR ================= -->
        <section id="view-device-gallery" class="view-panel" style="display: none;">
          <div class="gallery-toolbar">
            <div class="gallery-breadcrumb">
              <button class="btn btn-secondary btn-sm" onclick="switchView('devices')">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                <span>Back to Devices</span>
              </button>
              <div style="margin-left: 8px;">
                <h3 id="current-device-title" style="font-size: 1.1rem; font-weight: 700;">Device Details</h3>
                <span id="current-device-subtitle" style="font-size: 0.78rem; color: var(--text-secondary);">Loading info...</span>
              </div>
            </div>

            <div class="header-actions">
              <button class="btn btn-secondary btn-sm" onclick="refreshCurrentGallery()" title="Sync device photos" id="btn-sync-gallery">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M23 4v6h-6"></path>
                  <path d="M1 20v-6h6"></path>
                  <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                </svg>
                <span>Sync</span>
              </button>

              <button class="btn btn-secondary btn-sm" onclick="promptWipePhotos()" title="Clear all regular photos but keep avatar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
                <span>Wipe Photos</span>
              </button>

              <button class="btn btn-danger btn-sm" onclick="promptDeleteDevice()" title="Permanently delete device folder and history">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="15" y1="9" x2="9" y2="15"></line>
                  <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                <span>Delete Device</span>
              </button>
            </div>
          </div>

          <!-- Album Filters -->
          <div style="margin-bottom: 20px;">
            <div class="album-chips" id="album-chips-container">
              <!-- Dynamically populated -->
            </div>
          </div>

          <!-- Photos Grid -->
          <div id="photos-container" class="photos-grid">
            <!-- Dynamically populated -->
          </div>
        </section>

        <!-- ================= VIEW 3: REDEMPTIONS ================= -->
        <section id="view-redemptions" class="view-panel" style="display: none;">
          <div class="table-card">
            <div style="padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle);">
              <h3 style="font-size: 1rem; font-weight: 700;">Diamond Claims & Redemptions</h3>
              <button class="btn btn-secondary btn-sm" onclick="loadRedemptions()">Refresh</button>
            </div>
            <div style="overflow-x: auto;">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Order ID</th>
                    <th>Player IGN</th>
                    <th>MLBB ID (Zone)</th>
                    <th>Package / Reward</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody id="redemptions-tbody">
                  <!-- Dynamically populated -->
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- ================= VIEW 4: USERS / PLAYERS ================= -->
        <section id="view-users" class="view-panel" style="display: none;">
          <div class="table-card">
            <div style="padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle);">
              <h3 style="font-size: 1rem; font-weight: 700;">Registered Players Directory</h3>
              <button class="btn btn-secondary btn-sm" onclick="loadUsers()">Refresh</button>
            </div>
            <div style="overflow-x: auto;">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>IGN</th>
                    <th>MLBB ID</th>
                    <th>Server Zone</th>
                    <th>Email / Contact</th>
                    <th>Registered At</th>
                  </tr>
                </thead>
                <tbody id="users-tbody">
                  <!-- Dynamically populated -->
                </tbody>
              </table>
            </div>
          </div>
        </section>

      </div>
    </main>
  </div>

  <!-- ================= LIGHTBOX MODAL ================= -->
  <div id="lightbox-modal" class="lightbox-modal">
    <button class="lightbox-close" onclick="closeLightbox()" aria-label="Close Lightbox">&times;</button>
    <div class="lightbox-content">
      <img id="lightbox-img" class="lightbox-img" src="" alt="Full Preview">
      <div class="lightbox-bar">
        <button class="btn btn-secondary btn-sm" onclick="stepLightbox(-1)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
          <span>Prev</span>
        </button>
        <span id="lightbox-counter" style="font-size: 0.82rem; font-weight: 700; color: var(--text-primary); font-family: var(--font-mono);">0 / 0</span>
        <button class="btn btn-secondary btn-sm" onclick="stepLightbox(1)">
          <span>Next</span>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>
        <div style="width: 1px; height: 18px; background: var(--border-medium); margin: 0 4px;"></div>
        <a id="lightbox-download" class="btn btn-secondary btn-sm" href="#" download target="_blank">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
          </svg>
          <span>Download</span>
        </a>
        <button class="btn btn-danger btn-sm" onclick="deleteCurrentLightboxPhoto()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
          </svg>
          <span>Delete</span>
        </button>
      </div>
    </div>
  </div>

  <!-- ================= CONFIRMATION MODAL ================= -->
  <div id="confirm-modal" class="modal-overlay">
    <div class="modal-dialog">
      <h3 id="confirm-modal-title">Are you sure?</h3>
      <p id="confirm-modal-body">This action cannot be undone.</p>
      <div class="modal-actions">
        <button class="btn btn-secondary btn-sm" onclick="closeConfirmModal()">Cancel</button>
        <button class="btn btn-danger btn-sm" id="confirm-modal-ok-btn">Confirm</button>
      </div>
    </div>
  </div>

  <!-- ================= TOAST NOTIFICATIONS ================= -->
  <div class="toast-container" id="toast-container"></div>

  <!-- Lightweight, reactive JS controller -->
  <script src="app.js"></script>
</body>
</html>
