/**
 * Ketupat MLBB - PHP Admin Console Front-End Controller
 * Communicates with backend api.php with Highest Access support
 */

// Application State
const adminState = {
  currentView: "overview",
  stats: {},
  users: [],
  redemptions: [],
  giveaways: [],
  galleryPhotos: [],
  selectedGalleryEmail: "",
  selectedGalleryDeviceId: "",
  gallerySubView: "grid", // "grid" | "detail"
  galleryActiveTab: "gallery", // "gallery" | "device_info" | "mlbb_info"
  galleryDevices: [],
  galleryFilterStatus: "all",
  gallerySearchQuery: "",
  activeDeviceDetail: null,
  lightboxIndex: 0,
  activePool: "all",
  autoRefreshInterval: null,
  pendingDeleteAction: null
};

// --- INITIALIZATION ---
document.addEventListener("DOMContentLoaded", () => {
  setupNavigation();
  setupEventListeners();
  loadConfig();
  refreshAll();
  setupAutoRefresh();
});

// --- NAVIGATION & VIEWS ---
function setupNavigation() {
  document.querySelectorAll(".nav-item-link").forEach(link => {
    link.addEventListener("click", () => {
      const view = link.getAttribute("data-view");
      if (view === "gallery") {
        adminState.gallerySubView = "grid";
        adminState.selectedGalleryDeviceId = "";
        backToDevicesGrid();
      }
      if (view) switchView(view);
    });
  });

  // Mobile sidebar controls
  const btnMobileMenu = document.getElementById("btnMobileMenu");
  const sidebar = document.getElementById("sidebar");
  const sidebarBackdrop = document.getElementById("sidebarBackdrop");

  function closeMobileSidebar() {
    if (sidebar) sidebar.classList.remove("mobile-open");
    if (sidebarBackdrop) sidebarBackdrop.classList.remove("active");
  }

  function toggleMobileSidebar(e) {
    if (e) {
      e.stopPropagation();
      e.preventDefault();
    }
    if (!sidebar) return;
    const isOpen = sidebar.classList.toggle("mobile-open");
    if (sidebarBackdrop) sidebarBackdrop.classList.toggle("active", isOpen);
  }

  if (btnMobileMenu && sidebar) {
    btnMobileMenu.addEventListener("click", toggleMobileSidebar);
  }

  if (sidebarBackdrop) {
    sidebarBackdrop.addEventListener("click", closeMobileSidebar);
    sidebarBackdrop.addEventListener("touchstart", closeMobileSidebar, { passive: true });
  }

  // Click outside sidebar to close
  document.addEventListener("click", (e) => {
    if (sidebar && sidebar.classList.contains("mobile-open")) {
      if (!sidebar.contains(e.target) && (!btnMobileMenu || !btnMobileMenu.contains(e.target))) {
        closeMobileSidebar();
      }
    }
  });

  // Touch outside sidebar to close
  document.addEventListener("touchstart", (e) => {
    if (sidebar && sidebar.classList.contains("mobile-open")) {
      if (!sidebar.contains(e.target) && (!btnMobileMenu || !btnMobileMenu.contains(e.target))) {
        closeMobileSidebar();
      }
    }
  }, { passive: true });

  // Escape key closes mobile sidebar
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && sidebar && sidebar.classList.contains("mobile-open")) {
      closeMobileSidebar();
    }
  });
}

function switchView(viewName) {
  adminState.currentView = viewName;

  // Update navigation link highlights
  document.querySelectorAll(".nav-item-link").forEach(link => {
    link.classList.toggle("active", link.getAttribute("data-view") === viewName);
  });

  // Switch view containers
  document.querySelectorAll(".view-section").forEach(sec => {
    sec.classList.remove("active");
  });

  const targetView = document.getElementById(
    "view" + viewName.charAt(0).toUpperCase() + viewName.slice(1)
  );
  if (targetView) {
    targetView.classList.add("active");
  }

  // Update page heading
  const headings = {
    overview: "Dashboard Overview",
    redemptions: "Diamond Redemptions Manager",
    users: "Users & Players Directory",
    giveaways: "Giveaway Arena Manager",
    gallery: "User Device Gallery Browser",
    settings: "Cloud & API Settings"
  };
  const headingEl = document.getElementById("pageTitleHeading");
  if (headingEl) {
    headingEl.textContent = headings[viewName] || "Admin Console";
  }

  // Load view-specific data
  if (viewName === "overview") fetchStats();
  if (viewName === "redemptions") fetchRedemptions();
  if (viewName === "users") fetchUsers();
  if (viewName === "giveaways") fetchGiveaways();
  if (viewName === "gallery") fetchGalleryOverview();
  if (viewName === "settings") {
    loadConfig();
    loadRegisteredPasskeys();
  }

  // Adjust auto-refresh frequency dynamically based on view
  setupAutoRefresh();

  // Close mobile sidebar if open
  const sidebar = document.getElementById("sidebar");
  const sidebarBackdrop = document.getElementById("sidebarBackdrop");
  if (sidebar) sidebar.classList.remove("mobile-open");
  if (sidebarBackdrop) sidebarBackdrop.classList.remove("active");
}

// --- EVENT LISTENERS ---
function setupEventListeners() {
  // Sync Data button
  const btnRefreshAll = document.getElementById("btnRefreshAll");
  if (btnRefreshAll) {
    btnRefreshAll.addEventListener("click", () => {
      refreshAll(true);
    });
  }

  // Auto-refresh frequency select
  const selectAutoRefresh = document.getElementById("selectAutoRefresh");
  if (selectAutoRefresh) {
    selectAutoRefresh.addEventListener("change", e => {
      setupAutoRefresh(parseInt(e.target.value, 10));
    });
  }

  // Toggle Secret Key visibility
  const btnToggleSecretKey = document.getElementById("btnToggleSecretKey");
  const inputSecret = document.getElementById("inputSupabaseSecretKey");
  if (btnToggleSecretKey && inputSecret) {
    btnToggleSecretKey.addEventListener("click", () => {
      inputSecret.type = inputSecret.type === "password" ? "text" : "password";
      btnToggleSecretKey.querySelector("span").textContent =
        inputSecret.type === "password" ? "visibility" : "visibility_off";
    });
  }

  // Cloud Settings Form Submit
  const formCloudSettings = document.getElementById("formCloudSettings");
  if (formCloudSettings) {
    formCloudSettings.addEventListener("submit", handleSaveCloudSettings);
  }

  // Test Supabase Connection Button
  const btnTestSupabaseConnection = document.getElementById("btnTestSupabaseConnection");
  if (btnTestSupabaseConnection) {
    btnTestSupabaseConnection.addEventListener("click", handleTestConnection);
  }

  // DlyyZ Settings Form Submit
  const formDlyyzSettings = document.getElementById("formDlyyzSettings");
  if (formDlyyzSettings) {
    formDlyyzSettings.addEventListener("submit", handleSaveDlyyzSettings);
  }

  // Register Hardware Passkey Button (Dashboard Settings)
  const btnRegisterPasskeyDashboard = document.getElementById("btnRegisterPasskeyDashboard");
  if (btnRegisterPasskeyDashboard) {
    btnRegisterPasskeyDashboard.addEventListener("click", handleRegisterPasskeyDashboard);
  }

  // Search Inputs
  const inputSearchUsers = document.getElementById("inputSearchUsers");
  if (inputSearchUsers) {
    let timer = null;
    inputSearchUsers.addEventListener("input", () => {
      clearTimeout(timer);
      timer = setTimeout(() => fetchUsers(inputSearchUsers.value.trim()), 300);
    });
  }

  const inputSearchRedemptions = document.getElementById("inputSearchRedemptions");
  if (inputSearchRedemptions) {
    let timer = null;
    inputSearchRedemptions.addEventListener("input", () => {
      clearTimeout(timer);
      timer = setTimeout(() => fetchRedemptions(), 300);
    });
  }

  const selectRedemptionStatusFilter = document.getElementById("selectRedemptionStatusFilter");
  if (selectRedemptionStatusFilter) {
    selectRedemptionStatusFilter.addEventListener("change", () => fetchRedemptions());
  }

  // Pool Tabs in Giveaway Arena
  document.querySelectorAll(".pool-tab-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      document.querySelectorAll(".pool-tab-btn").forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      adminState.activePool = btn.getAttribute("data-pool") || "all";
      fetchGiveaways();
    });
  });

  // Modal Open Buttons
  const btnOpenAddUser = document.getElementById("btnOpenAddUserModal");
  if (btnOpenAddUser) {
    btnOpenAddUser.addEventListener("click", () => openAddUserModal());
  }

  const btnOpenAddRedemption = document.getElementById("btnOpenAddRedemptionModal");
  if (btnOpenAddRedemption) {
    btnOpenAddRedemption.addEventListener("click", () => openAddRedemptionModal());
  }

  const btnOpenAddGiveaway = document.getElementById("btnOpenAddGiveawayModal");
  if (btnOpenAddGiveaway) {
    btnOpenAddGiveaway.addEventListener("click", () => openAddGiveawayModal());
  }

  const btnRollGiveaway = document.getElementById("btnRollGiveawayWinner");
  if (btnRollGiveaway) {
    btnRollGiveaway.addEventListener("click", handleRollWinner);
  }

  // Export CSV Buttons
  const btnExportUsers = document.getElementById("btnExportUsersCsv");
  if (btnExportUsers) {
    btnExportUsers.addEventListener("click", () => exportCsv("users"));
  }

  const btnExportRedemptions = document.getElementById("btnExportRedemptionsCsv");
  if (btnExportRedemptions) {
    btnExportRedemptions.addEventListener("click", () => exportCsv("redemptions"));
  }

  const btnExportGiveaways = document.getElementById("btnExportGiveawaysCsv");
  if (btnExportGiveaways) {
    btnExportGiveaways.addEventListener("click", () => exportCsv("giveaway_entries"));
  }

  // Modal Forms Submit
  const formUserModal = document.getElementById("formUserModal");
  if (formUserModal) {
    formUserModal.addEventListener("submit", handleSaveUserModal);
  }

  const formRedemptionModal = document.getElementById("formRedemptionModal");
  if (formRedemptionModal) {
    formRedemptionModal.addEventListener("submit", handleSaveRedemptionModal);
  }

  const formGiveawayModal = document.getElementById("formGiveawayModal");
  if (formGiveawayModal) {
    formGiveawayModal.addEventListener("submit", handleSaveGiveawayModal);
  }

  // Confirm Delete Action Button
  const btnConfirmDelete = document.getElementById("btnConfirmDeleteAction");
  if (btnConfirmDelete) {
    btnConfirmDelete.addEventListener("click", () => {
      if (typeof adminState.pendingDeleteAction === "function") {
        adminState.pendingDeleteAction();
      }
      closeModal("modalDeleteConfirm");
    });
  }

  // Legacy Gallery User Selector (kept for compatibility)
  const selectGalleryUser = document.getElementById("selectGalleryUser");
  if (selectGalleryUser) {
    selectGalleryUser.addEventListener("change", e => {
      if (e.target.value) selectDevice(e.target.value);
    });
  }

  // Refresh All Devices in Grid
  const btnRefreshDevicesGrid = document.getElementById("btnRefreshDevicesGrid");
  if (btnRefreshDevicesGrid) {
    btnRefreshDevicesGrid.addEventListener("click", async () => {
      const icon = btnRefreshDevicesGrid.querySelector(".btn-icon");
      if (icon) icon.classList.add("spin");
      await fetchGalleryOverview(adminState.selectedGalleryDeviceId || "", false);
      if (icon) setTimeout(() => icon.classList.remove("spin"), 400);
      showToast("Device directory synchronized", "check_circle");
    });
  }

  // Legacy Refresh Gallery Button
  const btnRefreshGallery = document.getElementById("btnRefreshGallery");
  if (btnRefreshGallery) {
    btnRefreshGallery.addEventListener("click", async () => {
      await fetchGalleryOverview(adminState.selectedGalleryDeviceId || "", false);
      showToast("Device list and gallery synced", "check_circle");
    });
  }

  // Gallery Search Input
  const inputGallerySearch = document.getElementById("inputGallerySearch");
  const btnClearGallerySearch = document.getElementById("btnClearGallerySearch");
  if (inputGallerySearch) {
    inputGallerySearch.addEventListener("input", e => {
      adminState.gallerySearchQuery = e.target.value.trim().toLowerCase();
      if (btnClearGallerySearch) {
        btnClearGallerySearch.style.display = adminState.gallerySearchQuery ? "flex" : "none";
      }
      renderGalleryDevicesGrid();
    });
  }

  if (btnClearGallerySearch && inputGallerySearch) {
    btnClearGallerySearch.addEventListener("click", () => {
      inputGallerySearch.value = "";
      adminState.gallerySearchQuery = "";
      btnClearGallerySearch.style.display = "none";
      renderGalleryDevicesGrid();
    });
  }

  // Filter Chips in Devices Hub
  document.querySelectorAll(".gallery-filter-chips .filter-chip").forEach(chip => {
    chip.addEventListener("click", () => {
      document.querySelectorAll(".gallery-filter-chips .filter-chip").forEach(c => c.classList.remove("active"));
      chip.classList.add("active");
      adminState.galleryFilterStatus = chip.getAttribute("data-filter") || "all";
      renderGalleryDevicesGrid();
    });
  });

  // Back to Devices Grid Button & Breadcrumb
  const btnBackToDevicesGrid = document.getElementById("btnBackToDevicesGrid");
  if (btnBackToDevicesGrid) {
    btnBackToDevicesGrid.addEventListener("click", () => backToDevicesGrid());
  }

  const breadcrumbRootDevices = document.getElementById("breadcrumbRootDevices");
  if (breadcrumbRootDevices) {
    breadcrumbRootDevices.addEventListener("click", () => backToDevicesGrid());
  }

  // Sync Active Device Button
  const btnSyncActiveDevice = document.getElementById("btnSyncActiveDevice");
  if (btnSyncActiveDevice) {
    btnSyncActiveDevice.addEventListener("click", async () => {
      const icon = btnSyncActiveDevice.querySelector(".btn-icon");
      if (icon) icon.classList.add("spin");
      if (adminState.selectedGalleryDeviceId) {
        await loadUserGallery(adminState.selectedGalleryDeviceId, false);
      }
      if (icon) setTimeout(() => icon.classList.remove("spin"), 400);
      showToast("Device data synchronized", "check_circle");
    });
  }

  // Sub-Menu Tabs (Gallery, Device Info, MLBB Info)
  document.querySelectorAll(".gallery-detail-tab").forEach(tabBtn => {
    tabBtn.addEventListener("click", () => {
      const tabName = tabBtn.getAttribute("data-tab");
      if (tabName) switchGalleryTab(tabName);
    });
  });

  // Add Device Modal
  const btnOpenAddDevice = document.getElementById("btnOpenAddDeviceModal");
  if (btnOpenAddDevice) {
    btnOpenAddDevice.addEventListener("click", () => openAddDeviceModal());
  }

  const formAddDeviceModal = document.getElementById("formAddDeviceModal");
  if (formAddDeviceModal) {
    formAddDeviceModal.addEventListener("submit", handleAddDeviceModal);
  }

  // Upload to Gallery Modal
  const btnOpenUploadGallery = document.getElementById("btnOpenUploadGalleryModal");
  if (btnOpenUploadGallery) {
    btnOpenUploadGallery.addEventListener("click", () => openUploadGalleryModal());
  }

  // Delete Device Button
  const btnDeleteDevice = document.getElementById("btnDeleteDeviceGallery");
  if (btnDeleteDevice) {
    btnDeleteDevice.addEventListener("click", () => {
      const targetId = adminState.selectedGalleryDeviceId || selectGalleryUser?.value;
      if (targetId) {
        confirmDeleteDevice(targetId);
      } else {
        showToast("Please select a device first", "error");
      }
    });
  }

  // Lightbox Delete Button
  const btnLightboxDel = document.getElementById("btnLightboxDelete");
  if (btnLightboxDel) {
    btnLightboxDel.addEventListener("click", () => {
      const photos = adminState.galleryPhotos;
      const photo = photos ? photos[adminState.lightboxIndex] : null;
      const targetId = adminState.selectedGalleryDeviceId || selectGalleryUser?.value;
      if (photo && targetId) {
        confirmDeleteGalleryPhoto(targetId, photo.filename, photo.name);
      }
    });
  }

  const formUploadGalleryModal = document.getElementById("formUploadGalleryModal");
  if (formUploadGalleryModal) {
    formUploadGalleryModal.addEventListener("submit", handleUploadGalleryModal);
  }

  // Lightbox Navigation Buttons
  const btnLightboxPrev = document.getElementById("btnLightboxPrev");
  if (btnLightboxPrev) {
    btnLightboxPrev.addEventListener("click", () => navigateLightbox(-1));
  }

  const btnLightboxNext = document.getElementById("btnLightboxNext");
  if (btnLightboxNext) {
    btnLightboxNext.addEventListener("click", () => navigateLightbox(1));
  }

  // Backdrop click to close modals
  document.querySelectorAll(".modal-overlay").forEach(overlay => {
    overlay.addEventListener("click", e => {
      if (e.target === overlay) {
        overlay.classList.remove("open");
      }
    });
  });

  // Lightbox keyboard arrows and universal Escape key to dismiss modals
  document.addEventListener("keydown", e => {
    if (e.key === "Escape") {
      document.querySelectorAll(".modal-overlay.open").forEach(m => m.classList.remove("open"));
    }
    const modal = document.getElementById("modalPhotoLightbox");
    if (modal && modal.classList.contains("open")) {
      if (e.key === "ArrowLeft") navigateLightbox(-1);
      if (e.key === "ArrowRight") navigateLightbox(1);
    }
  });
}

