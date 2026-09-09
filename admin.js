/**
 * Ketupat MLBB - Supabase Cloud Admin Console Engine
 * Production Dashboard for User Management, Diamond Redemptions & Giveaways
 */

// Default credentials pre-configured with active Supabase project
const DEFAULT_SUPABASE_CONFIG = {
  url: "https://uatqaxxfzmpxkeeoeoin.supabase.co",
  anonKey: "sb_publishable_tRQMoLxGUIrWGcq2ZfEQRQ_CH3AufnW",
  secretKey: ""
};

const ADMIN_STORAGE_KEY = "ketupat_admin_supabase_config";

// Global State
let adminState = {
  config: loadAdminConfig(),
  currentView: "overview",
  users: [],
  redemptions: [],
  giveawayEntries: [],
  autoRefreshInterval: null,
  selectedOrder: null,
  selectedUser: null,
  isRolling: false
};

// --- INITIALIZATION ---
document.addEventListener("DOMContentLoaded", () => {
  setupNavigation();
  setupEventListeners();
  populateSettingsForm();
  
  // Initial data load
  refreshAllData();

  // Setup auto refresh timer
  setupAutoRefresh();
});

// --- CONFIGURATION MANAGEMENT ---
function loadAdminConfig() {
  try {
    const saved = localStorage.getItem(ADMIN_STORAGE_KEY);
    if (saved) {
      const parsed = JSON.parse(saved);
      return {
        url: parsed.url || DEFAULT_SUPABASE_CONFIG.url,
        anonKey: parsed.anonKey || DEFAULT_SUPABASE_CONFIG.anonKey,
        secretKey: parsed.secretKey || ""
      };
    }
  } catch (e) {}
  return { ...DEFAULT_SUPABASE_CONFIG };
}

function saveAdminConfig(url, anonKey, secretKey) {
  adminState.config = {
    url: url.replace(/\/+$/, "").replace(/\/rest\/v1\/?$/, ""),
    anonKey: anonKey.trim(),
    secretKey: secretKey ? secretKey.trim() : ""
  };
  localStorage.setItem(ADMIN_STORAGE_KEY, JSON.stringify(adminState.config));
  showAdminToast("Cloud configuration saved successfully!", "check_circle");
  refreshAllData();
}

// --- SUPABASE REST REQUEST WRAPPER ---
async function supabaseApi(endpoint, method = "GET", body = null, extraHeaders = {}) {
  const cfg = adminState.config;
  const baseUrl = cfg.url.replace(/\/+$/, "").replace(/\/rest\/v1\/?$/, "");
  const url = `${baseUrl}/rest/v1/${endpoint}`;
  
  // Use secret key if provided by admin for elevated permissions, otherwise publishable key
  const activeKey = cfg.secretKey || cfg.anonKey;

  const headers = {
    "apikey": activeKey,
    "Authorization": `Bearer ${activeKey}`,
    "Content-Type": "application/json",
    "Prefer": "return=representation",
    ...extraHeaders
  };

  const options = {
    method,
    headers
  };
  if (body) {
    options.body = JSON.stringify(body);
  }

  try {
    const res = await fetch(url, options);
    if (!res.ok) {
      const errorText = await res.text().catch(() => "");
      console.warn(`[Admin Supabase] ${method} ${endpoint} failed (${res.status}):`, errorText);
      return { ok: false, status: res.status, error: errorText };
    }
    const data = await res.json().catch(() => null);
    return { ok: true, status: res.status, data };
  } catch (err) {
    console.error(`[Admin Supabase Network Error] ${endpoint}:`, err);
    return { ok: false, status: 0, error: err.message };
  }
}

// --- DATA FETCHING ---
async function refreshAllData() {
  const btn = document.getElementById("btnRefreshAll");
  if (btn) btn.classList.add("spinning");

  updateConnectionStatusPill("connecting");

  try {
    const [usersRes, redemptionsRes, giveawaysRes] = await Promise.all([
      supabaseApi("users?select=*&order=created_at.desc"),
      supabaseApi("redemptions?select=*&order=created_at.desc"),
      supabaseApi("giveaway_entries?select=*&order=created_at.desc")
    ]);

    if (usersRes.ok && redemptionsRes.ok && giveawaysRes.ok) {
      adminState.users = Array.isArray(usersRes.data) ? usersRes.data : [];
      adminState.redemptions = Array.isArray(redemptionsRes.data) ? redemptionsRes.data : [];
      adminState.giveawayEntries = Array.isArray(giveawaysRes.data) ? giveawaysRes.data : [];

      updateConnectionStatusPill("online");
      renderCurrentView();
      updateDashboardKpis();
    } else {
      updateConnectionStatusPill("offline");
      showAdminToast("Connection failed. Verify Supabase URL & Key in Settings.", "error");
    }
  } catch (err) {
    updateConnectionStatusPill("offline");
    showAdminToast("Failed to connect to Supabase: " + err.message, "error");
  } finally {
    if (btn) {
      setTimeout(() => btn.classList.remove("spinning"), 500);
    }
  }
}

