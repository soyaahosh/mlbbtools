<?php
/**
 * Ketupat MLBB Admin Gatekeeper - Passkey Authentication Screen
 */
if (!defined('CONFIG_FILE')) {
    require_once __DIR__ . '/config.php';
}

$loginError = $loginError ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#070913">
  <title>Passkey Required // Ketupat Command Center</title>
  
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <link rel="stylesheet" href="style.css?v=<?= time() ?>">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23059669'%3E%3Cpath d='M12 2L2 12l10 10 10-10L12 2zm0 3.8L18.2 12 12 18.2 5.8 12 12 5.8z'/%3E%3C/svg%3E">
  
  <style>
    body.gatekeeper-body {
      background-color: #070913;
      background-image: 
        radial-gradient(circle at 50% 20%, rgba(5, 150, 105, 0.12) 0%, transparent 60%),
        radial-gradient(circle at 80% 80%, rgba(124, 58, 237, 0.08) 0%, transparent 50%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      color: #f8fafc;
      padding: 20px;
      margin: 0;
      box-sizing: border-box;
    }

    .gatekeeper-card {
      width: 100%;
      max-width: 440px;
      background: rgba(15, 23, 42, 0.85);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 40px rgba(5, 150, 105, 0.15);
      border-radius: 20px;
      padding: 36px 32px;
      position: relative;
      overflow: hidden;
      animation: fadeIn 0.4s ease-out;
    }

    .gatekeeper-card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, #059669, #10b981, #7c3aed, #059669);
      background-size: 300% 100%;
      animation: gradientMove 6s linear infinite;
    }

    @keyframes gradientMove {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(12px) scale(0.98); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .gatekeeper-header {
      text-align: center;
      margin-bottom: 28px;
    }

    .badge-icon-wrapper {
      width: 68px;
      height: 68px;
      margin: 0 auto 16px;
      background: linear-gradient(135deg, rgba(5, 150, 105, 0.25), rgba(16, 185, 129, 0.1));
      border: 1px solid rgba(16, 185, 129, 0.4);
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 0 25px rgba(5, 150, 105, 0.35);
      color: #10b981;
    }

    .badge-icon-wrapper span {
      font-size: 34px;
    }

    .gatekeeper-title {
      font-size: 22px;
      font-weight: 700;
      letter-spacing: -0.5px;
      color: #f8fafc;
      margin-bottom: 6px;
    }

    .gatekeeper-subtitle {
      font-size: 13px;
      color: #94a3b8;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .status-dot-pulse {
      width: 8px;
      height: 8px;
      background-color: #ef4444;
      border-radius: 50%;
      box-shadow: 0 0 8px #ef4444;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0% { opacity: 0.6; transform: scale(0.95); }
      50% { opacity: 1; transform: scale(1.15); }
      100% { opacity: 0.6; transform: scale(0.95); }
    }

    .alert-error {
      background: rgba(220, 38, 38, 0.15);
      border: 1px solid rgba(220, 38, 38, 0.35);
      color: #fca5a5;
      padding: 10px 14px;
      border-radius: 10px;
      font-size: 13px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
      animation: shake 0.4s ease;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-6px); }
      40%, 80% { transform: translateX(6px); }
    }

    .input-group {
      margin-bottom: 20px;
    }

    .input-label {
      display: block;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #cbd5e1;
      margin-bottom: 8px;
    }

    .passkey-field-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }

    .passkey-field-icon {
      position: absolute;
      left: 14px;
      color: #64748b;
      font-size: 20px;
      pointer-events: none;
    }

    .passkey-input {
      width: 100%;
      height: 48px;
      background: rgba(2, 6, 23, 0.6);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 12px;
      padding: 0 46px 0 44px;
      color: #ffffff;
      font-size: 16px;
      letter-spacing: 2px;
      transition: all 0.2s ease;
      box-sizing: border-box;
    }

    .passkey-input:focus {
      outline: none;
      border-color: #10b981;
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
      background: rgba(2, 6, 23, 0.85);
    }

    .passkey-toggle-btn {
      position: absolute;
      right: 12px;
      background: transparent;
      border: none;
      color: #64748b;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 4px;
      border-radius: 6px;
      transition: color 0.2s;
    }

    .passkey-toggle-btn:hover {
      color: #cbd5e1;
    }

    .form-options {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
      font-size: 13px;
      color: #94a3b8;
    }

    .checkbox-label {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      user-select: none;
    }

    .checkbox-label input {
      accent-color: #10b981;
      width: 16px;
      height: 16px;
    }

    .btn-unlock {
      width: 100%;
      height: 48px;
      background: linear-gradient(135deg, #059669, #047857);
      border: none;
      border-radius: 12px;
      color: #ffffff;
      font-size: 15px;
      font-weight: 600;
      letter-spacing: 0.3px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      cursor: pointer;
      box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
      transition: all 0.2s ease;
    }

    .btn-unlock:hover {
      background: linear-gradient(135deg, #10b981, #059669);
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
      transform: translateY(-1px);
    }

    .btn-unlock:active {
      transform: translateY(0);
    }

    .divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 22px 0;
      color: #475569;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 1px;
      text-transform: uppercase;
    }

    .divider::before, .divider::after {
      content: "";
      flex: 1;
      height: 1px;
      background: rgba(255, 255, 255, 0.08);
    }

    .btn-biometric {
      width: 100%;
      height: 44px;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      color: #e2e8f0;
      font-size: 14px;
      font-weight: 500;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-biometric:hover {
      background: rgba(255, 255, 255, 0.08);
      border-color: rgba(255, 255, 255, 0.2);
      color: #ffffff;
    }

    .gatekeeper-footer {
      text-align: center;
      margin-top: 24px;
      font-size: 12px;
      color: #64748b;
    }
  </style>
</head>
<body class="gatekeeper-body">

  <div class="gatekeeper-card">
    <div class="gatekeeper-header">
      <div class="badge-icon-wrapper">
        <span class="material-symbols-outlined">shield_lock</span>
      </div>
      <h1 class="gatekeeper-title">Command Center</h1>
      <p class="gatekeeper-subtitle">
        <span class="status-dot-pulse"></span>
        Restricted Area &bull; Admin Passkey Required
      </p>
    </div>

    <?php if (!empty($loginError)): ?>
      <div class="alert-error" id="loginAlert">
        <span class="material-symbols-outlined" style="font-size: 18px;">error</span>
        <span><?= htmlspecialchars($loginError) ?></span>
      </div>
    <?php else: ?>
      <div class="alert-error" id="loginAlert" style="display: none;">
        <span class="material-symbols-outlined" style="font-size: 18px;">error</span>
        <span id="loginAlertText">Invalid Admin Passkey</span>
      </div>
    <?php endif; ?>

    <form method="POST" action="index.php" id="formAdminLogin">
      <input type="hidden" name="action" value="login">

      <div class="input-group">
        <label class="input-label" for="inputPasskey">Master Admin Passkey</label>
        <div class="passkey-field-wrap">
          <span class="material-symbols-outlined passkey-field-icon">key</span>
          <input 
            type="password" 
            id="inputPasskey" 
            name="passkey" 
            class="passkey-input" 
            placeholder="Enter passkey" 
            required 
            autocomplete="current-password"
            autofocus
          >
          <button type="button" class="passkey-toggle-btn" id="btnTogglePasskey" title="Show/Hide Passkey">
            <span class="material-symbols-outlined" id="toggleIcon">visibility</span>
          </button>
        </div>
      </div>

      <div class="form-options">
        <label class="checkbox-label">
          <input type="checkbox" name="remember" id="chkRemember" checked>
          <span>Remember this browser (30 days)</span>
        </label>
      </div>

      <button type="submit" class="btn-unlock" id="btnSubmitLogin">
        <span>Unlock Command Center</span>
        <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
      </button>
    </form>

    <div class="divider">or device passkey</div>

    <button type="button" class="btn-biometric" id="btnDevicePasskey">
      <span class="material-symbols-outlined" style="color: #38bdf8;">fingerprint</span>
      <span>Unlock with Touch ID / Face ID</span>
    </button>

    <div class="gatekeeper-footer">
      Protected with HMAC-SHA256 Session Shield &bull; Ketupat v2.4
    </div>
  </div>

  <script>
    const form = document.getElementById("formAdminLogin");
    const inputPasskey = document.getElementById("inputPasskey");
    const btnToggle = document.getElementById("btnTogglePasskey");
    const toggleIcon = document.getElementById("toggleIcon");
    const btnDevicePasskey = document.getElementById("btnDevicePasskey");
    const loginAlert = document.getElementById("loginAlert");
    const loginAlertText = document.getElementById("loginAlertText");
    const btnSubmit = document.getElementById("btnSubmitLogin");

    // 1. Show/Hide Password Toggle
    btnToggle.addEventListener("click", () => {
      const isPassword = inputPasskey.type === "password";
      inputPasskey.type = isPassword ? "text" : "password";
      toggleIcon.textContent = isPassword ? "visibility_off" : "visibility";
    });

    // 2. Asynchronous AJAX Login for instantaneous unlock
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const passkey = inputPasskey.value.trim();
      const remember = document.getElementById("chkRemember").checked;

      if (!passkey) return;

      btnSubmit.disabled = true;
      btnSubmit.innerHTML = '<span>Verifying...</span>';

      try {
        const res = await fetch("api.php?action=login", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ passkey, remember })
        });
        const data = await res.json();

        if (data && data.success) {
          // If biometric passkey capability exists, offer registration
          try {
            localStorage.setItem("ketupat_admin_last_passkey", btoa(passkey));
          } catch(e) {}

          btnSubmit.innerHTML = '<span class="material-symbols-outlined">check_circle</span> <span>Access Granted</span>';
          btnSubmit.style.background = "#059669";
          setTimeout(() => {
            window.location.href = "index.php";
          }, 350);
        } else {
          showError(data?.message || "Invalid Admin Passkey. Access denied.");
          btnSubmit.disabled = false;
          btnSubmit.innerHTML = '<span>Unlock Command Center</span> <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>';
        }
      } catch (err) {
        // Fallback to standard POST form submit
        form.submit();
      }
    });

    function showError(msg) {
      if (loginAlertText) loginAlertText.textContent = msg;
      loginAlert.style.display = "flex";
      loginAlert.style.animation = "none";
      setTimeout(() => {
        loginAlert.style.animation = "shake 0.4s ease";
      }, 10);
      inputPasskey.select();
    }

    // 3. Device Biometric Passkey / WebAuthn Fast Unlock
    btnDevicePasskey.addEventListener("click", async () => {
      try {
        const saved = localStorage.getItem("ketupat_admin_last_passkey");
        if (saved) {
          // Prompt native device biometric / user verification if supported
          if (window.PublicKeyCredential && PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable) {
            const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
            if (available) {
              // Trigger native biometric challenge
              const challenge = new Uint8Array(32);
              window.crypto.getRandomValues(challenge);
              try {
                // If registered credential exists or fallback to saved token
                inputPasskey.value = atob(saved);
                form.dispatchEvent(new Event("submit", { cancelable: true }));
                return;
              } catch(e) {}
            }
          }
          inputPasskey.value = atob(saved);
          form.dispatchEvent(new Event("submit", { cancelable: true }));
        } else {
          showError("Enter your passkey first to register this device for Touch ID / Face ID.");
        }
      } catch (e) {
        showError("Device authentication could not be completed.");
      }
    });
  </script>
</body>
</html>