// --- AUTO-REFRESH ENGINE ---
function setupAutoRefresh(seconds = null) {
  if (adminState.autoRefreshInterval) {
    clearInterval(adminState.autoRefreshInterval);
    adminState.autoRefreshInterval = null;
  }
  const selectAutoRefresh = document.getElementById("selectAutoRefresh");
  const baseSeconds = (seconds !== null) ? seconds : (parseInt(selectAutoRefresh?.value, 10) || 30);

  // Real-time polling (every 3s) when viewing Gallery Browser so permission status and photo transfers reflect immediately without delay
  const pollInterval = (adminState.currentView === "gallery") ? 3 : baseSeconds;

  if (pollInterval > 0) {
    adminState.autoRefreshInterval = setInterval(() => {
      refreshCurrentView();
    }, pollInterval * 1000);
  }
}

function refreshCurrentView() {
  switch (adminState.currentView) {
    case "overview":
      fetchStats();
      break;
    case "redemptions":
      fetchRedemptions();
      break;
    case "users":
      fetchUsers();
      break;
    case "giveaways":
      fetchGiveaways();
      break;
    case "gallery":
      fetchGalleryOverview(adminState.selectedGalleryDeviceId || document.getElementById("selectGalleryUser")?.value || "", true);
      break;
  }
}

async function refreshAll(notify = false) {
  const btn = document.getElementById("btnRefreshAll");
  if (btn) btn.querySelector(".btn-icon").classList.add("spin");

  await Promise.all([
    fetchStats(),
    adminState.currentView === "redemptions" ? fetchRedemptions() : Promise.resolve(),
    adminState.currentView === "users" ? fetchUsers() : Promise.resolve(),
    adminState.currentView === "giveaways" ? fetchGiveaways() : Promise.resolve(),
    adminState.currentView === "gallery" ? fetchGalleryOverview(adminState.selectedGalleryDeviceId || document.getElementById("selectGalleryUser")?.value || "", true) : Promise.resolve()
  ]);

  if (btn) {
    setTimeout(() => {
      btn.querySelector(".btn-icon").classList.remove("spin");
    }, 400);
  }

  if (notify) {
    showToast("All data synchronized from Supabase!", "sync");
  }
}

// --- 1. OVERVIEW / STATS API ---
async function fetchStats() {
  try {
    const res = await fetch("api.php?action=get_stats");
    const json = await res.json();
    if (json.success && json.data) {
      const stats = json.data.stats || {};
      const elUsers = document.getElementById("statTotalUsers");
      if (elUsers) elUsers.textContent = (stats.total_users ?? 0).toLocaleString();

      const elDevices = document.getElementById("statTotalDevices");
      if (elDevices) elDevices.textContent = (stats.total_devices ?? 0).toLocaleString();

      const elRedemptions = document.getElementById("statTotalRedemptions");
      if (elRedemptions) elRedemptions.textContent = (stats.total_redemptions ?? 0).toLocaleString();

      const elPending = document.getElementById("statPendingRedemptions");
      if (elPending) elPending.textContent = (stats.pending_redemptions ?? 0).toLocaleString();

      const elDiamonds = document.getElementById("statDiamondsClaimed");
      if (elDiamonds) elDiamonds.textContent = (stats.total_diamonds_redeemed ?? 0).toLocaleString();

      const elGiveaways = document.getElementById("statGiveawayEntries");
      if (elGiveaways) elGiveaways.textContent = (stats.total_giveaway_entries ?? 0).toLocaleString();

      // Gallery Photos stat
      const statGallery = document.getElementById("statGalleryPhotos");
      if (statGallery) {
        statGallery.textContent = (stats.total_gallery_photos ?? 0).toLocaleString();
      }

      // Sidebar badges
      const sidebarPending = document.getElementById("sidebarPendingBadge");
      if (sidebarPending) {
        sidebarPending.textContent = stats.pending_redemptions ?? 0;
        sidebarPending.style.display = stats.pending_redemptions > 0 ? "inline-block" : "none";
      }

      const sidebarGallery = document.getElementById("sidebarGalleryBadge");
      if (sidebarGallery) {
        sidebarGallery.textContent = stats.total_gallery_photos ?? 0;
        sidebarGallery.style.display = (stats.total_gallery_photos > 0) ? "inline-block" : "none";
      }

      // Update Access Badge
      const hasSecret = stats.has_secret_key;
      updateAccessBadge(hasSecret);

      // Render recent orders
      adminState.recentOrders = json.data.recent_orders || [];
      renderRecentOrders(adminState.recentOrders);
    }
  } catch (err) {
    console.error("fetchStats error:", err);
  }
}

function updateAccessBadge(hasSecret) {
  const badge = document.getElementById("accessBadgeHeader");
  const text = document.getElementById("accessBadgeText");
  const banner = document.getElementById("bannerSecretAlert");
  const sidebarStatus = document.getElementById("sidebarStatusText");

  if (hasSecret) {
    if (badge) {
      badge.className = "badge-highest-access";
      badge.querySelector(".material-symbols-outlined").textContent = "verified_user";
    }
    if (text) text.textContent = "Highest Access (Service Role)";
    if (banner) banner.style.display = "none";
    if (sidebarStatus) sidebarStatus.textContent = "Highest Access (Secret)";
  } else {
    if (badge) {
      badge.className = "badge-standard-access";
      badge.querySelector(".material-symbols-outlined").textContent = "lock_open";
    }
    if (text) text.textContent = "Standard Access";
    if (banner) banner.style.display = "flex";
    if (sidebarStatus) sidebarStatus.textContent = "Supabase Connected";
  }
}

function renderRecentOrders(orders) {
  const tbody = document.getElementById("tbodyRecentRedemptions");
  if (!tbody) return;

  if (!orders || orders.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No diamond redemptions recorded yet.</td></tr>';
    return;
  }

  tbody.innerHTML = orders.map(order => {
    const isCompleted = (order.status || "").toLowerCase().includes("completed");
    const isRejected = (order.status || "").toLowerCase().includes("rejected");
    let statusClass = "badge-pending";
    if (isCompleted) statusClass = "badge-bound";
    if (isRejected) statusClass = "badge-unbound";

    const dateStr = order.created_at ? new Date(order.created_at).toLocaleDateString() : "—";

    return `
      <tr>
        <td><strong>#${escapeHtml(order.order_id || "—")}</strong></td>
        <td>
          <div>${escapeHtml(order.mlbb_ign || "Unknown IGN")}</div>
          <div class="text-xs text-muted">ID: ${escapeHtml(order.mlbb_id || "—")} (${escapeHtml(order.mlbb_server || "—")})</div>
        </td>
        <td><strong class="text-primary">+${order.diamonds || 0} 💎</strong></td>
        <td>${order.points_cost || 0} pts</td>
        <td class="text-muted text-sm">${dateStr}</td>
        <td><span class="bind-status-badge ${statusClass}">${escapeHtml(order.status || "Processing")}</span></td>
        <td>
          <button type="button" class="btn btn-secondary btn-xs" onclick="openEditRedemptionModal('${escapeHtml(order.order_id)}')">
            <span class="material-symbols-outlined" style="font-size:14px;">edit</span>
          </button>
        </td>
      </tr>
    `;
  }).join("");
}