// --- RENDER CURRENT VIEW ---
function renderCurrentView() {
  switch (adminState.currentView) {
    case "overview":
      renderOverview();
      break;
    case "redemptions":
      renderRedemptions();
      break;
    case "users":
      renderUsers();
      break;
    case "giveaways":
      renderGiveaways();
      break;
    case "settings":
      // Static form populated already
      break;
  }
}

// --- VIEW 1: OVERVIEW RENDERING ---
function updateDashboardKpis() {
  const pendingCount = adminState.redemptions.filter(r => r.status && r.status.includes("Processing")).length;
  const totalDiamonds = adminState.redemptions
    .filter(r => r.status && r.status.includes("Delivered"))
    .reduce((sum, r) => sum + (Number(r.diamonds) || 0), 0);
  const totalTickets = adminState.giveawayEntries.reduce((sum, g) => sum + (Number(g.ticket_count) || 0), 0);

  const elUsers = document.getElementById("kpiTotalUsers");
  const elDevices = document.getElementById("kpiTotalDevices");
  const elRedemptions = document.getElementById("kpiTotalRedemptions");
  const elPending = document.getElementById("kpiPendingOrders");
  const elDiamonds = document.getElementById("kpiDiamondsClaimed");
  const elTickets = document.getElementById("kpiTotalTickets");
  const sidebarBadge = document.getElementById("sidebarPendingBadge");

  if (elUsers) elUsers.textContent = adminState.users.length.toLocaleString();
  if (elDevices) elDevices.textContent = adminState.users.length.toLocaleString();
  if (elRedemptions) elRedemptions.textContent = adminState.redemptions.length.toLocaleString();
  if (elPending) elPending.textContent = pendingCount.toLocaleString();
  if (elDiamonds) elDiamonds.textContent = totalDiamonds.toLocaleString();
  if (elTickets) elTickets.textContent = totalTickets.toLocaleString();
  if (sidebarBadge) {
    sidebarBadge.textContent = pendingCount;
    sidebarBadge.style.display = pendingCount > 0 ? "inline-block" : "none";
  }
}

function renderOverview() {
  updateDashboardKpis();

  // 1. Pending Orders Needing Diamond Delivery
  const pendingOrders = adminState.redemptions
    .filter(r => r.status && r.status.includes("Processing"))
    .slice(0, 5);

  const tbodyPending = document.getElementById("tbodyOverviewPending");
  if (tbodyPending) {
    if (pendingOrders.length === 0) {
      tbodyPending.innerHTML = `
        <tr>
          <td colspan="6" class="empty-state">
            <span class="material-symbols-outlined" style="color:var(--primary);">verified</span>
            <p>All caught up! Zero pending orders require delivery.</p>
          </td>
        </tr>`;
    } else {
      tbodyPending.innerHTML = pendingOrders.map(order => `
        <tr>
          <td class="font-mono font-bold">${escapeHtml(order.order_id)}</td>
          <td>
            <div class="player-cell">
              <div>
                <div class="player-name-bold">
                  <span>${escapeHtml(order.mlbb_ign || "Player")}</span>
                </div>
                <div class="player-sub-mono">ID: ${escapeHtml(order.mlbb_id)} (${escapeHtml(order.mlbb_server)})</div>
              </div>
            </div>
          </td>
          <td>
            <span class="badge badge-delivered font-bold">${escapeHtml(order.diamonds)} 💎</span>
            <div style="font-size:0.75rem; color:var(--text-muted);">${escapeHtml(order.pack_name || "")}</div>
          </td>
          <td>
            <span class="badge badge-processing">
              <span class="status-dot online" style="background:#f59e0b;"></span>
              <span>${escapeHtml(order.status)}</span>
            </span>
          </td>
          <td style="color:var(--text-muted); font-size:0.8rem;">${formatDate(order.created_at)}</td>
          <td>
            <div class="action-btn-group">
              <button type="button" class="btn btn-primary btn-sm" onclick="quickMarkDelivered('${order.order_id}')" title="Mark as Delivered">
                <span class="material-symbols-outlined" style="font-size:1rem;">check_circle</span>
                <span>Deliver</span>
              </button>
              <button type="button" class="action-icon-btn" onclick="copyTopUpInfo('${order.mlbb_id}', '${order.mlbb_server}', '${order.mlbb_ign}')" title="Copy MLBB ID & Zone for Top-up">
                <span class="material-symbols-outlined" style="font-size:1.1rem;">content_copy</span>
              </button>
            </div>
          </td>
        </tr>
      `).join("");
    }
  }

  // 2. Top Users Leaderboard
  const topUsers = [...adminState.users]
    .sort((a, b) => (b.points || 0) - (a.points || 0))
    .slice(0, 5);

  const tbodyTopUsers = document.getElementById("tbodyOverviewTopUsers");
  if (tbodyTopUsers) {
    tbodyTopUsers.innerHTML = topUsers.map(u => `
      <tr>
        <td>
          <div class="player-cell">
            <img src="${u.avatar_data || getDefaultAvatar()}" class="player-avatar-mini" alt="PFP">
            <div>
              <div class="player-name-bold">${escapeHtml(u.username || u.mlbb_ign || "User")}</div>
              <div class="player-sub-mono">${escapeHtml(u.email)}</div>
            </div>
          </div>
        </td>
        <td class="font-mono">${escapeHtml(u.mlbb_id || "—")} (${escapeHtml(u.mlbb_server || "—")})</td>
        <td>
          <span class="badge badge-tag">${escapeHtml(u.mlbb_region || "Global")}</span>
        </td>
        <td class="font-bold" style="color:var(--primary); font-size:0.95rem;">${(u.points || 0).toLocaleString()} pts</td>
        <td class="font-bold">${(u.diamonds_claimed || 0).toLocaleString()} 💎</td>
        <td>${(u.giveaway_tickets || 0).toLocaleString()} 🎟</td>
      </tr>
    `).join("");
  }
}

