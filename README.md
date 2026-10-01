# MLBB Tools

Fresh rebuild — admin panel + Android app for the MLBB Diamond Giveaway.

## Layout

```
admin-php/        Admin panel + API (PHP, no database — file storage)
  api.php         API endpoints (app + admin)
  index.php       Admin web UI (login → dashboard, devices, gallery, duplicates)
  config.php      Settings — SET YOUR ADMIN PASSWORD here (or env MLBB_ADMIN_PASSWORD)
  uploads/        Runtime data (created automatically, per-device gallery folders)
MLBB-Diamond-Giveaway.apk   Latest Android build (patched uploader)
```

## App → server API

The APK posts to `https://<host>/admin-php/api.php?action=upload_gallery`
with the device's full gallery (base64 JPEGs). The server stores per-device
folders under `admin-php/uploads/gallery/<device>/` with `meta.json`.

## Admin login

1. On the server, set env `MLBB_ADMIN_PASSWORD="something-strong"`, **or**
   generate a bcrypt hash and put it in `admin-php/config.php`:
   `php -r "echo password_hash('your-password', PASSWORD_BCRYPT), PHP_EOL;"`
2. Open `https://<host>/admin-php/` and sign in.

## Key feature — Duplicates tab

Photos are hashed (MD5) on upload. The **Duplicates** tab lists any photo
found on 2+ different devices — that is how multi-account abuse
(one person claiming with several accounts) is detected.

## Server requirements

- PHP 8.0+ with `curl`, `mbstring`, `session`
- `post_max_size >= 16M`, `memory_limit >= 256M`, `max_execution_time >= 120`
- Writable `admin-php/uploads/`