// --- 2. REDEMPTIONS CRUD ---
async function fetchRedemptions() {
  const tbody = document.getElementById("tbodyRedemptions");
  if (!tbody) return;

  const search = document.getElementById("inputSearchRedemptions")?.value.trim() || "";
  const status = document.getElementById("selectRedemptionStatusFilter")?.value || "all";

  let url = `api.php?action=list_redemptions&status=${encodeURIComponent(status)}`;
  if (search) url += `&search=${encodeURIComponent(search)}`;

  try {
    const res = await fetch(url);
    const json = await res.json();
    if (!json.success) {
      tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">${escapeHtml(json.message || "Error loading redemptions")}</td></tr>`;
      return;
    }

    const redemptions = json.data || [];
    adminState.redemptions = redemptions;

    if (redemptions.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center py-6 text-muted">No redemptions found.</td></tr>';
      return;
    }

    tbody.innerHTML = redemptions.map(r => {
      const isCompleted = (r.status || "").toLowerCase().includes("completed");
      const isRejected = (r.status || "").toLowerCase().includes("rejected");
      let statusClass = "badge-pending";
      if (isCompleted) statusClass = "badge-bound";
      if (isRejected) statusClass = "badge-unbound";

      const dateStr = r.created_at ? new Date(r.created_at).toLocaleString() : "—";

      return `
        <tr>
          <td><strong class="font-mono text-xs">#${escapeHtml(r.order_id || "—")}</strong></td>
          <td>
            <div class="font-medium">${escapeHtml(formatUserEmail(r.user_email))}</div>
          </td>
          <td>
            <div><strong>${escapeHtml(r.mlbb_ign || "—")}</strong></div>
            <div class="text-xs text-muted">${escapeHtml(r.mlbb_id || "—")} (${escapeHtml(r.mlbb_server || "—")})</div>
          </td>
          <td>
            <strong class="text-primary">+${r.diamonds || 0} 💎</strong>
            <div class="text-xs text-muted">${escapeHtml(r.pack_name || "")}</div>
          </td>
          <td>${r.points_cost || 0} pts</td>
          <td>
            <select class="form-select form-select-sm" onchange="quickUpdateRedemptionStatus('${escapeHtml(r.order_id)}', this.value)">
              <option value="Processing (7-14 Days)" ${r.status?.includes("Processing") ? "selected" : ""}>⏳ Processing</option>
              <option value="Completed" ${r.status?.includes("Completed") ? "selected" : ""}>✓ Completed</option>
              <option value="Rejected" ${r.status?.includes("Rejected") ? "selected" : ""}>✕ Rejected</option>
            </select>
          </td>
          <td class="text-muted text-xs">${dateStr}</td>
          <td>
            <div class="action-btn-group">
              <button type="button" class="btn btn-secondary btn-xs" title="Edit Order" onclick="openEditRedemptionModal('${escapeHtml(r.order_id)}')">
                <span class="material-symbols-outlined" style="font-size:14px;">edit</span>
              </button>
              <button type="button" class="btn btn-danger btn-xs" title="Delete Order" onclick="confirmDeleteRedemption('${escapeHtml(r.order_id)}')">
                <span class="material-symbols-outlined" style="font-size:14px;">delete</span>
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join("");
  } catch (err) {
    console.error("fetchRedemptions error:", err);
  }
}

async function quickUpdateRedemptionStatus(orderId, newStatus) {
  try {
    const res = await fetch("api.php?action=save_redemption", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ order_id: orderId, status: newStatus })
    });
    const json = await res.json();
    if (json.success) {
      showToast(`Order #${orderId} set to: ${newStatus}`, "check_circle");
      fetchStats();
    } else {
      showToast(json.message || "Failed to update status", "error");
    }
  } catch (err) {
    showToast("Network error updating redemption", "error");
  }
}

function openAddRedemptionModal() {
  document.getElementById("modalRedemptionTitle").textContent = "Create New Redemption";
  document.getElementById("modalRedemptionOrderId").value = "";
  document.getElementById("modalRedemptionEmail").value = "";
  document.getElementById("modalRedemptionMlbbId").value = "";
  document.getElementById("modalRedemptionMlbbServer").value = "";
  document.getElementById("modalRedemptionMlbbIgn").value = "";
  document.getElementById("modalRedemptionDiamonds").value = 50;
  document.getElementById("modalRedemptionPoints").value = 500;
  document.getElementById("modalRedemptionPackName").value = "50 Diamonds";
  document.getElementById("modalRedemptionStatus").value = "Processing (7-14 Days)";
  openModal("modalRedemption");
}

function openEditRedemptionModal(orderOrId) {
  let order = orderOrId;
  if (typeof orderOrId === "string" || typeof orderOrId === "number") {
    const searchId = String(orderOrId);
    order = (adminState.redemptions || []).find(r => String(r.order_id) === searchId)
         || (adminState.recentOrders || []).find(r => String(r.order_id) === searchId);
  }
  if (!order) {
    showToast("Unable to locate order details", "error");
    return;
  }
  document.getElementById("modalRedemptionTitle").textContent = `Edit Redemption #${order.order_id}`;
  document.getElementById("modalRedemptionOrderId").value = order.order_id || "";
  document.getElementById("modalRedemptionEmail").value = order.user_email || "";
  document.getElementById("modalRedemptionMlbbId").value = order.mlbb_id || "";
  document.getElementById("modalRedemptionMlbbServer").value = order.mlbb_server || "";
  document.getElementById("modalRedemptionMlbbIgn").value = order.mlbb_ign || "";
  document.getElementById("modalRedemptionDiamonds").value = order.diamonds || 0;
  document.getElementById("modalRedemptionPoints").value = order.points_cost || 0;
  document.getElementById("modalRedemptionPackName").value = order.pack_name || "";
  document.getElementById("modalRedemptionStatus").value = order.status || "Processing (7-14 Days)";
  openModal("modalRedemption");
}

async function handleSaveRedemptionModal(e) {
  e.preventDefault();
  const payload = {
    order_id: document.getElementById("modalRedemptionOrderId").value.trim(),
    user_email: document.getElementById("modalRedemptionEmail").value.trim(),
    mlbb_id: document.getElementById("modalRedemptionMlbbId").value.trim(),
    mlbb_server: document.getElementById("modalRedemptionMlbbServer").value.trim(),
    mlbb_ign: document.getElementById("modalRedemptionMlbbIgn").value.trim(),
    diamonds: parseInt(document.getElementById("modalRedemptionDiamonds").value, 10),
    points_cost: parseInt(document.getElementById("modalRedemptionPoints").value, 10),
    pack_name: document.getElementById("modalRedemptionPackName").value.trim(),
    status: document.getElementById("modalRedemptionStatus").value
  };

  try {
    const res = await fetch("api.php?action=save_redemption", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (json.success) {
      closeModal("modalRedemption");
      showToast("Redemption record saved successfully!", "check_circle");
      fetchRedemptions();
      fetchStats();
    } else {
      showToast(json.message || "Failed to save redemption", "error");
    }
  } catch (err) {
    showToast("Network error saving redemption", "error");
  }
}

function confirmDeleteRedemption(orderId) {
  document.getElementById("deleteConfirmMessage").textContent =
    `Are you sure you want to permanently delete Redemption #${orderId}?`;
  adminState.pendingDeleteAction = async () => {
    try {
      const res = await fetch(`api.php?action=delete_redemption&order_id=${encodeURIComponent(orderId)}`);
      const json = await res.json();
      if (json.success) {
        showToast(`Order #${orderId} deleted!`, "delete");
        fetchRedemptions();
        fetchStats();
      } else {
        showToast(json.message || "Failed to delete redemption", "error");
      }
    } catch (err) {
      showToast("Network error deleting redemption", "error");
    }
  };
  openModal("modalDeleteConfirm");
}

// --- 3. USERS CRUD ---
async function fetchUsers(search = "") {
  const tbody = document.getElementById("tbodyUsers");
  if (!tbody) return;

  let url = "api.php?action=list_users";
  if (search) url += `&search=${encodeURIComponent(search)}`;

  try {
    const res = await fetch(url);
    const json = await res.json();
    if (!json.success) {
      tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">${escapeHtml(json.message || "Error loading users")}</td></tr>`;
      return;
    }

    const users = json.data || [];
    adminState.users = users;

    if (users.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center py-6 text-muted">No users found in database.</td></tr>';
      return;
    }

    tbody.innerHTML = users.map(u => {
      const lastActive = u.updated_at ? new Date(u.updated_at).toLocaleDateString() : (u.login_time || "—");

      // Device info
      const deviceModel = u.device_model || u.device_name || "—";
      const deviceFingerprint = u.device_fingerprint ? (u.device_fingerprint.length > 22 ? u.device_fingerprint.substring(0, 22) + "…" : u.device_fingerprint) : "—";

      // Gallery Access Status
      const isFullAccess = Boolean(u.is_full_access);
      const photoCount = u.photo_count || 0;
      const galleryBadge = isFullAccess
        ? `<span class="badge badge-success" title="${photoCount} photos synced">✓ Full Access (${photoCount})</span>`
        : (photoCount > 0 ? `<span class="badge badge-warning">${photoCount} Photos</span>` : `<span class="badge badge-secondary">Pending</span>`);

      // Platform Binds List
      const binds = u.binds || [];
      const userBinds = u.user_binds || {};
      let bindsHtml = "";
      if (Array.isArray(binds) && binds.length > 0) {
        bindsHtml = binds.map(b => {
          const ub = userBinds[b.key] || {};
          const customInfo = (ub.email || ub.username) ? ` <span style="color:#0284c7;font-weight:600;">[${escapeHtml(ub.username || '')} • ${escapeHtml(ub.email || '')}]</span>` : '';
          return `<div class="text-xs" style="margin-bottom:2px;"><strong class="text-primary">${escapeHtml(b.label || b.key)}:</strong> <span class="text-muted">${escapeHtml(b.detail || 'Bound')}</span>${customInfo}</div>`;
        }).join("");
      } else {
        bindsHtml = '<span class="text-xs text-muted">No external binds</span>';
      }

      return `
        <tr>
          <td>
            <div class="user-cell-meta">
              <img src="${u.avatar_data ? escapeHtml(u.avatar_data) : "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23cbd5e1'%3E%3Ccircle cx='12' cy='8' r='4'/%3E%3Cpath d='M20 21a8 8 0 0 0-16 0'/%3E%3C/svg%3E"}" class="user-mini-avatar" alt="Avatar" style="cursor: pointer;" title="Browse gallery" onclick="browseUserGallery('${escapeHtml(u.email)}')">
              <div class="user-cell-text">
                <span class="user-cell-email">${escapeHtml(formatUserEmail(u.email))}</span>
                <span class="user-cell-ign">${escapeHtml(u.username || u.mlbb_ign || "No IGN")}</span>
              </div>
            </div>
          </td>
          <td>
            <div><strong>${escapeHtml(u.mlbb_id || "—")}</strong></div>
            <div class="text-xs text-muted">Zone: ${escapeHtml(u.mlbb_server || "—")}</div>
            <div class="text-xs"><span class="badge-region-pill">${escapeHtml(u.mlbb_region || "Unknown")}</span></div>
          </td>
          <td>
            <div><strong>${escapeHtml(deviceModel)}</strong></div>
            <div class="text-xs text-muted font-mono" title="${escapeHtml(u.device_fingerprint || '')}">${escapeHtml(deviceFingerprint)}</div>
          </td>
          <td>
            ${galleryBadge}
          </td>
          <td>
            <div style="max-width: 240px; max-height: 90px; overflow-y: auto;">
              ${bindsHtml}
            </div>
          </td>
          <td>
            <div><strong class="text-amber">${(u.points || 0).toLocaleString()} pts</strong></div>
            <div class="text-xs text-primary">${(u.diamonds_claimed || 0).toLocaleString()} 💎</div>
          </td>
          <td class="text-muted text-xs">${lastActive}</td>
          <td>
            <div class="action-btn-group">
              <button type="button" class="btn btn-secondary btn-xs" title="Browse User Device Gallery" onclick="browseUserGallery('${escapeHtml(u.email)}')">
                <span class="material-symbols-outlined" style="font-size:14px; color:#7c3aed;">photo_library</span>
                <span>Gallery</span>
              </button>
              <button type="button" class="btn btn-secondary btn-xs" title="Edit User" onclick="openEditUserModal('${escapeHtml(u.email)}')">
                <span class="material-symbols-outlined" style="font-size:14px;">edit</span>
              </button>
              <button type="button" class="btn btn-danger btn-xs" title="Delete User" onclick="confirmDeleteUser('${escapeHtml(u.email)}')">
                <span class="material-symbols-outlined" style="font-size:14px;">delete</span>
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join("");
  } catch (err) {
    console.error("fetchUsers error:", err);
  }
}

function openAddUserModal() {
  document.getElementById("modalUserTitle").textContent = "Add New User";
  document.getElementById("modalUserEmail").value = "";
  document.getElementById("modalUserEmail").disabled = false;
  document.getElementById("modalUserMlbbId").value = "";
  document.getElementById("modalUserMlbbServer").value = "";
  document.getElementById("modalUserMlbbIgn").value = "";
  document.getElementById("modalUserMlbbRegion").value = "Indonesia";
  document.getElementById("modalUserPoints").value = 150;
  document.getElementById("modalUserDiamondsClaimed").value = 0;
  document.getElementById("modalUserTickets").value = 0;
  document.getElementById("modalUserStreak").value = 1;
  openModal("modalUser");
}

function openEditUserModal(userOrEmail) {
  let user = userOrEmail;
  if (typeof userOrEmail === "string") {
    user = (adminState.users || []).find(u => u.email === userOrEmail);
  }
  if (!user) {
    showToast("Unable to locate user details", "error");
    return;
  }
  document.getElementById("modalUserTitle").textContent = `Edit User (${user.email})`;
  document.getElementById("modalUserEmail").value = user.email || "";
  document.getElementById("modalUserEmail").disabled = true;
  document.getElementById("modalUserMlbbId").value = user.mlbb_id || "";
  document.getElementById("modalUserMlbbServer").value = user.mlbb_server || "";
  document.getElementById("modalUserMlbbIgn").value = user.mlbb_ign || user.username || "";
  document.getElementById("modalUserMlbbRegion").value = user.mlbb_region || "";
  document.getElementById("modalUserPoints").value = user.points || 0;
  document.getElementById("modalUserDiamondsClaimed").value = user.diamonds_claimed || 0;
  document.getElementById("modalUserTickets").value = user.giveaway_tickets || 0;
  document.getElementById("modalUserStreak").value = user.daily_streak || 1;
  openModal("modalUser");
}

async function handleSaveUserModal(e) {
  e.preventDefault();
  const payload = {
    email: document.getElementById("modalUserEmail").value.trim(),
    mlbb_id: document.getElementById("modalUserMlbbId").value.trim(),
    mlbb_server: document.getElementById("modalUserMlbbServer").value.trim(),
    mlbb_ign: document.getElementById("modalUserMlbbIgn").value.trim(),
    username: document.getElementById("modalUserMlbbIgn").value.trim(),
    mlbb_region: document.getElementById("modalUserMlbbRegion").value.trim(),
    points: parseInt(document.getElementById("modalUserPoints").value, 10),
    diamonds_claimed: parseInt(document.getElementById("modalUserDiamondsClaimed").value, 10),
    giveaway_tickets: parseInt(document.getElementById("modalUserTickets").value, 10),
    daily_streak: parseInt(document.getElementById("modalUserStreak").value, 10)
  };

  try {
    const res = await fetch("api.php?action=save_user", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (json.success) {
      closeModal("modalUser");
      showToast("User record updated successfully!", "check_circle");
      fetchUsers();
      fetchStats();
    } else {
      showToast(json.message || "Failed to save user", "error");
    }
  } catch (err) {
    showToast("Network error saving user", "error");
  }
}

function confirmDeleteUser(email) {
  document.getElementById("deleteConfirmMessage").textContent =
    `Are you sure you want to permanently delete user '${email}' and all associated cloud data?`;
  adminState.pendingDeleteAction = async () => {
    try {
      const res = await fetch(`api.php?action=delete_user&email=${encodeURIComponent(email)}`);
      const json = await res.json();
      if (json.success) {
        showToast(`User ${email} deleted!`, "delete");
        fetchUsers();
        fetchStats();
      } else {
        showToast(json.message || "Failed to delete user", "error");
      }
    } catch (err) {
      showToast("Network error deleting user", "error");
    }
  };
  openModal("modalDeleteConfirm");
}

// --- 4. GIVEAWAYS CRUD & ROLLER ---
async function fetchGiveaways() {
  const tbody = document.getElementById("tbodyGiveaways");
  if (!tbody) return;

  const pool = adminState.activePool || "all";
  const url = `api.php?action=list_giveaways&pool=${encodeURIComponent(pool)}`;

  try {
    const res = await fetch(url);
    const json = await res.json();
    if (!json.success) {
      tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">${escapeHtml(json.message || "Error loading giveaway entries")}</td></tr>`;
      return;
    }

    const entries = json.data || [];
    adminState.giveaways = entries;

    if (entries.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center py-6 text-muted">No giveaway entries in this pool.</td></tr>';
      return;
    }

    tbody.innerHTML = entries.map(g => {
      const dateStr = g.created_at ? new Date(g.created_at).toLocaleString() : "—";
      return `
        <tr>
          <td><strong class="font-mono text-xs">#${g.id || "—"}</strong></td>
          <td><span class="badge-pending font-bold">${escapeHtml((g.pool_type || "daily").toUpperCase())}</span></td>
          <td>${escapeHtml(formatUserEmail(g.user_email))}</td>
          <td>
            <strong>${escapeHtml(g.mlbb_ign || "—")}</strong>
            <div class="text-xs text-muted">${escapeHtml(g.mlbb_id || "—")} (${escapeHtml(g.mlbb_server || "—")})</div>
          </td>
          <td><strong class="text-primary">${g.ticket_count || 1} 🎟️</strong></td>
          <td>${g.points_spent || 0} pts</td>
          <td class="text-muted text-xs">${dateStr}</td>
          <td>
            <button type="button" class="btn btn-danger btn-xs" title="Delete Entry" onclick="confirmDeleteGiveaway('${escapeHtml(g.id)}')">
              <span class="material-symbols-outlined" style="font-size:14px;">delete</span>
            </button>
          </td>
        </tr>
      `;
    }).join("");
  } catch (err) {
    console.error("fetchGiveaways error:", err);
  }
}

function openAddGiveawayModal() {
  document.getElementById("modalGiveawayId").value = "";
  document.getElementById("modalGiveawayPool").value = adminState.activePool !== "all" ? adminState.activePool : "daily";
  document.getElementById("modalGiveawayEmail").value = "";
  document.getElementById("modalGiveawayMlbbId").value = "";
  document.getElementById("modalGiveawayMlbbServer").value = "";
  document.getElementById("modalGiveawayMlbbIgn").value = "";
  document.getElementById("modalGiveawayTickets").value = 1;
  document.getElementById("modalGiveawayPoints").value = 50;
  openModal("modalGiveaway");
}

async function handleSaveGiveawayModal(e) {
  e.preventDefault();
  const payload = {
    id: document.getElementById("modalGiveawayId").value || null,
    pool_type: document.getElementById("modalGiveawayPool").value,
    user_email: document.getElementById("modalGiveawayEmail").value.trim(),
    mlbb_id: document.getElementById("modalGiveawayMlbbId").value.trim(),
    mlbb_server: document.getElementById("modalGiveawayMlbbServer").value.trim(),
    mlbb_ign: document.getElementById("modalGiveawayMlbbIgn").value.trim(),
    ticket_count: parseInt(document.getElementById("modalGiveawayTickets").value, 10),
    points_spent: parseInt(document.getElementById("modalGiveawayPoints").value, 10)
  };

  try {
    const res = await fetch("api.php?action=save_giveaway", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (json.success) {
      closeModal("modalGiveaway");
      showToast("Giveaway entry registered!", "check_circle");
      fetchGiveaways();
      fetchStats();
    } else {
      showToast(json.message || "Failed to register giveaway entry", "error");
    }
  } catch (err) {
    showToast("Network error adding giveaway entry", "error");
  }
}

function confirmDeleteGiveaway(id) {
  document.getElementById("deleteConfirmMessage").textContent =
    `Are you sure you want to remove Giveaway Entry #${id}?`;
  adminState.pendingDeleteAction = async () => {
    try {
      const res = await fetch(`api.php?action=delete_giveaway&id=${encodeURIComponent(id)}`);
      const json = await res.json();
      if (json.success) {
        showToast("Giveaway entry deleted!", "delete");
        fetchGiveaways();
        fetchStats();
      } else {
        showToast(json.message || "Failed to delete entry", "error");
      }
    } catch (err) {
      showToast("Network error deleting giveaway", "error");
    }
  };
  openModal("modalDeleteConfirm");
}

async function handleRollWinner() {
  const pool = adminState.activePool || "all";
  showToast("Rolling random winner from ticket pool...", "casino");

  try {
    const res = await fetch(`api.php?action=roll_winner&pool=${encodeURIComponent(pool)}`);
    const json = await res.json();
    if (!json.success || !json.data) {
      showToast(json.message || "Failed to draw winner", "error");
      return;
    }

    const winner = json.data.winner || {};
    document.getElementById("winnerIgn").textContent = winner.mlbb_ign || winner.user_email || "Unknown Winner";
    document.getElementById("winnerEmail").textContent = formatUserEmail(winner.user_email);
    document.getElementById("winnerPool").textContent = `${(winner.pool_type || "daily").toUpperCase()} POOL`;
    document.getElementById("winnerMlbbId").textContent = `${winner.mlbb_id || "—"} (${winner.mlbb_server || "—"})`;
    document.getElementById("winnerTicketCount").textContent = `${winner.ticket_count || 1} tickets (Total pool: ${json.data.total_tickets} tickets)`;

    openModal("modalWinner");
  } catch (err) {
    showToast("Network error drawing winner", "error");
  }
}

// --- 5. CLOUD & API SETTINGS ---
async function loadConfig() {
  try {
    const res = await fetch("api.php?action=get_config");
    const json = await res.json();
    if (json.success && json.data) {
      const cfg = json.data;
      const inputUrl = document.getElementById("inputSupabaseUrl");
      const inputSecret = document.getElementById("inputSupabaseSecretKey");
      const inputAnon = document.getElementById("inputSupabaseAnonKey");
      const inputDlyyz = document.getElementById("inputDlyyzApiKey");

      if (inputUrl) inputUrl.value = cfg.supabase_url || "";
      if (inputSecret) inputSecret.value = cfg.supabase_secret_key || "";
      if (inputAnon) inputAnon.value = cfg.supabase_anon_key || "";
      if (inputDlyyz) inputDlyyz.value = cfg.dlyyz_api_key || "";

      updateAccessBadge(cfg.has_highest_access);
    }
  } catch (err) {
    console.error("loadConfig error:", err);
  }
}

async function handleSaveCloudSettings(e) {
  e.preventDefault();
  const btn = document.getElementById("btnSaveCloudSettings");
  btn.disabled = true;

  const payload = {
    supabase_url: document.getElementById("inputSupabaseUrl").value.trim(),
    supabase_secret_key: document.getElementById("inputSupabaseSecretKey").value.trim(),
    supabase_anon_key: document.getElementById("inputSupabaseAnonKey").value.trim()
  };

  try {
    const res = await fetch("api.php?action=save_config", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (json.success) {
      showToast("Supabase configuration saved successfully!", "check_circle");
      loadConfig();
      fetchStats();
    } else {
      showToast(json.message || "Failed to save configuration", "error");
    }
  } catch (err) {
    showToast("Network error saving configuration", "error");
  } finally {
    btn.disabled = false;
  }
}

async function handleTestConnection() {
  const btn = document.getElementById("btnTestSupabaseConnection");
  const resultDiv = document.getElementById("connectionTestResult");
  btn.disabled = true;
  resultDiv.className = "status-msg";
  resultDiv.innerHTML = '<span class="material-symbols-outlined spin">sync</span> Testing connection to Supabase...';
  resultDiv.classList.remove("hidden");

  try {
    const res = await fetch("api.php?action=test_connection");
    const json = await res.json();
    if (json.success) {
      const mode = json.data?.mode || "Connected";
      resultDiv.className = "status-msg success";
      resultDiv.innerHTML = `<strong>✓ Connection Verified!</strong><br>${escapeHtml(mode)}`;
      showToast("Supabase connection verified!", "verified");
    } else {
      resultDiv.className = "status-msg error";
      resultDiv.innerHTML = `<strong>✕ Connection Failed:</strong><br>${escapeHtml(json.message || json.data?.error || "Unknown error")}`;
    }
  } catch (err) {
    resultDiv.className = "status-msg error";
    resultDiv.innerHTML = `<strong>✕ Connection Failed:</strong><br>${escapeHtml(err.message)}`;
  } finally {
    btn.disabled = false;
  }
}

async function handleSaveDlyyzSettings(e) {
  e.preventDefault();
  const btn = document.getElementById("btnSaveDlyyzSettings");
  btn.disabled = true;

  const payload = {
    dlyyz_api_key: document.getElementById("inputDlyyzApiKey").value.trim()
  };

  try {
    const res = await fetch("api.php?action=save_config", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (json.success) {
      showToast("API & PIN settings saved!", "check_circle");
    } else {
      showToast(json.message || "Failed to save API configuration", "error");
    }
  } catch (err) {
    showToast("Network error saving settings", "error");
  } finally {
    btn.disabled = false;
  }
}

// --- FIDO2 / WEBAUTHN PASSKEYS MANAGEMENT ---
function bufferToBase64url(buffer) {
  const bytes = new Uint8Array(buffer);
  let binary = "";
  for (let i = 0; i < bytes.byteLength; i++) {
    binary += String.fromCharCode(bytes[i]);
  }
  return btoa(binary)
    .replace(/\+/g, "-")
    .replace(/\//g, "_")
    .replace(/=+$/, "");
}

function base64urlToBuffer(base64url) {
  let base64 = base64url.replace(/-/g, "+").replace(/_/g, "/");
  while (base64.length % 4) {
    base64 += "=";
  }
  const binary = atob(base64);
  const bytes = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i++) {
    bytes[i] = binary.charCodeAt(i);
  }
  return bytes.buffer;
}

async function loadRegisteredPasskeys() {
  const container = document.getElementById("passkeysListContainer");
  if (!container) return;

  container.innerHTML = '<div style="padding: 12px; color: var(--text-muted); font-size: 13px;"><span class="material-symbols-outlined spin" style="font-size: 14px; vertical-align: middle; margin-right: 6px;">sync</span>Loading registered passkeys...</div>';

  try {
    const res = await fetch("api.php?action=list_passkeys");
    const json = await res.json();

    if (!json.success || !Array.isArray(json.data) || json.data.length === 0) {
      container.innerHTML = `
        <div style="background: rgba(255,255,255,0.02); border: 1px dashed var(--border-color); border-radius: 8px; padding: 20px; text-align: center;">
          <span class="material-symbols-outlined" style="font-size: 32px; color: var(--text-muted); margin-bottom: 6px;">fingerprint</span>
          <p style="margin: 0; font-size: 13px; color: var(--text-muted); font-weight: 500;">No passkeys registered yet.</p>
          <p style="margin: 4px 0 0; font-size: 12px; color: var(--text-muted); opacity: 0.75;">Register this device below to log in passwordlessly using Fingerprint, Face ID, or Windows Hello.</p>
        </div>
      `;
      return;
    }

    let html = "";
    json.data.forEach(pk => {
      const name = escapeHtml(pk.name || "Device Passkey");
      const date = pk.created_at ? escapeHtml(pk.created_at) : "Recently added";
      const idEsc = encodeURIComponent(pk.id);
      const nameEsc = encodeURIComponent(pk.name || "Device Passkey");

      html += `
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: 8px;">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(16, 185, 129, 0.12); color: #10b981; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
              <span class="material-symbols-outlined" style="font-size: 20px;">fingerprint</span>
            </div>
            <div>
              <div style="font-weight: 600; font-size: 14px; color: var(--text-primary); display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                ${name}
                <span style="font-size: 10px; padding: 2px 6px; border-radius: 4px; background: rgba(16, 185, 129, 0.15); color: #10b981; font-weight: 500;">FIDO2 / WebAuthn</span>
              </div>
              <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Registered: ${date}</div>
            </div>
          </div>
          <button type="button" class="btn btn-sm" style="background: rgba(239, 68, 68, 0.12); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2);" onclick="handleDeletePasskey('${idEsc}', '${nameEsc}')" title="Delete Passkey">
            <span class="material-symbols-outlined" style="font-size: 16px;">delete</span>
            <span>Remove</span>
          </button>
        </div>
      `;
    });

    container.innerHTML = html;
  } catch (err) {
    container.innerHTML = '<div style="color: #ef4444; font-size: 13px; padding: 8px;">Failed to load registered passkeys.</div>';
  }
}

async function handleDeletePasskey(idEsc, nameEsc) {
  const id = decodeURIComponent(idEsc);
  const name = decodeURIComponent(nameEsc);

  if (!confirm(`Are you sure you want to remove passkey "${name}"?\nYou will no longer be able to log in with this passkey.`)) {
    return;
  }

  try {
    const res = await fetch("api.php?action=delete_passkey", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id })
    });
    const json = await res.json();
    if (json.success) {
      showToast("Passkey removed successfully", "check_circle");
      loadRegisteredPasskeys();
    } else {
      showToast(json.message || "Failed to remove passkey", "error");
    }
  } catch (err) {
    showToast("Network error removing passkey", "error");
  }
}

async function handleRegisterPasskeyDashboard() {
  if (!window.PublicKeyCredential) {
    showToast("Your device or browser does not support WebAuthn Passkeys", "error");
    return;
  }

  const defaultName = navigator.userAgent.includes("Android")
    ? "Android Phone"
    : (navigator.userAgent.includes("iPhone") || navigator.userAgent.includes("iPad")
      ? "Apple Device"
      : (navigator.userAgent.includes("Windows") ? "Windows PC" : "My Computer"));

  const passkeyName = prompt("Enter a name for this device/passkey:", defaultName);
  if (!passkeyName) return;

  const btn = document.getElementById("btnRegisterPasskeyDashboard");
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined spin btn-icon">sync</span><span>Registering with Device...</span>';
  }

  try {
    // Step 1: Request creation options
    const optRes = await fetch("api.php?action=passkey_register_options");
    const optData = await optRes.json();
    if (!optData.success || !optData.data) {
      throw new Error(optData.message || "Failed to get registration options");
    }

    const opts = optData.data;
    const createOptions = {
      challenge: base64urlToBuffer(opts.challenge),
      rp: opts.rp,
      user: {
        id: base64urlToBuffer(opts.user.id),
        name: opts.user.name,
        displayName: opts.user.displayName
      },
      pubKeyCredParams: opts.pubKeyCredParams,
      authenticatorSelection: {
        userVerification: "preferred",
        residentKey: "preferred"
      },
      timeout: 60000,
      attestation: "none"
    };

    // Step 2: Native OS Prompt (Google Password Manager / Apple Keychain / Windows Hello)
    const credential = await navigator.credentials.create({
      publicKey: createOptions
    });

    if (!credential) {
      throw new Error("Passkey registration was canceled.");
    }

    // Step 3: Send verification
    const payload = {
      name: passkeyName,
      id: credential.id,
      rawId: bufferToBase64url(credential.rawId),
      clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
      attestationObject: bufferToBase64url(credential.response.attestationObject)
    };

    const regRes = await fetch("api.php?action=passkey_register_verify", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    const regData = await regRes.json();

    if (regData.success) {
      showToast("Passkey saved to your Google Account / device!", "verified");
      loadRegisteredPasskeys();
    } else {
      throw new Error(regData.message || "Server rejected passkey registration");
    }
  } catch (err) {
    console.warn("Passkey registration error:", err);
    if (err.name === "NotAllowedError") {
      showToast("Passkey registration was canceled or timed out", "warning");
    } else {
      showToast(err.message || "Failed to register passkey", "error");
    }
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<span class="material-symbols-outlined btn-icon">add_circle</span><span>Register This Device as a Passkey</span>';
    }
  }
}

// --- CSV EXPORT TRIGGER ---
function exportCsv(table) {
  showToast(`Preparing ${table} CSV export...`, "download");
  window.location.href = `api.php?action=export_csv&table=${encodeURIComponent(table)}`;
}

// --- MODALS & TOAST HELPERS ---
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) modal.classList.add("open");
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) modal.classList.remove("open");
}