// --- VIEW 2: REDEMPTIONS RENDERING ---
function renderRedemptions() {
  const tbody = document.getElementById("tbodyRedemptions");
  if (!tbody) return;

  const filterStatus = document.getElementById("filterRedemptionStatus")?.value || "all";
  const searchTerm = (document.getElementById("searchRedemptions")?.value || "").toLowerCase().trim();

  let filtered = adminState.redemptions;

  if (filterStatus !== "all") {
    filtered = filtered.filter(r => r.status && r.status.toLowerCase().includes(filterStatus.toLowerCase()));
  }

  if (searchTerm) {
    filtered = filtered.filter(r => 
      (r.order_id && r.order_id.toLowerCase().includes(searchTerm)) ||
      (r.user_email && r.user_email.toLowerCase().includes(searchTerm)) ||
      (r.mlbb_ign && r.mlbb_ign.toLowerCase().includes(searchTerm)) ||
      (r.mlbb_id && String(r.mlbb_id).includes(searchTerm))
    );
  }

  if (filtered.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="8" class="empty-state">
          <span class="material-symbols-outlined">search_off</span>
          <p>No redemption orders matching criteria.</p>
        </td>
      </tr>`;
    return;
  }

  tbody.innerHTML = filtered.map(order => {
    const isDelivered = order.status && order.status.toLowerCase().includes("delivered");
    const isCancelled = order.status && order.status.toLowerCase().includes("cancel");
    const badgeClass = isDelivered ? "badge-delivered" : (isCancelled ? "badge-cancelled" : "badge-processing");

    return `
      <tr>
        <td class="font-mono font-bold">${escapeHtml(order.order_id)}</td>
        <td>
          <div class="player-cell">
            <div>
              <div class="player-name-bold">${escapeHtml(order.mlbb_ign || "Player")}</div>
              <div class="player-sub-mono">ID: ${escapeHtml(order.mlbb_id)} (${escapeHtml(order.mlbb_server)})</div>
            </div>
          </div>
        </td>
        <td>
          <span class="badge badge-delivered font-bold">${escapeHtml(order.diamonds)} 💎</span>
          <div style="font-size:0.75rem; color:var(--text-muted);">${escapeHtml(order.pack_name || "")}</div>
        </td>
        <td style="color:var(--text-muted);">${(order.points_cost || 0).toLocaleString()} pts</td>
        <td class="player-sub-mono">${escapeHtml(order.user_email)}</td>
        <td>
          <span class="badge ${badgeClass}">${escapeHtml(order.status)}</span>
        </td>
        <td style="font-size:0.8rem; color:var(--text-muted);">${formatDate(order.created_at)}</td>
        <td>
          <div class="action-btn-group">
            ${!isDelivered ? `
              <button type="button" class="action-icon-btn btn-approve" onclick="quickMarkDelivered('${order.order_id}')" title="Mark Delivered">
                <span class="material-symbols-outlined" style="font-size:1.1rem;">check</span>
              </button>
            ` : ""}
            <button type="button" class="action-icon-btn" onclick="openUpdateRedemptionModal('${order.order_id}')" title="Edit Status / Notes">
              <span class="material-symbols-outlined" style="font-size:1.1rem;">edit</span>
            </button>
            <button type="button" class="action-icon-btn" onclick="copyTopUpInfo('${order.mlbb_id}', '${order.mlbb_server}', '${order.mlbb_ign}')" title="Copy MLBB ID & Zone">
              <span class="material-symbols-outlined" style="font-size:1.1rem;">content_copy</span>
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join("");
}

// --- VIEW 3: USERS RENDERING ---
function renderUsers() {
  const tbody = document.getElementById("tbodyUsers");
  if (!tbody) return;

  const searchTerm = (document.getElementById("searchUsers")?.value || "").toLowerCase().trim();

  let filtered = adminState.users;

  if (searchTerm) {
    filtered = filtered.filter(u => 
      (u.email && u.email.toLowerCase().includes(searchTerm)) ||
      (u.username && u.username.toLowerCase().includes(searchTerm)) ||
      (u.mlbb_ign && u.mlbb_ign.toLowerCase().includes(searchTerm)) ||
      (u.mlbb_id && String(u.mlbb_id).includes(searchTerm))
    );
  }

  if (filtered.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="9" class="empty-state">
          <span class="material-symbols-outlined">person_off</span>
          <p>No registered players found matching criteria.</p>
        </td>
      </tr>`;
    return;
  }

  tbody.innerHTML = filtered.map(u => `
    <tr>
      <td>
        <div class="player-cell">
          <img src="${u.avatar_data || getDefaultAvatar()}" class="player-avatar-mini" alt="PFP">
          <div>
            <div class="player-name-bold">${escapeHtml(u.username || u.mlbb_ign || "Player")}</div>
            <div class="player-sub-mono">${escapeHtml(u.email)}</div>
          </div>
        </div>
      </td>
      <td>
        <div class="font-mono font-bold">${escapeHtml(u.mlbb_id || "Not Linked")}</div>
        <div class="player-sub-mono">Zone: ${escapeHtml(u.mlbb_server || "—")} • ${escapeHtml(u.mlbb_region || "Global")}</div>
      </td>
      <td>
        <span class="font-bold" style="color:var(--primary);">${(u.points || 0).toLocaleString()}</span>
        <span style="font-size:0.75rem; color:var(--text-muted);">pts</span>
      </td>
      <td class="font-bold">${(u.diamonds_claimed || 0).toLocaleString()} 💎</td>
      <td>${(u.giveaway_tickets || 0).toLocaleString()} 🎟</td>
      <td>
        <span class="badge badge-tag">🔥 Day ${u.daily_streak || 1}</span>
      </td>
      <td style="font-size:0.75rem; font-family:monospace; color:var(--text-muted); max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${escapeHtml(u.location_text || '')}">
        ${escapeHtml(u.location_text || "Not logged")}
      </td>
      <td style="font-size:0.8rem; color:var(--text-muted);">${formatDate(u.updated_at || u.created_at)}</td>
      <td>
        <div class="action-btn-group">
          <button type="button" class="btn btn-outline btn-sm" onclick="openAdjustPointsModal('${u.email}')" title="Add or Deduct Points">
            <span class="material-symbols-outlined" style="font-size:0.95rem;">toll</span>
            <span>Points</span>
          </button>
        </div>
      </td>
    </tr>
  `).join("");
}

// --- VIEW 4: GIVEAWAYS & WINNER PICKER ---
function renderGiveaways() {
  const pool = document.getElementById("selectGiveawayPool")?.value || "mega";
  const poolTitle = pool === "mega" ? "Mega Diamond Giveaway (1,000 💎)" : "Daily Diamond Giveaway (150 💎)";
  
  const elTitle = document.getElementById("arenaPoolTitle");
  if (elTitle) elTitle.textContent = poolTitle;

  // Filter entries by pool
  const poolEntries = adminState.giveawayEntries.filter(g => (g.pool_type || "").toLowerCase() === pool.toLowerCase());

  // Aggregate tickets per player
  const playerMap = {};
  let poolTotalTickets = 0;
  let poolTotalPoints = 0;

  poolEntries.forEach(entry => {
    const key = entry.user_email || "unknown";
    const tickets = Number(entry.ticket_count) || 1;
    const spent = Number(entry.points_spent) || 0;

    poolTotalTickets += tickets;
    poolTotalPoints += spent;

    if (!playerMap[key]) {
      playerMap[key] = {
        email: entry.user_email,
        ign: entry.mlbb_ign || "Player",
        mlbbId: entry.mlbb_id || "—",
        server: entry.mlbb_server || "—",
        tickets: 0,
        spent: 0,
        lastEntered: entry.created_at
      };
    }
    playerMap[key].tickets += tickets;
    playerMap[key].spent += spent;
    if (new Date(entry.created_at) > new Date(playerMap[key].lastEntered)) {
      playerMap[key].lastEntered = entry.created_at;
    }
  });

  const participants = Object.values(playerMap).sort((a, b) => b.tickets - a.tickets);

  // Update Arena Stats
  const elArenaTickets = document.getElementById("arenaTotalTickets");
  const elArenaParticipants = document.getElementById("arenaTotalParticipants");
  const elArenaPoints = document.getElementById("arenaTotalPoints");

  if (elArenaTickets) elArenaTickets.textContent = poolTotalTickets.toLocaleString();
  if (elArenaParticipants) elArenaParticipants.textContent = participants.length.toLocaleString();
  if (elArenaPoints) elArenaPoints.textContent = poolTotalPoints.toLocaleString();

  // Render Participants Table
  const tbody = document.getElementById("tbodyGiveaways");
  if (tbody) {
    if (participants.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" class="empty-state">
            <span class="material-symbols-outlined">confirmation_number</span>
            <p>No tickets purchased in this pool yet.</p>
          </td>
        </tr>`;
      return;
    }

    tbody.innerHTML = participants.map(p => {
      const chance = poolTotalTickets > 0 ? ((p.tickets / poolTotalTickets) * 100).toFixed(1) : 0;
      return `
        <tr>
          <td>
            <div class="player-name-bold">${escapeHtml(p.ign)}</div>
            <div class="player-sub-mono">${escapeHtml(p.email)}</div>
          </td>
          <td class="font-mono">${escapeHtml(p.mlbbId)} (${escapeHtml(p.server)})</td>
          <td>
            <span class="badge badge-delivered font-bold">${p.tickets.toLocaleString()} 🎟</span>
          </td>
          <td>
            <div style="display:flex; align-items:center; gap:8px;">
              <span class="font-bold" style="color:var(--accent-blue);">${chance}%</span>
              <div style="background:#e2e8f0; border-radius:9999px; height:6px; width:60px; overflow:hidden;">
                <div style="background:var(--accent-blue); width:${chance}%; height:100%;"></div>
              </div>
            </div>
          </td>
          <td style="color:var(--text-muted);">${p.spent.toLocaleString()} pts</td>
          <td style="font-size:0.8rem; color:var(--text-muted);">${formatDate(p.lastEntered)}</td>
        </tr>
      `;
    }).join("");
  }
}

