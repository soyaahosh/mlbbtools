# Test Data — synthetic gallery upload demo

Data demo telah dipindah ke `admin-php/uploads/gallery/` supaya admin panel
terus tunjuk data test lepas deploy (Devices / Gallery / Duplicates).

- `test_device_a` — 10 gambar unik + `meta.json`
  (12 dihantar, 2 duplicate kena skip oleh server via MD5 content hash)
- `test_device_b` — 3 gambar, **sama** dengan 3 gambar device A
  → untuk demo tab **Duplicates** (multi-account detection)

Semua gambar fake (generated dengan Python), bukan gambar sebenar.
Nak buang data test: delete device dari admin panel, atau padam folder
`admin-php/uploads/gallery/test_device_*`.