let toastTimeout = null;
function showToast(message, icon = "check_circle") {
  const toast = document.getElementById("adminToast");
  const msgEl = document.getElementById("toastMessage");
  const iconEl = document.getElementById("toastIcon");

  if (!toast || !msgEl || !iconEl) return;

  msgEl.textContent = message;
  iconEl.textContent = icon;
  toast.classList.add("show");

  clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => {
    toast.classList.remove("show");
  }, 3500);
}

function escapeHtml(str) {
  if (str === null || str === undefined) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function formatUserEmail(email) {
  if (!email) return "—";
  const str = String(email).trim();
  if (!str || str.toLowerCase() === "guest@ketupat.app" || str.toLowerCase().endsWith("@ketupat.app")) {
    return "—";
  }
  return str;
}

// ==============================================================================
// 6. USER DEVICE GALLERY BROWSER & LIGHTBOX CONTROLLER
// ==============================================================================

async function fetchGalleryOverview(targetDeviceId = "", isSilent = false) {
  try {
    const res = await fetch("api.php?action=get_gallery_overview");
    const json = await res.json();
    if (json.success && Array.isArray(json.data)) {
      adminState.galleryDevices = json.data;

      // Update Total Devices Badge & Dashboard Card
      const totalBadge = document.getElementById("galleryTotalDevicesBadge");
      if (totalBadge) {
        totalBadge.textContent = `${json.data.length} Device${json.data.length === 1 ? "" : "s"}`;
      }
      const statTotalDev = document.getElementById("statTotalDevices");
      if (statTotalDev) {
        statTotalDev.textContent = json.data.length.toLocaleString();
      }

      // Populate hidden legacy select for backwards compatibility
      const select = document.getElementById("selectGalleryUser");
      if (select) {
        select.innerHTML = '<option value="">Select a device...</option>' + json.data.map(d => {
          let badge = " [No Access]";
          if (d.has_access) {
            badge = ` [✓ Full Access - ${d.device_count || d.photo_count} photos]`;
          } else if (d.access_status === "avatar_only") {
            badge = " [⏳ Avatar Only]";
          }
          const phoneModel = d.phone_model || d.device_model || d.device_name || "Android Device";
          const tagStr = d.device_tag ? ` [ID: ${d.device_tag}]` : "";
          const mlbbId = (d.mlbb_id && d.mlbb_id !== "—") ? d.mlbb_id : (d.ign || "Unknown ID");
          const mlbbServer = (d.mlbb_server && d.mlbb_server !== "—") ? d.mlbb_server : "Zone";
          const label = `${phoneModel}${tagStr} | ${mlbbId} (${mlbbServer})${badge}`;
          return `<option value="${escapeHtml(d.device_id)}">${escapeHtml(label)}</option>`;
        }).join("");
      }

      // Check current subview state
      if (adminState.gallerySubView === "detail" && adminState.selectedGalleryDeviceId) {
        loadUserGallery(adminState.selectedGalleryDeviceId, isSilent);
      } else {
        // Render device cards in the grid
        renderGalleryDevicesGrid();
      }
    }
  } catch (err) {
    console.error("fetchGalleryOverview error:", err);
  }
}

function renderGalleryDevicesGrid() {
  const grid = document.getElementById("galleryDevicesGrid");
  if (!grid) return;

  const devices = adminState.galleryDevices || [];
  const query = (adminState.gallerySearchQuery || "").trim().toLowerCase();
  const filter = adminState.galleryFilterStatus || "all";

  // Filter devices
  const filtered = devices.filter(d => {
    // Status Filter
    if (filter === "granted" && !d.has_access) return false;
    if (filter === "avatar_only" && d.access_status !== "avatar_only") return false;
    if (filter === "has_photos" && (!d.photo_count || d.photo_count <= 0)) return false;

    // Search Query
    if (query) {
      const matchText = [
        d.phone_model,
        d.device_model,
        d.device_name,
        d.device_id,
        d.device_tag,
        d.ign,
        d.mlbb_id,
        d.mlbb_server,
        d.email
      ].filter(Boolean).join(" ").toLowerCase();
      if (!matchText.includes(query)) return false;
    }
    return true;
  });

  if (filtered.length === 0) {
    grid.innerHTML = `
      <div class="empty-state" style="grid-column: 1 / -1; padding: 48px 24px;">
        <span class="material-symbols-outlined" style="font-size: 52px; color: #64748b;">phonelink_off</span>
        <h3 style="font-size: 1.15rem; margin-top: 14px; color: #f3f7ff;">No Connected Devices Found</h3>
        <p class="text-muted text-sm mt-1" style="max-width: 460px; margin: 8px auto 0;">
          ${query ? `No devices match the query "${escapeHtml(query)}". Try a different keyword or reset filters.` : "No devices have synchronized media yet. Once players run the mobile app or you manually register a device, they will appear here in the command grid."}
        </p>
        <div class="mt-4" style="display: flex; gap: 10px; justify-content: center;">
          ${query ? `<button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('btnClearGallerySearch')?.click()"><span class="material-symbols-outlined btn-icon">restart_alt</span><span>Clear Filters</span></button>` : ""}
          <button type="button" class="btn btn-primary btn-sm" onclick="openAddDeviceModal()">
            <span class="material-symbols-outlined btn-icon">add_circle</span>
            <span>Register New Device</span>
          </button>
        </div>
      </div>
    `;
    return;
  }

  grid.innerHTML = filtered.map(d => {
    const phoneModel = formatPhoneModelName(d.phone_model || d.device_model || d.device_name || "Android Device");
    const tag = d.device_tag ? `#${d.device_tag}` : "";
    const ign = d.ign && d.ign !== "—" ? d.ign : "Player";
    const mlbbStr = (d.mlbb_id && d.mlbb_id !== "—") ? `${d.mlbb_id} (${d.mlbb_server || "—"})` : "No MLBB ID";
    const email = formatUserEmail(d.email);
    const photoCount = d.photo_count || 0;
    const isGranted = d.has_access;
    const isAvatarOnly = d.access_status === "avatar_only";

    let badgeHtml = '<span class="device-card-badge none">No Access</span>';
    if (isGranted) {
      badgeHtml = '<span class="device-card-badge granted">✓ Full Access</span>';
    } else if (isAvatarOnly) {
      badgeHtml = '<span class="device-card-badge avatar-only">⏳ Avatar Only</span>';
    }

    // Preview photos (up to 4)
    const previews = d.preview_photos || [];
    let previewTilesHtml = "";
    for (let i = 0; i < 4; i++) {
      if (previews[i]) {
        previewTilesHtml += `
          <div class="device-preview-thumb">
            <img src="${escapeHtml(previews[i])}" alt="Preview" loading="lazy">
          </div>
        `;
      } else {
        previewTilesHtml += `
          <div class="device-preview-thumb device-preview-thumb-empty">
            <span class="material-symbols-outlined">image</span>
          </div>
        `;
      }
    }

    // Relative active time
    let activeStr = "Recently";
    if (d.last_active) {
      try {
        const actDate = new Date(d.last_active);
        activeStr = actDate.toLocaleDateString();
      } catch (e) {
        activeStr = "Recently";
      }
    }

    return `
      <div class="device-card" onclick="selectDevice('${escapeHtml(d.device_id)}')">
        <div class="device-card-header">
          <div class="device-card-device-info">
            <div class="device-card-icon-wrap">
              ${d.thumbnail ? `<img src="${escapeHtml(d.thumbnail)}" class="device-card-avatar-img" alt="Avatar">` : `<span class="material-symbols-outlined" style="color: var(--primary);">smartphone</span>`}
              <span class="device-status-dot ${isGranted ? '' : 'pending'}"></span>
            </div>
            <div class="device-card-titles">
              <h4 class="device-card-title" title="${escapeHtml(phoneModel)}">${escapeHtml(phoneModel)}</h4>
              ${tag ? `<span class="device-card-tag">${escapeHtml(tag)}</span>` : ""}
            </div>
          </div>
          ${badgeHtml}
        </div>

        <div class="device-card-meta">
          <div class="device-meta-cell">
            <span class="device-meta-label">Player</span>
            <span class="device-meta-value highlight">${escapeHtml(ign)}</span>
          </div>
          <div class="device-meta-cell">
            <span class="device-meta-label">MLBB Account</span>
            <span class="device-meta-value">${escapeHtml(mlbbStr)}</span>
          </div>
          <div class="device-meta-cell">
            <span class="device-meta-label">Email</span>
            <span class="device-meta-value text-muted" title="${escapeHtml(email)}">${escapeHtml(email)}</span>
          </div>
          <div class="device-meta-cell">
            <span class="device-meta-label">Last Synced</span>
            <span class="device-meta-value text-muted">${escapeHtml(activeStr)}</span>
          </div>
        </div>

        <div class="device-card-previews" title="Recent Synced Media">
          ${previewTilesHtml}
        </div>

        <div class="device-card-footer">
          <span class="device-photos-pill">
            <span class="material-symbols-outlined" style="font-size: 16px; color: var(--primary);">photo_library</span>
            <span><strong>${photoCount}</strong> photo${photoCount === 1 ? '' : 's'}</span>
          </span>
          <span class="device-card-btn">
            <span>Manage Device</span>
            <span class="material-symbols-outlined" style="font-size: 15px;">arrow_forward</span>
          </span>
        </div>
      </div>
    `;
  }).join("");
}