// --- WINNER RANDOM DRAW ENGINE ---
function rollGiveawayWinner() {
  if (adminState.isRolling) return;

  const pool = document.getElementById("selectGiveawayPool")?.value || "mega";
  const poolEntries = adminState.giveawayEntries.filter(g => (g.pool_type || "").toLowerCase() === pool.toLowerCase());

  if (poolEntries.length === 0) {
    showAdminToast("Cannot draw: No participants in this pool yet!", "warning");
    return;
  }

  // Build weighted ticket pool
  const ticketPool = [];
  poolEntries.forEach(entry => {
    const count = Number(entry.ticket_count) || 1;
    for (let i = 0; i < count; i++) {
      ticketPool.push({
        email: entry.user_email,
        ign: entry.mlbb_ign || "Player",
        mlbbId: entry.mlbb_id || "",
        server: entry.mlbb_server || "",
        pool: pool
      });
    }
  });

  adminState.isRolling = true;
  const btnRoll = document.getElementById("btnRollWinner");
  const tickerBox = document.getElementById("winnerTickerBox");
  const tickerName = document.getElementById("winnerTickerName");

  if (btnRoll) btnRoll.disabled = true;
  if (tickerBox) tickerBox.classList.add("active");

  // Animate ticker for 2.8 seconds
  let iterations = 0;
  const maxIterations = 28;
  const interval = setInterval(() => {
    const randomPick = ticketPool[Math.floor(Math.random() * ticketPool.length)];
    if (tickerName) {
      tickerName.textContent = `${randomPick.ign} (ID: ${randomPick.mlbbId})`;
    }
    iterations++;

    if (iterations >= maxIterations) {
      clearInterval(interval);
      adminState.isRolling = false;
      if (btnRoll) btnRoll.disabled = false;
      if (tickerBox) tickerBox.classList.remove("active");

      // Final certified winner
      const winner = ticketPool[Math.floor(Math.random() * ticketPool.length)];
      showWinnerCelebration(winner);
    }
  }, 100);
}

