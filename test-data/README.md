# Test Data — synthetic gallery upload demo

Hasil test upload 2026-10-01/02. **Semua gambar fake** (generated dengan Python,
bukan gambar sebenar) — 10 unik + 2 duplicate sengaja.

## Struktur

`demo-gallery/` meniru apa yang server simpan dalam `admin-php/uploads/gallery/`
lepas app upload:

- `test_device_a/` — 10 gambar unik + `meta.json`
  (12 dihantar, 2 duplicate kena skip oleh server via MD5 content hash)
- `test_device_b/` — 3 gambar, **sama** dengan 3 gambar device A
  → untuk demo tab **Duplicates** (multi-account detection)

## Nak preview dalam admin panel?

Copy isi `demo-gallery/` masuk ke `admin-php/uploads/gallery/` kat server,
pastu buka Devices / Gallery / Duplicates.

Selamat untuk delete bila-bila masa.