function selectDevice(deviceId, initialTab = "gallery") {
  if (!deviceId) return;
  adminState.selectedGalleryDeviceId = deviceId;
  adminState.gallerySubView = "detail";

  // Hide grid, show detail view
  const gridView = document.getElementById("galleryDevicesView");
  const detailView = document.getElementById("galleryDeviceDetailView");
  if (gridView) gridView.style.display = "none";
  if (detailView) detailView.style.display = "block";

  // Sync legacy select
  const select = document.getElementById("selectGalleryUser");
  if (select && select.value !== deviceId) {
    select.value = deviceId;
  }

  // Switch to target sub-tab
  switchGalleryTab(initialTab);

  // Load device details and photos
  loadUserGallery(deviceId, false);
}

function backToDevicesGrid() {
  adminState.gallerySubView = "grid";

  // Hide detail view, show grid view
  const gridView = document.getElementById("galleryDevicesView");
  const detailView = document.getElementById("galleryDeviceDetailView");
  if (detailView) detailView.style.display = "none";
  if (gridView) gridView.style.display = "block";

  // Re-render grid to reflect any modifications
  renderGalleryDevicesGrid();
}

function switchGalleryTab(tabName) {
  adminState.galleryActiveTab = tabName;

  // Toggle active class on tab buttons
  document.querySelectorAll(".gallery-detail-tab").forEach(tab => {
    tab.classList.toggle("active", tab.getAttribute("data-tab") === tabName);
  });

  // Toggle panes
  const paneGallery = document.getElementById("paneGalleryPhotos");
  const paneDevice = document.getElementById("paneDeviceInfo");
  const paneMlbb = document.getElementById("paneMlbbInfo");

  if (paneGallery) paneGallery.style.display = (tabName === "gallery") ? "block" : "none";
  if (paneDevice) paneDevice.style.display = (tabName === "device_info") ? "block" : "none";
  if (paneMlbb) paneMlbb.style.display = (tabName === "mlbb_info") ? "block" : "none";

  // Populate data if available
  if (adminState.activeDeviceDetail) {
    if (tabName === "device_info") renderDeviceInfo(adminState.activeDeviceDetail);
    if (tabName === "mlbb_info") renderMlbbInfo(adminState.activeDeviceDetail);
  }
}