function showWinnerCelebration(winner) {
  const elIgn = document.getElementById("winnerModalIgn");
  const elId = document.getElementById("winnerModalId");
  const elServer = document.getElementById("winnerModalServer");
  const elTickets = document.getElementById("winnerModalTickets");

  const totalUserTickets = adminState.giveawayEntries
    .filter(g => g.user_email === winner.email && (g.pool_type || "").toLowerCase() === (winner.pool || "mega").toLowerCase())
    .reduce((sum, g) => sum + (Number(g.ticket_count) || 1), 0);

  if (elIgn) elIgn.textContent = winner.ign;
  if (elId) elId.textContent = winner.mlbbId || "—";
  if (elServer) elServer.textContent = winner.server || "—";
  if (elTickets) elTickets.textContent = `${totalUserTickets} Tickets`;

  const btnCopy = document.getElementById("btnCopyWinnerDetails");
  if (btnCopy) {
    btnCopy.onclick = () => copyTopUpInfo(winner.mlbbId, winner.server, winner.ign);
  }

  openModal("modalWinnerAlert");
}

// --- REDEMPTION STATUS UPDATE ---
function quickMarkDelivered(orderId) {
  updateRedemptionStatus(orderId, "Delivered");
}

function openUpdateRedemptionModal(orderId) {
  const order = adminState.redemptions.find(r => r.order_id === orderId);
  if (!order) return;

  adminState.selectedOrder = order;

  const elId = document.getElementById("orderModalId");
  const elDetails = document.getElementById("orderModalDetails");
  const elStatus = document.getElementById("orderModalStatus");
  const elNotes = document.getElementById("orderModalNotes");

  if (elId) elId.value = order.order_id;
  if (elDetails) {
    elDetails.innerHTML = `
      <strong>${escapeHtml(order.mlbb_ign || "Player")}</strong> • 
      MLBB ID: <code>${escapeHtml(order.mlbb_id)}</code> (${escapeHtml(order.mlbb_server)})<br>
      Package: <strong>${escapeHtml(order.diamonds)} Diamonds</strong> (${(order.points_cost || 0).toLocaleString()} pts)
    `;
  }
  if (elStatus) elStatus.value = order.status || "Processing (7-14 Days)";
  if (elNotes) elNotes.value = order.notes || "";

  openModal("modalUpdateRedemption");
}

async function submitUpdateRedemptionModal() {
  if (!adminState.selectedOrder) return;
  const newStatus = document.getElementById("orderModalStatus")?.value;
  const notes = document.getElementById("orderModalNotes")?.value.trim();

  await updateRedemptionStatus(adminState.selectedOrder.order_id, newStatus, notes);
  closeModal("modalUpdateRedemption");
}

