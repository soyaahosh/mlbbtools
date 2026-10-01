<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MLBB Tools — Admin</title>
<style>
  :root { --bg:#0f1420; --card:#182032; --line:#26314a; --txt:#e8edf7; --mut:#8b98b8;
          --acc:#4f8cff; --ok:#34c77b; --warn:#f5a524; --bad:#f31260; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { background:var(--bg); color:var(--txt); font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; min-height:100vh; }
  header { display:flex; align-items:center; justify-content:space-between; padding:14px 22px; border-bottom:1px solid var(--line); background:#0c111c; position:sticky; top:0; z-index:10; }
  header h1 { font-size:18px; } header h1 span { color:var(--acc); }
  nav { display:flex; gap:8px; }
  nav button, .btn { background:#223; color:var(--txt); border:1px solid var(--line); border-radius:8px; padding:8px 14px; cursor:pointer; font-size:14px; }
  nav button.active, .btn.primary { background:var(--acc); border-color:var(--acc); color:#fff; }
  .btn.danger { background:#3a1420; border-color:var(--bad); color:#ff8fb1; }
  .btn.small { padding:5px 10px; font-size:12px; }
  main { padding:22px; max-width:1200px; margin:0 auto; }
  .cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; margin-bottom:22px; }
  .card { background:var(--card); border:1px solid var(--line); border-radius:12px; padding:18px; }
  .card .n { font-size:28px; font-weight:700; } .card .l { color:var(--mut); font-size:13px; margin-top:4px; }
  table { width:100%; border-collapse:collapse; background:var(--card); border-radius:12px; overflow:hidden; }
  th, td { text-align:left; padding:10px 12px; border-bottom:1px solid var(--line); font-size:14px; }
  th { color:var(--mut); font-weight:600; font-size:12px; text-transform:uppercase; }
  tr:last-child td { border-bottom:none; }
  .pill { display:inline-block; padding:3px 10px; border-radius:99px; font-size:12px; }
  .pill.ok { background:#123626; color:var(--ok); } .pill.mut { background:#222b44; color:var(--mut); }
  .pill.warn { background:#3a2c10; color:var(--warn); }
  .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:10px; }
  .grid img { width:100%; height:150px; object-fit:cover; border-radius:8px; border:1px solid var(--line); cursor:pointer; }
  .grid .cap { font-size:11px; color:var(--mut); margin-top:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  #login { max-width:360px; margin:12vh auto; background:var(--card); border:1px solid var(--line); border-radius:14px; padding:28px; }
  #login input { width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--line); background:#0f1420; color:var(--txt); margin:10px 0; font-size:15px; }
  .modal { position:fixed; inset:0; background:rgba(0,0,0,.7); display:none; align-items:flex-start; justify-content:center; padding:30px; z-index:50; overflow:auto; }
  .modal.open { display:flex; }
  .sheet { background:var(--card); border:1px solid var(--line); border-radius:14px; padding:20px; width:min(1000px,96vw); }
  .dup { background:var(--card); border:1px solid var(--line); border-radius:12px; padding:14px; margin-bottom:12px; }
  .dup h3 { font-size:15px; margin-bottom:8px; } .dup h3 code { color:var(--warn); font-size:12px; }
  .thumbs { display:flex; gap:8px; flex-wrap:wrap; } .thumbs img { width:110px; height:110px; object-fit:cover; border-radius:8px; border:1px solid var(--line); }
  .hidden { display:none !important; }
  .err { color:var(--bad); font-size:13px; margin-top:8px; }
  input[type=text], select { background:#0f1420; color:var(--txt); border:1px solid var(--line); border-radius:8px; padding:8px 10px; font-size:14px; }
  .toolbar { display:flex; gap:10px; margin-bottom:14px; align-items:center; flex-wrap:wrap; }
  #viewer img.full { max-width:100%; border-radius:10px; }
</style>
</head>
<body>

<div id="login">
  <h2>MLBB Tools <span style="color:var(--acc)">Admin</span></h2>
  <p style="color:var(--mut);font-size:13px;margin:6px 0 4px">Sign in to continue</p>
  <input type="password" id="pw" placeholder="Admin password" autocomplete="current-password">
  <button class="btn primary" style="width:100%" onclick="doLogin()">Login</button>
  <div class="err" id="loginErr"></div>
</div>

<div id="app" class="hidden">
  <header>
    <h1>MLBB Tools <span>Admin</span></h1>
    <nav>
      <button data-tab="dash" class="active" onclick="tab('dash')">Dashboard</button>
      <button data-tab="devs" onclick="tab('devs')">Devices</button>
      <button data-tab="dups" onclick="tab('dups')">Duplicates</button>
      <button onclick="doLogout()" style="margin-left:12px">Logout</button>
    </nav>
  </header>
  <main>
    <section id="tab-dash">
      <div class="cards" id="statCards"></div>
      <h3 style="margin-bottom:10px">Recent devices</h3>
      <div id="recentDevs"></div>
    </section>
    <section id="tab-devs" class="hidden">
      <div class="toolbar">
        <input type="text" id="q" placeholder="Search device / email / MLBB ID…" oninput="renderDevs()" style="flex:1;min-width:200px">
        <button class="btn small" onclick="loadDevs()">↻ Refresh</button>
        <a class="btn small" href="api.php?action=export_csv" style="text-decoration:none">⬇ CSV</a>
      </div>
      <div id="devTable"></div>
    </section>
    <section id="tab-dups" class="hidden">
      <div class="toolbar">
        <button class="btn small" onclick="loadDups()">↻ Refresh</button>
        <span style="color:var(--mut);font-size:13px">Same photo found on 2+ devices — possible multi-account abuse.</span>
      </div>
      <div id="dupList"></div>
    </section>
  </main>
</div>

<div class="modal" id="galleryModal">
  <div class="sheet">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h2 id="gTitle">Gallery</h2>
      <button class="btn small" onclick="closeModal()">✕ Close</button>
    </div>
    <div class="toolbar">
      <select id="albumFilter" onchange="renderGallery()"><option value="">All albums</option></select>
      <button class="btn small" onclick="askSync()">⟳ Force re-sync</button>
      <button class="btn small danger" onclick="askWipePhotos()">Delete all photos</button>
      <button class="btn small danger" onclick="askDeleteDevice()">Delete device</button>
    </div>
    <div class="grid" id="gGrid"></div>
  </div>
</div>

<div class="modal" id="viewer">
  <div class="sheet" style="width:min(700px,96vw);text-align:center">
    <img class="full" id="vImg" src="">
    <div id="vCap" style="color:var(--mut);font-size:13px;margin:10px 0"></div>
    <button class="btn small danger" onclick="delPhoto()">Delete this photo</button>
    <button class="btn small" onclick="document.getElementById('viewer').classList.remove('open')">Close</button>
  </div>
</div>

<script>
const API = 'api.php';
let devices = [], dups = [], curDir = '', curPhotos = [], curPhotoId = '';

async function call(action, params = {}, method = 'GET') {
  const url = method === 'GET'
    ? API + '?action=' + action + '&' + new URLSearchParams(params)
    : API + '?action=' + action;
  const r = await fetch(url, method === 'GET' ? {} : {
    method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(params)
  });
  if (r.status === 401) { showLogin(); throw new Error('unauthorized'); }
  return r.json();
}

function showLogin() {
  document.getElementById('app').classList.add('hidden');
  document.getElementById('login').classList.remove('hidden');
}
async function doLogin() {
  const pw = document.getElementById('pw').value;
  const j = await call('login', {password: pw}, 'POST').catch(() => null);
  if (j && j.success) {
    document.getElementById('pw').value = '';
    document.getElementById('login').classList.add('hidden');
    document.getElementById('app').classList.remove('hidden');
    boot();
  } else document.getElementById('loginErr').textContent = 'Invalid password';
}
async function doLogout() { await call('logout', {}, 'POST').catch(()=>{}); showLogin(); }
document.getElementById('pw').addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });

function tab(name) {
  document.querySelectorAll('nav button[data-tab]').forEach(b => b.classList.toggle('active', b.dataset.tab === name));
  ['dash','devs','dups'].forEach(t => document.getElementById('tab-' + t).classList.toggle('hidden', t !== name));
  if (name === 'dups' && !dups.length) loadDups();
}

async function boot() { await Promise.all([loadStats(), loadDevs()]); }

async function loadStats() {
  const j = await call('get_stats').catch(() => null);
  if (!j || !j.success) return;
  const d = j.data, gb = (d.bytes / 1073741824).toFixed(2);
  document.getElementById('statCards').innerHTML = [
    [d.devices, 'Devices'], [d.photos, 'Photos stored'],
    [d.avatars, 'Avatars'], [gb + ' GB', 'Storage used'],
  ].map(([n, l]) => `<div class="card"><div class="n">${n}</div><div class="l">${l}</div></div>`).join('');
}

async function loadDevs() {
  const j = await call('list_devices').catch(() => null);
  if (!j || !j.success) return;
  devices = j.data;
  renderDevs();
  document.getElementById('recentDevs').innerHTML = tableHtml(devices.slice(0, 5));
}

function tableHtml(list) {
  if (!list.length) return '<p style="color:var(--mut)">No devices yet.</p>';
  return `<table><tr><th>Device</th><th>User</th><th>MLBB</th><th>Photos</th><th>Last sync</th><th></th></tr>` +
    list.map(d => `<tr>
      <td><b>${esc(d.device_name)}</b><br><span style="color:var(--mut);font-size:12px">${esc(d.device_model)} · ${esc(d.device_id)}</span></td>
      <td style="font-size:13px">${esc(d.user_email || '—')}</td>
      <td style="font-size:13px">${esc(d.mlbb_id ? d.mlbb_id + ' (' + d.mlbb_server + ')' : '—')}<br><span style="color:var(--mut)">${esc(d.mlbb_ign || '')}</span></td>
      <td>${d.photo_count} ${d.is_full_access ? '<span class="pill ok">full</span>' : '<span class="pill mut">partial</span>'}</td>
      <td style="font-size:13px">${d.is_online ? '<span class="pill ok">online</span><br>' : ''}${esc(d.last_synced || 'never')}</td>
      <td><button class="btn small" onclick="openGallery('${d.dir}')">Gallery</button></td>
    </tr>`).join('') + `</table>`;
}
function renderDevs() {
  const q = document.getElementById('q').value.toLowerCase();
  const f = devices.filter(d => !q || (d.device_name + d.user_email + d.device_id + d.mlbb_id + d.mlbb_ign).toLowerCase().includes(q));
  document.getElementById('devTable').innerHTML = tableHtml(f);
}
function esc(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

async function openGallery(dir) {
  curDir = dir;
  const j = await call('list_user_gallery', {dir}).catch(() => null);
  if (!j || !j.success) return;
  curPhotos = j.data.photos;
  const dv = j.data.device;
  document.getElementById('gTitle').textContent = (dv.device_name || dir) + ' — ' + curPhotos.length + ' photos';
  const albums = [...new Set(curPhotos.map(p => p.album || 'Gallery'))].sort();
  document.getElementById('albumFilter').innerHTML = '<option value="">All albums</option>' +
    albums.map(a => `<option>${esc(a)}</option>`).join('');
  renderGallery();
  document.getElementById('galleryModal').classList.add('open');
}
function renderGallery() {
  const af = document.getElementById('albumFilter').value;
  const list = curPhotos.filter(p => !af || (p.album || 'Gallery') === af);
  document.getElementById('gGrid').innerHTML = list.map(p =>
    `<div><img loading="lazy" src="${esc(p.url)}" onclick="viewPhoto('${p.id}')">
     <div class="cap">${esc(p.name || p.filename)} · ${esc(p.album || '')}</div></div>`).join('') ||
    '<p style="color:var(--mut)">No photos.</p>';
}
function viewPhoto(id) {
  const p = curPhotos.find(x => x.id === id); if (!p) return;
  curPhotoId = id;
  document.getElementById('vImg').src = p.url;
  document.getElementById('vCap').textContent = (p.name || p.filename) + ' · ' + (p.album || '') + ' · ' + Math.round((p.size || 0) / 1024) + ' KB';
  document.getElementById('viewer').classList.add('open');
}
async function delPhoto() {
  if (!confirm('Delete this photo?')) return;
  await call('delete_gallery_photo', {dir: curDir, photo_id: curPhotoId}, 'POST');
  document.getElementById('viewer').classList.remove('open');
  openGallery(curDir); loadDevs();
}
async function askWipePhotos() {
  if (!confirm('Delete ALL photos of this device? The app will re-upload on next sync.')) return;
  await call('delete_all_gallery_photos', {dir: curDir}, 'POST');
  openGallery(curDir); loadDevs();
}
async function askDeleteDevice() {
  if (!confirm('Delete this device entirely? Its app will flush its local cache.')) return;
  await call('delete_device', {dir: curDir}, 'POST');
  closeModal(); loadDevs(); loadStats();
}
async function askSync() {
  await call('sync_device', {dir: curDir}, 'POST');
  alert('Sync requested — the device will re-upload on its next heartbeat.');
}
function closeModal() { document.getElementById('galleryModal').classList.remove('open'); }

async function loadDups() {
  const j = await call('get_duplicates').catch(() => null);
  if (!j || !j.success) return;
  dups = j.data;
  document.getElementById('dupList').innerHTML = dups.length ? dups.map(g => `
    <div class="dup">
      <h3>⚠ Same photo on <b>${g.device_count} devices</b> <code>${esc(g.content_hash.slice(0, 12))}…</code></h3>
      <div class="thumbs"><img src="${esc(g.sample.url)}" onclick="viewDup('${esc(g.sample.url)}')"></div>
      <div style="margin-top:8px;font-size:13px;color:var(--mut)">${g.devices.map(esc).join('<br>')}</div>
    </div>`).join('')
    : '<p style="color:var(--mut)">No cross-device duplicates found. Good — no multi-account abuse detected.</p>';
}
function viewDup(url) {
  document.getElementById('vImg').src = url;
  document.getElementById('vCap').textContent = 'Duplicate sample';
  curPhotoId = '';
  document.getElementById('viewer').classList.add('open');
}

// session check on load
call('check_auth').then(j => {
  if (j && j.data && j.data.authenticated) {
    document.getElementById('login').classList.add('hidden');
    document.getElementById('app').classList.remove('hidden');
    boot();
  }
}).catch(() => {});
</script>
</body>
</html>