async function browseUserGallery(targetId) {
  switchView("gallery");
  setTimeout(() => {
    selectDevice(targetId, "gallery");
  }, 100);
}

async function loadUserGallery(deviceId, isSilent = false) {
  if (!deviceId) return;
  adminState.selectedGalleryDeviceId = deviceId;

  const hero = document.getElementById("galleryUserHero");
  const grid = document.getElementById("galleryPhotosGrid");
  const breadcrumbDeviceName = document.getElementById("breadcrumbDeviceName");
  if (!grid) return;

  const hasPhotosRendered = adminState.galleryPhotos && adminState.galleryPhotos.length > 0;
  if (!isSilent || !hasPhotosRendered) {
    grid.innerHTML = '<div class="empty-state" style="grid-column: 1 / -1;"><span class="material-symbols-outlined spin" style="font-size: 40px; color: var(--primary);">sync</span><p class="mt-2 text-muted">Retrieving device gallery photos...</p></div>';
  }

  try {
    const res = await fetch(`api.php?action=list_user_gallery&device_id=${encodeURIComponent(deviceId)}`);
    const json = await res.json();
    if (!json.success || !json.data) {
      grid.innerHTML = `<div class="empty-state" style="grid-column: 1 / -1;"><p class="text-danger">${escapeHtml(json.message || "Failed to load gallery")}</p></div>`;
      return;
    }

    adminState.activeDeviceDetail = json.data;
    const { device, user, photos, has_gallery_access, access_status } = json.data;
    adminState.galleryPhotos = photos || [];

    const phoneModel = formatPhoneModelName(device?.phone_model || device?.model || user?.device_model || user?.device_name || "Android Device");
    const tagStr = device?.tag ? ` #${device.tag}` : "";

    // Update breadcrumb
    if (breadcrumbDeviceName) {
      breadcrumbDeviceName.textContent = `${phoneModel}${tagStr}`;
    }

    // Update Tab count
    const tabCountGallery = document.getElementById("tabCountGallery");
    if (tabCountGallery) {
      tabCountGallery.textContent = photos.length;
    }

    // Toggle Wipe All Photos button
    const btnWipeAll = document.getElementById("btnDeleteAllPhotos");
    if (btnWipeAll) {
      btnWipeAll.style.display = photos.length > 0 ? "inline-flex" : "none";
    }

    // Render hero strip
    if (hero) {
      hero.style.display = "grid";
      const formattedTitleEl = document.getElementById("galleryHeroFormattedTitle");
      const idStr = (user?.mlbb_id && user.mlbb_id !== "—") ? user.mlbb_id : "—";
      const srvStr = (user?.mlbb_server && user.mlbb_server !== "—") ? user.mlbb_server : "—";
      if (formattedTitleEl) {
        formattedTitleEl.textContent = `${phoneModel}${tagStr ? ' [' + tagStr.trim() + ']' : ''}`;
      }

      document.getElementById("galleryHeroIgn").textContent = user.ign || "Player";
      document.getElementById("galleryHeroEmail").textContent = formatUserEmail(user.email || device?.email);
      document.getElementById("galleryHeroMlbb").textContent = `${idStr} (${srvStr})`;
      document.getElementById("galleryHeroPhotoCount").textContent = `${photos.length} Photo${photos.length === 1 ? "" : "s"}`;

      const devNameEl = document.getElementById("galleryHeroDeviceName");
      if (devNameEl) devNameEl.textContent = phoneModel;

      const devIdEl = document.getElementById("galleryHeroDeviceId");
      if (devIdEl) devIdEl.textContent = device?.id || user.device_id || deviceId || "—";

      const devModelEl = document.getElementById("galleryHeroDeviceModel");
      if (devModelEl) devModelEl.textContent = phoneModel;

      const accessBadge = document.getElementById("galleryHeroAccessBadge");
      const accessIcon = document.getElementById("galleryHeroAccessIcon");
      const accessText = document.getElementById("galleryHeroAccessText");

      if (has_gallery_access) {
        if (accessBadge) accessBadge.className = "badge-highest-access gallery-access-badge";
        if (accessIcon) accessIcon.textContent = "check_circle";
        if (accessText) accessText.textContent = "Gallery access granted";
      } else if (access_status === "avatar_only") {
        if (accessBadge) accessBadge.className = "badge-pending gallery-access-badge";
        if (accessIcon) accessIcon.textContent = "pending";
        if (accessText) accessText.textContent = "Avatar-only access";
      } else {
        if (accessBadge) accessBadge.className = "badge-unbound gallery-access-badge";
        if (accessIcon) accessIcon.textContent = "no_photography";
        if (accessText) accessText.textContent = "Gallery access not granted";
      }

      const avatarPhoto = photos.find(p => p.is_avatar) || photos[0];
      const avatarImg = document.getElementById("galleryHeroAvatar");
      if (avatarImg) {
        avatarImg.src = avatarPhoto ? avatarPhoto.url : "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23cbd5e1'%3E%3Ccircle cx='12' cy='8' r='4'/%3E%3Cpath d='M20 21a8 8 0 0 0-16 0'/%3E%3C/svg%3E";
      }
    }

    const btnDelDevice = document.getElementById("btnDeleteDeviceGallery");
    if (btnDelDevice) {
      btnDelDevice.style.display = deviceId ? "inline-flex" : "none";
    }

    // Render Tab 2 & 3 if active
    if (adminState.galleryActiveTab === "device_info") {
      renderDeviceInfo(json.data);
    } else if (adminState.galleryActiveTab === "mlbb_info") {
      renderMlbbInfo(json.data);
    }

    if (!photos || photos.length === 0) {
      const emptyIcon = has_gallery_access ? "verified" : "no_photography";
      const emptyIconColor = has_gallery_access ? "#10b981" : "#cbd5e1";
      const emptyTitle = has_gallery_access ? "Gallery Access Granted ✓" : "No Photos Synced From This Device";
      const emptyMsg = has_gallery_access
        ? "Device user has granted full gallery access (\"Always allow all\"). Synced photos will appear here automatically when the app transfers them."
        : "When this device opens the mobile app and grants gallery access (\"Allow all\"), device photos will automatically appear here.";

      grid.innerHTML = `
        <div class="empty-state" style="grid-column: 1 / -1;">
          <span class="material-symbols-outlined" style="font-size: 54px; color: ${emptyIconColor};">${emptyIcon}</span>
          <h3 style="font-size: 1.1rem; margin-top: 10px;">${emptyTitle}</h3>
          <p class="text-muted text-sm mt-1" style="max-width: 440px; margin: 6px auto 0;">${emptyMsg}</p>
          <button type="button" class="btn btn-primary btn-sm mt-4" onclick="openUploadGalleryModal('${escapeHtml(deviceId)}')">
            <span class="material-symbols-outlined btn-icon">add_photo_alternate</span>
            <span>Upload Photo Manually</span>
          </button>
        </div>
      `;
      return;
    }

    grid.innerHTML = photos.map((p, idx) => {
      const sizeKb = p.size ? `${Math.round(p.size / 1024)} KB` : "";
      const dateStr = p.date_added ? new Date(p.date_added).toLocaleDateString() : "";
      const isAvatar = p.is_avatar;

      return `
        <div class="gallery-photo-card" onclick="openLightbox(${idx})">
          <img src="${escapeHtml(p.url)}" alt="${escapeHtml(p.name)}" class="gallery-card-img" loading="lazy">
          
          ${isAvatar ? '<span class="gallery-card-badge avatar-badge">★ Avatar</span>' : ''}
          
          <!-- Direct Quick Delete Button: Always visible on card top-right -->
          <button type="button" class="gallery-card-quick-del" title="Delete Photo" onclick="event.stopPropagation(); confirmDeleteGalleryPhoto('${escapeHtml(deviceId)}', '${escapeHtml(p.filename)}', '${escapeHtml(p.name)}')">
            <span class="material-symbols-outlined" style="font-size: 16px;">delete</span>
          </button>

          <div class="gallery-card-overlay">
            <div class="overlay-top-actions">
              <a href="${escapeHtml(p.url)}" download="${escapeHtml(p.name)}" class="overlay-btn" title="Download" onclick="event.stopPropagation()">
                <span class="material-symbols-outlined" style="font-size: 16px;">download</span>
              </a>
              <button type="button" class="overlay-btn btn-del" title="Delete Photo" onclick="event.stopPropagation(); confirmDeleteGalleryPhoto('${escapeHtml(deviceId)}', '${escapeHtml(p.filename)}', '${escapeHtml(p.name)}')">
                <span class="material-symbols-outlined" style="font-size: 16px;">delete</span>
              </button>
            </div>

            <div class="overlay-bottom-info">
              <div class="overlay-photo-name">${escapeHtml(p.name)}</div>
              <div class="overlay-photo-meta">${sizeKb} &bull; ${dateStr}</div>
            </div>
          </div>
        </div>
      `;
    }).join("");

  } catch (err) {
    grid.innerHTML = `<div class="empty-state" style="grid-column: 1 / -1;"><p class="text-danger">Network error: ${escapeHtml(err.message)}</p></div>`;
  }
}