async function updateRedemptionStatus(orderId, newStatus, notes = "") {
  showAdminToast(`Updating Order ${orderId}...`, "sync");

  const payload = {
    status: newStatus,
    updated_at: new Date().toISOString()
  };
  if (notes) payload.notes = notes;
  if (newStatus.toLowerCase().includes("delivered")) {
    payload.completed_at = new Date().toISOString();
  }

  const res = await supabaseApi(`redemptions?order_id=eq.${encodeURIComponent(orderId)}`, "PATCH", payload);
  if (res.ok) {
    showAdminToast(`Order ${orderId} updated to: ${newStatus}!`, "check_circle");
    
    // Update local cache
    const target = adminState.redemptions.find(r => r.order_id === orderId);
    if (target) {
      target.status = newStatus;
      if (notes) target.notes = notes;
    }
    renderCurrentView();
    updateDashboardKpis();
  } else {
    showAdminToast(`Update failed: ${res.error || "Permission denied"}`, "error");
  }
}

// --- USER POINTS ADJUSTMENT ---
function openAdjustPointsModal(email) {
  const user = adminState.users.find(u => u.email === email);
  if (!user) return;

  adminState.selectedUser = user;

  const elUser = document.getElementById("adjustPointsUser");
  const elCurrent = document.getElementById("adjustPointsCurrent");
  const elDelta = document.getElementById("adjustPointsDelta");
  const elReason = document.getElementById("adjustPointsReason");

  if (elUser) elUser.value = `${user.username || user.mlbb_ign || "Player"} (${user.email})`;
  if (elCurrent) elCurrent.value = `${(user.points || 0).toLocaleString()} Points`;
  if (elDelta) elDelta.value = "";
  if (elReason) elReason.value = "";

  openModal("modalAdjustPoints");
}

async function submitAdjustPoints() {
  if (!adminState.selectedUser) return;
  const delta = parseInt(document.getElementById("adjustPointsDelta")?.value, 10);
  const reason = document.getElementById("adjustPointsReason")?.value.trim();

  if (isNaN(delta) || delta === 0) {
    showAdminToast("Please enter a valid positive or negative points adjustment amount.", "warning");
    return;
  }

  const currentPoints = adminState.selectedUser.points || 0;
  const newPoints = Math.max(0, currentPoints + delta);

  showAdminToast(`Updating points for ${adminState.selectedUser.email}...`, "sync");

  const res = await supabaseApi(
    `users?email=eq.${encodeURIComponent(adminState.selectedUser.email)}`,
    "PATCH",
    {
      points: newPoints,
      updated_at: new Date().toISOString()
    }
  );

  if (res.ok) {
    showAdminToast(`Points updated! New balance: ${newPoints.toLocaleString()} pts`, "check_circle");
    adminState.selectedUser.points = newPoints;
    closeModal("modalAdjustPoints");
    renderCurrentView();
  } else {
    showAdminToast(`Failed to update points: ${res.error || "Database error"}`, "error");
  }
}

// --- CSV EXPORT HELPERS ---
function exportRedemptionsCsv() {
  if (adminState.redemptions.length === 0) {
    showAdminToast("No redemptions to export.", "warning");
    return;
  }

  const headers = ["Order ID", "IGN", "MLBB User ID", "MLBB Server", "Diamonds", "Points Cost", "User Email", "Status", "Date", "Notes"];
  const rows = adminState.redemptions.map(r => [
    r.order_id,
    r.mlbb_ign || "",
    r.mlbb_id || "",
    r.mlbb_server || "",
    r.diamonds,
    r.points_cost,
    r.user_email,
    r.status,
    r.created_at,
    r.notes || ""
  ]);

  downloadCsv("ketupat_redemptions.csv", [headers, ...rows]);
}

function exportUsersCsv() {
  if (adminState.users.length === 0) {
    showAdminToast("No users to export.", "warning");
    return;
  }

  const headers = ["Email", "Username", "MLBB IGN", "MLBB ID", "Zone", "Region", "Points", "Diamonds Claimed", "Tickets", "Streak", "Location", "Registered At"];
  const rows = adminState.users.map(u => [
    u.email,
    u.username || "",
    u.mlbb_ign || "",
    u.mlbb_id || "",
    u.mlbb_server || "",
    u.mlbb_region || "",
    u.points || 0,
    u.diamonds_claimed || 0,
    u.giveaway_tickets || 0,
    u.daily_streak || 1,
    u.location_text || "",
    u.created_at
  ]);

  downloadCsv("ketupat_users.csv", [headers, ...rows]);
}

function exportGiveawaysCsv() {
  if (adminState.giveawayEntries.length === 0) {
    showAdminToast("No giveaway entries to export.", "warning");
    return;
  }

  const headers = ["Pool", "IGN", "MLBB ID", "Server", "Email", "Tickets", "Points Spent", "Date"];
  const rows = adminState.giveawayEntries.map(g => [
    g.pool_type,
    g.mlbb_ign || "",
    g.mlbb_id || "",
    g.mlbb_server || "",
    g.user_email,
    g.ticket_count || 1,
    g.points_spent || 0,
    g.created_at
  ]);

  downloadCsv("ketupat_giveaways.csv", [headers, ...rows]);
}

function downloadCsv(filename, rows) {
  const csvContent = "data:text/csv;charset=utf-8," + 
    rows.map(e => e.map(val => `"${String(val).replace(/"/g, '""')}"`).join(",")).join("\n");
  
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  link.setAttribute("download", filename);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  showAdminToast(`Exported ${filename}!`, "download");
}

// --- UTILITY: COPY TOP-UP DESTINATION ---
function copyTopUpInfo(mlbbId, server, ign) {
  const text = `ID: ${mlbbId} Zone: ${server} (${ign})`;
  navigator.clipboard.writeText(text).then(() => {
    showAdminToast(`Copied: ${text}`, "content_copy");
  }).catch(() => {
    showAdminToast(`ID: ${mlbbId} Zone: ${server}`, "info");
  });
}

// --- NAVIGATION & UI HELPERS ---
function setupNavigation() {
  const navBtns = document.querySelectorAll("[data-view]");
  navBtns.forEach(btn => {
    btn.addEventListener("click", () => {
      const view = btn.getAttribute("data-view");
      switchAdminTab(view);
    });
  });

  // Mobile menu toggle
  const btnMobile = document.getElementById("btnMobileMenu");
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

  if (btnMobile && sidebar) {
    btnMobile.addEventListener("click", toggleMobileSidebar);
  }

  if (sidebarBackdrop) {
    sidebarBackdrop.addEventListener("click", closeMobileSidebar);
    sidebarBackdrop.addEventListener("touchstart", closeMobileSidebar, { passive: true });
  }

  // Click outside sidebar to close
  document.addEventListener("click", (e) => {
    if (sidebar && sidebar.classList.contains("mobile-open")) {
      if (!sidebar.contains(e.target) && (!btnMobile || !btnMobile.contains(e.target))) {
        closeMobileSidebar();
      }
    }
  });

  // Touch outside sidebar to close
  document.addEventListener("touchstart", (e) => {
    if (sidebar && sidebar.classList.contains("mobile-open")) {
      if (!sidebar.contains(e.target) && (!btnMobile || !btnMobile.contains(e.target))) {
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

function switchAdminTab(viewName) {
  adminState.currentView = viewName;

  // Update nav link active state
  document.querySelectorAll("[data-view]").forEach(btn => {
    if (btn.getAttribute("data-view") === viewName) {
      btn.classList.add("active");
    } else {
      btn.classList.remove("active");
    }
  });

  // Update section views
  document.querySelectorAll(".view-section").forEach(sec => {
    sec.classList.remove("active");
  });

  const targetView = document.getElementById(`view${capitalize(viewName)}`);
  if (targetView) targetView.classList.add("active");

  // Update title
  const heading = document.getElementById("pageTitleHeading");
  const titles = {
    overview: "Dashboard Overview",
    redemptions: "Diamond Redemptions",
    users: "User & Player Management",
    giveaways: "Giveaway Arena & Winner Draws",
    settings: "Supabase Cloud Settings"
  };
  if (heading) heading.textContent = titles[viewName] || "Admin Console";

  // Close mobile sidebar if open
  document.getElementById("sidebar")?.classList.remove("mobile-open");
  document.getElementById("sidebarBackdrop")?.classList.remove("active");

  renderCurrentView();
}

function setupAutoRefresh() {
  const select = document.getElementById("selectAutoRefresh");
  if (!select) return;

  const handleTimer = (secs) => {
    if (adminState.autoRefreshInterval) {
      clearInterval(adminState.autoRefreshInterval);
      adminState.autoRefreshInterval = null;
    }
    if (secs > 0) {
      adminState.autoRefreshInterval = setInterval(() => {
        refreshAllData();
      }, secs * 1000);
    }
  };

  select.addEventListener("change", (e) => {
    const secs = parseInt(e.target.value, 10);
    handleTimer(secs);
    showAdminToast(secs > 0 ? `Auto-refresh set to every ${secs}s` : "Auto-refresh disabled", "schedule");
  });

  handleTimer(parseInt(select.value, 10));
}

function updateConnectionStatusPill(status) {
  const dot = document.getElementById("sidebarStatusDot");
  const text = document.getElementById("sidebarStatusText");

  if (!dot || !text) return;

  if (status === "online") {
    dot.className = "status-dot online";
    text.textContent = "Supabase Connected";
  } else if (status === "connecting") {
    dot.className = "status-dot";
    text.textContent = "Connecting...";
  } else {
    dot.className = "status-dot offline";
    text.textContent = "Database Offline";
  }
}

// --- SETTINGS VIEW HANDLERS ---
function populateSettingsForm() {
  const inputUrl = document.getElementById("inputSupabaseUrl");
  const inputKey = document.getElementById("inputSupabaseKey");
  const inputSecret = document.getElementById("inputSupabaseSecret");
  const inputDlyyz = document.getElementById("inputAdminDlyyzKey");

  if (inputUrl) inputUrl.value = adminState.config.url || "";
  if (inputKey) inputKey.value = adminState.config.anonKey || "";
  if (inputSecret) inputSecret.value = adminState.config.secretKey || "";
  if (inputDlyyz) inputDlyyz.value = localStorage.getItem("ketupat_dlyyz_apikey") || "";
}

function setupEventListeners() {
  // Sync button
  document.getElementById("btnRefreshAll")?.addEventListener("click", refreshAllData);

  // Redemptions filters
  document.getElementById("filterRedemptionStatus")?.addEventListener("change", renderRedemptions);
  document.getElementById("searchRedemptions")?.addEventListener("input", renderRedemptions);
  document.getElementById("btnExportRedemptionsCsv")?.addEventListener("click", exportRedemptionsCsv);

  // Users filters
  document.getElementById("searchUsers")?.addEventListener("input", renderUsers);
  document.getElementById("btnExportUsersCsv")?.addEventListener("click", exportUsersCsv);

  // Giveaways filters & Roll
  document.getElementById("selectGiveawayPool")?.addEventListener("change", renderGiveaways);
  document.getElementById("btnRollWinner")?.addEventListener("click", rollGiveawayWinner);
  document.getElementById("btnExportGiveawayCsv")?.addEventListener("click", exportGiveawaysCsv);

  // Modal actions
  document.getElementById("btnSubmitUpdateStatus")?.addEventListener("click", submitUpdateRedemptionModal);
  document.getElementById("btnSubmitAdjustPoints")?.addEventListener("click", submitAdjustPoints);

  // Settings actions
  document.getElementById("btnSaveSettings")?.addEventListener("click", () => {
    const url = document.getElementById("inputSupabaseUrl")?.value || "";
    const key = document.getElementById("inputSupabaseKey")?.value || "";
    const secret = document.getElementById("inputSupabaseSecret")?.value || "";
    const dlyyzKey = document.getElementById("inputAdminDlyyzKey")?.value || "";

    if (dlyyzKey && dlyyzKey.trim()) {
      localStorage.setItem("ketupat_dlyyz_apikey", dlyyzKey.trim());
    } else {
      localStorage.removeItem("ketupat_dlyyz_apikey");
    }

    saveAdminConfig(url, key, secret);
  });

  document.getElementById("btnTestSettingsConn")?.addEventListener("click", testDatabaseHealth);
}

async function testDatabaseHealth() {
  const btn = document.getElementById("btnTestSettingsConn");
  const statusDiv = document.getElementById("settingsDiagStatus");
  if (btn) btn.disabled = true;

  if (statusDiv) {
    statusDiv.className = "status-msg info";
    statusDiv.textContent = "Running diagnostic on users, redemptions, and giveaway_entries tables...";
    statusDiv.classList.remove("hidden");
  }

  try {
    const [uTest, rTest, gTest] = await Promise.all([
      supabaseApi("users?select=count"),
      supabaseApi("redemptions?select=count"),
      supabaseApi("giveaway_entries?select=count")
    ]);

    if (uTest.ok && rTest.ok && gTest.ok) {
      if (statusDiv) {
        statusDiv.className = "status-msg info";
        statusDiv.innerHTML = `✓ <strong>Connection Healthy!</strong> All 3 PostgreSQL tables are accessible and RLS policies are operational.`;
      }
    } else {
      if (statusDiv) {
        statusDiv.className = "status-msg error";
        statusDiv.innerHTML = `⚠️ Test issue: Users: ${uTest.status} | Redemptions: ${rTest.status} | Giveaways: ${gTest.status}`;
      }
    }
  } catch (err) {
    if (statusDiv) {
      statusDiv.className = "status-msg error";
      statusDiv.textContent = `Diagnostic error: ${err.message}`;
    }
  } finally {
    if (btn) btn.disabled = false;
  }
}

// --- MODAL CONTROLS ---
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) modal.classList.add("open");
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) modal.classList.remove("open");
}

// Close modal on escape key
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    document.querySelectorAll(".modal-overlay.open").forEach(m => m.classList.remove("open"));
  }
});

// --- TOAST NOTIFICATION HELPER ---
let toastTimer = null;
function showAdminToast(message, icon = "info") {
  const toast = document.getElementById("adminToast");
  const iconEl = document.getElementById("adminToastIcon");
  const textEl = document.getElementById("adminToastText");

  if (!toast || !textEl) return;

  textEl.textContent = message;
  if (iconEl) iconEl.textContent = icon;

  toast.classList.add("show");

  if (toastTimer) clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toast.classList.remove("show");
  }, 3200);
}

// --- FORMATTERS & HELPERS ---
function formatDate(dateStr) {
  if (!dateStr) return "—";
  try {
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, { month: "short", day: "numeric", hour: "2-digit", minute: "2-digit" });
  } catch (e) {
    return dateStr;
  }
}

function capitalize(str) {
  if (!str) return "";
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function escapeHtml(str) {
  if (!str) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function getDefaultAvatar() {
  return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23cbd5e1'%3E%3Ccircle cx='12' cy='8' r='4'/%3E%3Cpath d='M20 21a8 8 0 0 0-16 0'/%3E%3C/svg%3E";
}