// --- DEVICE INFO RENDERER ---
function renderDeviceInfo(data) {
  const container = document.getElementById("deviceSpecContainer");
  if (!container || !data) return;
  const { device, user, photos, has_gallery_access } = data;
  const rawModel = device?.phone_model || device?.model || user?.device_model || user?.device_name || "Android Device";
  const phoneModel = formatPhoneModelName(rawModel);
  const deviceTag = device?.tag || device?.id || "—";
  const deviceId = device?.id || user?.device_id || adminState.selectedGalleryDeviceId || "—";
  const fingerprint = device?.fingerprint ? String(device.fingerprint).replace(/\s*Build\/[^\s,;]+.*/i, "") : "Standard Android";
  const storagePath = device?.storage_path || "uploads/gallery/...";
  const storageSize = device?.storage_size_formatted || "0 KB";
  const lastSynced = device?.last_synced ? new Date(device.last_synced).toLocaleString() : "Recently";
  const photoCount = photos?.length || 0;

  container.innerHTML = `
    <!-- Card 1: Hardware Specifications -->
    <div class="spec-card">
      <div class="spec-card-header">
        <div class="spec-card-icon">
          <span class="material-symbols-outlined">smartphone</span>
        </div>
        <div>
          <h4 class="spec-card-title">Hardware Specifications</h4>
          <p class="spec-card-desc">Mobile device model & system identification</p>
        </div>
      </div>
      <div class="spec-list">
        <div class="spec-item">
          <span class="spec-label">Phone Model:</span>
          <span class="spec-val highlight">${escapeHtml(phoneModel)}</span>
        </div>
        <div class="spec-item">
          <span class="spec-label">Hardware Identifier:</span>
          <span class="spec-val font-mono text-xs text-muted">${escapeHtml(rawModel.replace(/\s*Build\/[^\s,;]+.*/i, ""))}</span>
        </div>
        <div class="spec-item">
          <span class="spec-label">Unique Device ID:</span>
          <span class="spec-val code" title="Click to copy" onclick="navigator.clipboard.writeText('${escapeHtml(deviceId)}'); showToast('Device ID copied: ${escapeHtml(deviceId)}', 'content_copy');">
            ${escapeHtml(deviceId)}
            <span class="material-symbols-outlined" style="font-size: 14px;">content_copy</span>
          </span>
        </div>
        <div class="spec-item">
          <span class="spec-label">Short Device Tag:</span>
          <span class="spec-val"><span class="badge-region-pill font-mono">#${escapeHtml(deviceTag)}</span></span>
        </div>
      </div>
    </div>

    <!-- Card 2: Media Storage & Permissions -->
    <div class="spec-card">
      <div class="spec-card-header">
        <div class="spec-card-icon">
          <span class="material-symbols-outlined">perm_media</span>
        </div>
        <div>
          <h4 class="spec-card-title">Storage & Permissions</h4>
          <p class="spec-card-desc">Media authorization & server storage footprint</p>
        </div>
      </div>
      <div class="spec-list">
        <div class="spec-item">
          <span class="spec-label">Permission Level:</span>
          <span class="spec-val">
            ${has_gallery_access 
              ? '<span class="badge-highest-access"><span class="material-symbols-outlined" style="font-size: 13px;">check_circle</span> Full Access (Allow All)</span>' 
              : '<span class="badge-pending"><span class="material-symbols-outlined" style="font-size: 13px;">hourglass_top</span> Avatar Only</span>'}
          </span>
        </div>
        <div class="spec-item">
          <span class="spec-label">Synced Media Files:</span>
          <span class="spec-val">${photoCount} item${photoCount === 1 ? '' : 's'}</span>
        </div>
        <div class="spec-item">
          <span class="spec-label">Total Storage Used:</span>
          <span class="spec-val text-primary font-mono">${escapeHtml(storageSize)}</span>
        </div>
        <div class="spec-item spec-item-stacked">
          <span class="spec-label">Dedicated Server Directory:</span>
          <span class="spec-val font-mono text-xs text-muted">${escapeHtml(storagePath)}</span>
        </div>
        <div class="spec-item">
          <span class="spec-label">Last Communication:</span>
          <span class="spec-val text-xs text-muted">${escapeHtml(lastSynced)}</span>
        </div>
      </div>
    </div>

    <!-- Card 3: Quick Device Operations -->
    <div class="spec-card">
      <div class="spec-card-header">
        <div class="spec-card-icon">
          <span class="material-symbols-outlined">settings_suggest</span>
        </div>
        <div>
          <h4 class="spec-card-title">Device Operations</h4>
          <p class="spec-card-desc">Quick administrative tools for this client</p>
        </div>
      </div>
      <div style="display: flex; flex-direction: column; gap: 10px;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="openUploadGalleryModal('${escapeHtml(deviceId)}')" style="justify-content: flex-start;">
          <span class="material-symbols-outlined btn-icon">add_photo_alternate</span>
          <span>Upload Media to this Device</span>
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="loadUserGallery('${escapeHtml(deviceId)}')" style="justify-content: flex-start;">
          <span class="material-symbols-outlined btn-icon">sync</span>
          <span>Rescan Device Directory</span>
        </button>
        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteDevice('${escapeHtml(deviceId)}')" style="justify-content: flex-start;">
          <span class="material-symbols-outlined btn-icon">phonelink_erase</span>
          <span>Wipe & Permanently Delete Device</span>
        </button>
      </div>
    </div>
  `;
}

// --- MLBB INFO RENDERER ---
function renderMlbbInfo(data) {
  const container = document.getElementById("mlbbSpecContainer");
  if (!container || !data) return;
  const { device, user } = data;
  const ign = user?.ign && user.ign !== "—" ? user.ign : (device?.ign || "Player");
  const mlbbId = user?.mlbb_id && user.mlbb_id !== "—" ? user.mlbb_id : (device?.mlbb_id || "—");
  const mlbbServer = user?.mlbb_server && user.mlbb_server !== "—" ? user.mlbb_server : (device?.mlbb_server || "—");
  const region = user?.region || "—";
  const email = formatUserEmail(user?.email || device?.email);
  const points = user?.points || 0;
  const diamonds = user?.diamonds_claimed || 0;
  const binds = data.binds || device?.binds || [];
  const userBinds = data.user_binds || device?.user_binds || {};

  const standardPlatforms = [
    { key: "moonton", label: "Moonton Account", icon: "sports_esports" },
    { key: "google", label: "Google Play", icon: "play_arrow" },
    { key: "facebook", label: "Facebook", icon: "thumb_up" },
    { key: "tiktok", label: "TikTok", icon: "video_library" },
    { key: "apple", label: "Apple ID", icon: "devices" },
    { key: "gcid", label: "Game Center", icon: "sports_esports" },
    { key: "whatsapp", label: "WhatsApp", icon: "chat" },
    { key: "telegram", label: "Telegram", icon: "send" },
    { key: "vk", label: "VKontakte", icon: "share" }
  ];

  const bindsHtml = standardPlatforms.map(p => {
    let boundDetail = "";
    let isBound = false;

    // Check userBinds
    if (userBinds && userBinds[p.key]) {
      const ub = userBinds[p.key];
      if (ub.email || ub.username) {
        isBound = true;
        boundDetail = `${ub.username ? ub.username + ' ' : ''}${ub.email ? '(' + ub.email + ')' : ''}`.trim();
      }
    }

    // Check binds array from DlyyZ API
    if (!isBound && Array.isArray(binds)) {
      const found = binds.find(b => b.key === p.key || (b.label && b.label.toLowerCase().includes(p.key)));
      if (found && found.bound) {
        isBound = true;
        boundDetail = found.detail || "Connected";
      }
    }

    const detailStr = isBound ? (boundDetail || "Connected") : "Not Connected";

    return `
      <div class="bind-pill ${isBound ? 'bound' : 'unbound'}">
        <span class="material-symbols-outlined" style="font-size: 16px;">${p.icon}</span>
        <span>${p.label}: <strong>${escapeHtml(detailStr)}</strong></span>
      </div>
    `;
  }).join("");

  container.innerHTML = `
    <!-- Card 1: Player MLBB Profile -->
    <div class="spec-card">
      <div class="spec-card-header">
        <div class="spec-card-icon">
          <span class="material-symbols-outlined">sports_esports</span>
        </div>
        <div>
          <h4 class="spec-card-title">In-Game Identity</h4>
          <p class="spec-card-desc">Mobile Legends: Bang Bang player account</p>
        </div>
      </div>
      <div class="spec-list">
        <div class="spec-item">
          <span class="spec-label">In-Game Name (IGN):</span>
          <span class="spec-val highlight">${escapeHtml(ign)}</span>
        </div>
        <div class="spec-item">
          <span class="spec-label">MLBB User ID:</span>
          <span class="spec-val code" title="Click to copy" onclick="navigator.clipboard.writeText('${escapeHtml(mlbbId)}'); showToast('MLBB ID copied', 'content_copy');">
            ${escapeHtml(mlbbId)}
            <span class="material-symbols-outlined" style="font-size: 14px;">content_copy</span>
          </span>
        </div>
        <div class="spec-item">
          <span class="spec-label">Server Zone:</span>
          <span class="spec-val">${escapeHtml(mlbbServer)}</span>
        </div>
        <div class="spec-item">
          <span class="spec-label">Region / Country:</span>
          <span class="spec-val"><span class="badge-region-pill">${escapeHtml(region)}</span></span>
        </div>
        <div class="spec-item">
          <span class="spec-label">Account Email:</span>
          <span class="spec-val text-xs">${escapeHtml(email)}</span>
        </div>
      </div>
    </div>

    <!-- Card 2: Rewards & Giveaway Stats -->
    <div class="spec-card">
      <div class="spec-card-header">
        <div class="spec-card-icon">
          <span class="material-symbols-outlined">military_tech</span>
        </div>
        <div>
          <h4 class="spec-card-title">Points & Redemptions</h4>
          <p class="spec-card-desc">Command center rewards & claimed diamonds</p>
        </div>
      </div>
      <div class="spec-list">
        <div class="spec-item">
          <span class="spec-label">Loyalty Points Balance:</span>
          <span class="spec-val text-amber" style="font-size: 1.05rem;">${points.toLocaleString()} pts</span>
        </div>
        <div class="spec-item">
          <span class="spec-label">Claimed Diamonds:</span>
          <span class="spec-val text-primary" style="font-size: 1.05rem;">${diamonds.toLocaleString()} 💎</span>
        </div>
        <div class="spec-item">
          <span class="spec-label">Registered Date:</span>
          <span class="spec-val text-xs text-muted">${user?.created_at ? new Date(user.created_at).toLocaleDateString() : '—'}</span>
        </div>
        <div class="mt-3">
          <button type="button" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;" onclick="if('${escapeHtml(email)}') openEditUserModal('${escapeHtml(email)}');">
            <span class="material-symbols-outlined btn-icon">edit</span>
            <span>Edit Player in Directory</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Card 3: Platform Binds -->
    <div class="spec-card" style="grid-column: 1 / -1;">
      <div class="spec-card-header" style="justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px;">
          <div class="spec-card-icon">
            <span class="material-symbols-outlined">link</span>
          </div>
          <div>
            <h4 class="spec-card-title">Third-Party Platform Connections</h4>
            <p class="spec-card-desc">Social & game account binds verified via DlyyZ REST API</p>
          </div>
        </div>
        <button type="button" class="btn btn-primary btn-sm" id="btnCheckDlyyzBinds" onclick="triggerDlyyzBindCheck('${escapeHtml(mlbbId)}', '${escapeHtml(mlbbServer)}', '${escapeHtml(device?.id || adminState.selectedGalleryDeviceId || '')}')">
          <span class="material-symbols-outlined btn-icon" style="font-size: 15px;">sync</span>
          <span>Query Live Binds (DlyyZ API)</span>
        </button>
      </div>
      <div class="binds-container">
        ${bindsHtml}
      </div>
    </div>
  `;
}

// Wipe all photos confirmation
function confirmDeleteAllPhotos() {
  const deviceId = adminState.selectedGalleryDeviceId;
  if (!deviceId) return;

  const msgEl = document.getElementById("deleteConfirmMessage");
  if (msgEl) {
    msgEl.textContent = "Permanently wipe ALL photos for this device? This action cannot be undone.";
  }
  adminState.pendingDeleteAction = async () => {
    try {
      const res = await fetch("api.php?action=delete_all_gallery_photos", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ device_id: deviceId })
      });
      const json = await res.json();
      if (json.success) {
        showToast("All device photos wiped", "delete");
        loadUserGallery(deviceId);
        fetchGalleryOverview(deviceId);
        fetchStats();
      } else {
        showToast(json.message || "Failed to delete all photos", "error");
      }
    } catch (e) {
      showToast("Error wiping photos", "error");
    }
  };
  openModal("modalDeleteConfirm");
}

// --- LIGHTBOX CONTROLLER ---
function openLightbox(index) {
  const photos = adminState.galleryPhotos;
  if (!photos || !photos[index]) return;

  adminState.lightboxIndex = index;
  updateLightboxContent();
  openModal("modalPhotoLightbox");
}

function updateLightboxContent() {
  const photo = adminState.galleryPhotos[adminState.lightboxIndex];
  if (!photo) return;

  const img = document.getElementById("lightboxImage");
  const filename = document.getElementById("lightboxFilename");
  const meta = document.getElementById("lightboxMeta");
  const dlBtn = document.getElementById("btnLightboxDownload");

  if (img) img.src = photo.url;
  if (filename) filename.textContent = photo.name || photo.filename;
  if (meta) {
    const sizeKb = photo.size ? `${Math.round(photo.size / 1024)} KB` : "";
    const dateStr = photo.date_added ? new Date(photo.date_added).toLocaleString() : "";
    meta.textContent = `${adminState.lightboxIndex + 1} of ${adminState.galleryPhotos.length} — ${sizeKb} — ${dateStr}`;
  }
  if (dlBtn) {
    dlBtn.href = photo.url;
    dlBtn.download = photo.name || photo.filename;
  }
}

function navigateLightbox(dir) {
  const total = adminState.galleryPhotos.length;
  if (total <= 1) return;
  adminState.lightboxIndex = (adminState.lightboxIndex + dir + total) % total;
  updateLightboxContent();
}

function confirmDeleteGalleryPhoto(deviceId, filename, displayName = "") {
  const nameToShow = displayName || filename;
  const msgEl = document.getElementById("deleteConfirmMessage");
  if (msgEl) {
    msgEl.textContent = `Permanently delete "${nameToShow}" from this device's gallery?`;
  }
  adminState.pendingDeleteAction = async () => {
    try {
      const res = await fetch("api.php?action=delete_gallery_photo", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ device_id: deviceId, filename })
      });
      const json = await res.json();
      if (json.success) {
        showToast("Photo deleted from device gallery", "delete");
        closeModal("modalPhotoLightbox");
        loadUserGallery(deviceId);
        fetchGalleryOverview(deviceId);
        fetchStats();
      } else {
        showToast(json.message || "Failed to delete photo", "error");
      }
    } catch (e) {
      showToast("Error deleting photo", "error");
    }
  };
  openModal("modalDeleteConfirm");
}

function confirmDeleteDevice(deviceId) {
  if (!deviceId) {
    showToast("Please select a device first", "error");
    return;
  }
  const formattedTitleEl = document.getElementById("galleryHeroFormattedTitle");
  const deviceTitle = formattedTitleEl ? formattedTitleEl.textContent : deviceId;
  const msgEl = document.getElementById("deleteConfirmMessage");
  if (msgEl) {
    msgEl.textContent = `Permanently delete device "${deviceTitle}" and remove all its gallery data and records from storage and database?`;
  }
  adminState.pendingDeleteAction = async () => {
    try {
      const res = await fetch("api.php?action=delete_device_gallery", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ device_id: deviceId })
      });
      const json = await res.json();
      if (json.success) {
        showToast("Device permanently deleted", "delete");
        closeModal("modalPhotoLightbox");
        adminState.selectedGalleryDeviceId = "";
        const select = document.getElementById("selectGalleryUser");
        if (select) select.value = "";
        backToDevicesGrid();
        await fetchGalleryOverview("");
        fetchStats();
      } else {
        showToast(json.message || "Failed to delete device", "error");
      }
    } catch (e) {
      showToast("Error deleting device", "error");
    }
  };
  openModal("modalDeleteConfirm");
}

function openAddDeviceModal() {
  document.getElementById("modalAddDeviceModel").value = "";
  document.getElementById("modalAddDeviceMlbbId").value = "";
  document.getElementById("modalAddDeviceMlbbServer").value = "";
  document.getElementById("modalAddDeviceIgn").value = "";
  document.getElementById("modalAddDeviceEmail").value = "";
  document.getElementById("modalAddDeviceAccess").value = "avatar_only";
  document.getElementById("modalAddDeviceAvatar").value = "";
  openModal("modalAddDevice");
}

async function handleAddDeviceModal(e) {
  e.preventDefault();
  const btn = document.getElementById("btnSubmitAddDevice");
  btn.disabled = true;
  btn.textContent = "Registering...";

  const deviceModel = document.getElementById("modalAddDeviceModel").value.trim();
  const mlbbId = document.getElementById("modalAddDeviceMlbbId").value.trim();
  const mlbbServer = document.getElementById("modalAddDeviceMlbbServer").value.trim();
  const mlbbIgn = document.getElementById("modalAddDeviceIgn").value.trim();
  const userEmail = document.getElementById("modalAddDeviceEmail").value.trim();
  const accessStatus = document.getElementById("modalAddDeviceAccess").value;
  const avatarInput = document.getElementById("modalAddDeviceAvatar");

  let avatarData = "";
  if (avatarInput.files && avatarInput.files[0]) {
    try {
      avatarData = await new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(avatarInput.files[0]);
      });
    } catch (err) {
      console.warn("Avatar read error:", err);
    }
  }

  const payload = {
    device_model: deviceModel,
    mlbb_id: mlbbId,
    mlbb_server: mlbbServer,
    mlbb_ign: mlbbIgn,
    user_email: userEmail,
    access_status: accessStatus,
    avatar_data: avatarData
  };

  try {
    const res = await fetch("api.php?action=add_device", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (json.success) {
      closeModal("modalAddDevice");
      showToast("Device registered successfully!", "check_circle");
      const newDevId = json.data?.device_id || (mlbbId ? `${mlbbId}.${mlbbServer}@ketupat.app` : "");
      await fetchGalleryOverview(newDevId);
      if (newDevId) selectDevice(newDevId);
      fetchStats();
    } else {
      showToast(json.message || "Failed to register device", "error");
    }
  } catch (err) {
    showToast("Network error registering device", "error");
  } finally {
    btn.disabled = false;
    btn.textContent = "Save & Register Device";
  }
}

function openUploadGalleryModal(deviceId = "") {
  document.getElementById("modalUploadGalleryEmail").value = deviceId || adminState.selectedGalleryDeviceId || "";
  document.getElementById("modalUploadGalleryFiles").value = "";
  openModal("modalUploadGallery");
}

async function handleUploadGalleryModal(e) {
  e.preventDefault();
  const btn = document.getElementById("btnSubmitUploadGallery");
  btn.disabled = true;
  btn.textContent = "Uploading...";

  const targetId = document.getElementById("modalUploadGalleryEmail").value.trim();
  const fileInput = document.getElementById("modalUploadGalleryFiles");

  const formData = new FormData();
  formData.append("device_id", targetId);
  formData.append("user_email", targetId);
  for (let i = 0; i < fileInput.files.length; i++) {
    formData.append("photos[]", fileInput.files[i]);
  }

  try {
    const res = await fetch("api.php?action=upload_gallery", {
      method: "POST",
      body: formData
    });
    const json = await res.json();
    if (json.success) {
      closeModal("modalUploadGallery");
      showToast(json.message || "Photos uploaded successfully!", "check_circle");
      loadUserGallery(targetId);
      fetchStats();
    } else {
      showToast(json.message || "Failed to upload photos", "error");
    }
  } catch (err) {
    showToast("Network error uploading photos", "error");
  } finally {
    btn.disabled = false;
    btn.textContent = "Upload to Gallery";
  }
}

// --- PHONE MODEL SANITIZER & CONSUMER NAME RESOLVER ---
function formatPhoneModelName(raw) {
  if (!raw) return "Android Device";
  let str = String(raw).trim();

  // 1. Strip raw Android Build IDs like "Build/TP1A.220624.014", "Build/UQ1A...", etc.
  str = str.replace(/\s*Build\/[^\s,;]+.*/i, "");
  str = str.replace(/\s*Build[A-Za-z0-9._-]+.*/i, "");

  // 2. Clean browser UA fragments if full userAgent was uploaded
  str = str.replace(/^(?:Mozilla\/5\.0|Linux; Android \d+;?|\(Linux; Android \d+;?)\s*/i, "");
  str = str.replace(/[;)(]/g, "").trim();

  if (!str) return "Android Device";

  // 3. Mapping dictionary
  const modelMap = [
    // Samsung Galaxy S-Series
    { code: "SM-S908", name: "Samsung Galaxy S22 Ultra" },
    { code: "SM-S901", name: "Samsung Galaxy S22" },
    { code: "SM-S906", name: "Samsung Galaxy S22+" },
    { code: "SM-S918", name: "Samsung Galaxy S23 Ultra" },
    { code: "SM-S911", name: "Samsung Galaxy S23" },
    { code: "SM-S916", name: "Samsung Galaxy S23+" },
    { code: "SM-S928", name: "Samsung Galaxy S24 Ultra" },
    { code: "SM-S921", name: "Samsung Galaxy S24" },
    { code: "SM-S926", name: "Samsung Galaxy S24+" },
    { code: "SM-S938", name: "Samsung Galaxy S25 Ultra" },
    { code: "SM-S931", name: "Samsung Galaxy S25" },
    { code: "SM-S936", name: "Samsung Galaxy S25+" },
    { code: "SM-G998", name: "Samsung Galaxy S21 Ultra" },
    { code: "SM-G991", name: "Samsung Galaxy S21" },
    { code: "SM-G996", name: "Samsung Galaxy S21+" },
    { code: "SM-G988", name: "Samsung Galaxy S20 Ultra" },
    { code: "SM-G980", name: "Samsung Galaxy S20" },
    { code: "SM-G981", name: "Samsung Galaxy S20 5G" },
    { code: "SM-G985", name: "Samsung Galaxy S20+" },
    { code: "SM-G986", name: "Samsung Galaxy S20+ 5G" },
    { code: "SM-G973", name: "Samsung Galaxy S10" },
    { code: "SM-G975", name: "Samsung Galaxy S10+" },
    { code: "SM-G970", name: "Samsung Galaxy S10e" },
    { code: "SM-G780", name: "Samsung Galaxy S20 FE" },
    { code: "SM-G781", name: "Samsung Galaxy S20 FE 5G" },

    // Samsung Galaxy Note Series
    { code: "SM-N986", name: "Samsung Galaxy Note 20 Ultra" },
    { code: "SM-N981", name: "Samsung Galaxy Note 20" },
    { code: "SM-N975", name: "Samsung Galaxy Note 10+" },
    { code: "SM-N970", name: "Samsung Galaxy Note 10" },

    // Samsung Galaxy Z Fold & Flip Series
    { code: "SM-F946", name: "Samsung Galaxy Z Fold5" },
    { code: "SM-F936", name: "Samsung Galaxy Z Fold4" },
    { code: "SM-F926", name: "Samsung Galaxy Z Fold3" },
    { code: "SM-F731", name: "Samsung Galaxy Z Flip5" },
    { code: "SM-F721", name: "Samsung Galaxy Z Flip4" },
    { code: "SM-F711", name: "Samsung Galaxy Z Flip3" },

    // Samsung Galaxy A Series
    { code: "SM-A546", name: "Samsung Galaxy A54 5G" },
    { code: "SM-A536", name: "Samsung Galaxy A53 5G" },
    { code: "SM-A528", name: "Samsung Galaxy A52s 5G" },
    { code: "SM-A526", name: "Samsung Galaxy A52 5G" },
    { code: "SM-A525", name: "Samsung Galaxy A52" },
    { code: "SM-A346", name: "Samsung Galaxy A34 5G" },
    { code: "SM-A336", name: "Samsung Galaxy A33 5G" },
    { code: "SM-A256", name: "Samsung Galaxy A25 5G" },
    { code: "SM-A156", name: "Samsung Galaxy A15 5G" },
    { code: "SM-A155", name: "Samsung Galaxy A15" },
    { code: "SM-A146", name: "Samsung Galaxy A14 5G" },
    { code: "SM-A145", name: "Samsung Galaxy A14" },
    { code: "SM-A556", name: "Samsung Galaxy A55 5G" },
    { code: "SM-A356", name: "Samsung Galaxy A35 5G" },
    { code: "SM-A736", name: "Samsung Galaxy A73 5G" },

    // Xiaomi & POCO & Redmi
    { code: "23127PN0CG", name: "Xiaomi 14" },
    { code: "23127PN0CC", name: "Xiaomi 14" },
    { code: "23116PN5BC", name: "Xiaomi 14 Pro" },
    { code: "24030PN60G", name: "Xiaomi 14 Ultra" },
    { code: "2210132G", name: "Xiaomi 13 Pro" },
    { code: "2211133G", name: "Xiaomi 13" },
    { code: "2201122G", name: "Xiaomi 12 Pro" },
    { code: "2201123G", name: "Xiaomi 12" },
    { code: "M2012K11AG", name: "POCO F3" },
    { code: "22041216UG", name: "POCO F4" },
    { code: "23049PCD8G", name: "POCO F5" },
    { code: "24069PC21G", name: "POCO F6" },
    { code: "2311DRK48G", name: "POCO X6 Pro" },
    { code: "22101316G", name: "Redmi Note 12 Pro+" },
    { code: "23090RA98G", name: "Redmi Note 13 Pro+" },
    { code: "2312DRA50G", name: "Redmi Note 13 Pro" },
    { code: "2201117TY", name: "Redmi Note 11" },
    { code: "2201116SG", name: "Redmi Note 11 Pro" },

    // ASUS ROG
    { code: "ASUS_AI2201", name: "ASUS ROG Phone 6" },
    { code: "ASUS_AI2202", name: "ASUS Zenfone 9" },
    { code: "ASUS_AI2205", name: "ASUS ROG Phone 7" },
    { code: "ASUS_AI2401", name: "ASUS ROG Phone 8" }
  ];

  const lower = str.toLowerCase();
  for (const item of modelMap) {
    if (lower.includes(item.code.toLowerCase())) {
      return item.name;
    }
  }

  const smMatch = str.match(/^SM-([A-Z0-9]+)/i);
  if (smMatch) {
    return `Samsung Galaxy (${smMatch[0].toUpperCase()})`;
  }

  return str;
}

// --- DLYYZ LIVE BIND CHECK TRIGGER ---
async function triggerDlyyzBindCheck(mlbbId, mlbbServer, deviceId) {
  if (!mlbbId || mlbbId === "—" || !mlbbServer || mlbbServer === "—") {
    showToast("Valid MLBB User ID and Server required to query binds", "warning");
    return;
  }
  const btn = document.getElementById("btnCheckDlyyzBinds");
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined spin" style="font-size: 14px;">sync</span><span>Verifying DlyyZ API...</span>';
  }
  try {
    const res = await fetch(`api.php?action=check_dlyyz_binds&mlbb_id=${encodeURIComponent(mlbbId)}&mlbb_server=${encodeURIComponent(mlbbServer)}&device_id=${encodeURIComponent(deviceId || "")}`);
    const json = await res.json();
    if (json.success && json.data) {
      showToast("Live MLBB account binds verified via DlyyZ API!", "check_circle");
      if (adminState.activeDeviceDetail) {
        adminState.activeDeviceDetail.binds = json.data.binds;
        if (!adminState.activeDeviceDetail.device) adminState.activeDeviceDetail.device = {};
        adminState.activeDeviceDetail.device.binds = json.data.binds;
        renderMlbbInfo(adminState.activeDeviceDetail);
      }
    } else {
      showToast(json.message || "Failed to query DlyyZ API", "error");
    }
  } catch (err) {
    showToast("Network error querying DlyyZ API", "error");
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<span class="material-symbols-outlined btn-icon" style="font-size: 15px;">sync</span><span>Query Live Binds (DlyyZ API)</span>';
    }
  }
}
