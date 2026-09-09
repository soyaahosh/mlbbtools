<?php
/**
 * Ketupat MLBB Admin Gatekeeper - FIDO2 / WebAuthn Passkey Screen
 * True hardware-backed & Google Account / iCloud / Windows Hello Passkeys
 */
if (!defined('CONFIG_FILE')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/webauthn.php';

$loginError = $loginError ?? '';
$registeredPasskeys = WebAuthnEngine::getPasskeys();
$hasPasskeys = !empty($registeredPasskeys);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#070913">
  <title>Passkey Login // Ketupat Command Center</title>
  
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <link rel="stylesheet" href="style.css?v=<?= time() ?>">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23059669'%3E%3Cpath d='M12 2L2 12l10 10 10-10L12 2zm0 3.8L18.2 12 12 18.2 5.8 12 12 5.8z'/%3E%3C/svg%3E">
  
  <style>
    body.gatekeeper-body {
      background-color: #070913;
      background-image: 
        radial-gradient(circle at 50% 20%, rgba(5, 150, 105, 0.15) 0%, transparent 60%),
        radial-gradient(circle at 85% 85%, rgba(124, 58, 237, 0.1) 0%, transparent 50%);
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
      max-width: 450px;
      background: rgba(15, 23, 42, 0.88);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 45px rgba(5, 150, 105, 0.18);
      border-radius: 22px;
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
      margin-bottom: 26px;
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
      background-color: #10b981;
      border-radius: 50%;
      box-shadow: 0 0 8px #10b981;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0% { opacity: 0.6; transform: scale(0.95); }
      50% { opacity: 1; transform: scale(1.15); }
      100% { opacity: 0.6; transform: scale(0.95); }
    }

    .alert-banner {
      padding: 12px 14px;
      border-radius: 12px;
      font-size: 13px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      animation: shake 0.4s ease;
    }

    .alert-error {
      background: rgba(220, 38, 38, 0.15);
      border: 1px solid rgba(220, 38, 38, 0.35);
      color: #fca5a5;
    }

    .alert-success {
      background: rgba(5, 150, 105, 0.15);
      border: 1px solid rgba(16, 185, 129, 0.35);
      color: #6ee7b7;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-6px); }
      40%, 80% { transform: translateX(6px); }
    }

    /* Primary Passkey Button */
    .btn-passkey-primary {
      width: 100%;
      padding: 16px 20px;
      background: linear-gradient(135deg, #059669, #047857);
      border: 1px solid rgba(52, 211, 153, 0.3);
      border-radius: 14px;
      color: #ffffff;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 14px;
      box-shadow: 0 8px 24px rgba(5, 150, 105, 0.35);
      transition: all 0.2s ease;
      text-align: left;
      margin-bottom: 16px;
    }

    .btn-passkey-primary:hover {
      background: linear-gradient(135deg, #10b981, #059669);
      box-shadow: 0 10px 30px rgba(16, 185, 129, 0.45);
      transform: translateY(-2px);
    }

    .btn-passkey-primary:active {
      transform: translateY(0);
    }

    .passkey-icon-box {
      width: 44px;
      height: 44px;
      background: rgba(255, 255, 255, 0.15);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      flex-shrink: 0;
    }

    .passkey-text-wrap {
      flex: 1;
    }

    .passkey-btn-title {
      font-size: 16px;
      font-weight: 700;
      display: block;
      margin-bottom: 2px;
    }

    .passkey-btn-sub {
      font-size: 11px;
      color: #d1fae5;
      display: block;
    }

    /* Register New Passkey Section */
    .register-passkey-box {
      background: rgba(2, 6, 23, 0.5);
      border: 1px dashed rgba(255, 255, 255, 0.15);
      border-radius: 14px;
      padding: 14px 16px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .register-text {
      font-size: 12px;
      color: #94a3b8;
    }

    .register-text strong {
      color: #f1f5f9;
      display: block;
      font-size: 13px;
      margin-bottom: 2px;
    }

    .btn-setup-passkey {
      background: rgba(124, 58, 237, 0.15);
      border: 1px solid rgba(139, 92, 246, 0.35);
      color: #c4b5fd;
      padding: 8px 14px;
      border-radius: 10px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      white-space: nowrap;
      transition: all 0.2s;
    }

    .btn-setup-passkey:hover {
      background: rgba(124, 58, 237, 0.3);
      color: #ffffff;
      border-color: rgba(167, 139, 250, 0.5);
    }

    .divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 20px 0;
      color: #64748b;
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

    .passkey-field-wrap {
      position: relative;
      display: flex;
      align-items: center;
      margin-bottom: 14px;
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
      height: 46px;
      background: rgba(2, 6, 23, 0.6);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 12px;
      padding: 0 46px 0 44px;
      color: #ffffff;
      font-size: 15px;
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
    }

    .btn-pin-unlock {
      width: 100%;
      height: 44px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 12px;
      color: #cbd5e1;
      font-size: 13px;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-pin-unlock:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: rgba(255, 255, 255, 0.2);
      color: #ffffff;
    }

    .gatekeeper-footer {
      text-align: center;
      margin-top: 22px;
      font-size: 12px;
      color: #64748b;
    }
  </style>
</head>
<body class="gatekeeper-body">

  <div class="gatekeeper-card">
    <div class="gatekeeper-header">
      <div class="badge-icon-wrapper">
        <span class="material-symbols-outlined">fingerprint</span>
      </div>
      <h1 class="gatekeeper-title">Command Center</h1>
      <p class="gatekeeper-subtitle">
        <span class="status-dot-pulse"></span>
        FIDO2 / WebAuthn Hardware Passkey Shield
      </p>
    </div>

    <div class="alert-banner alert-error" id="loginAlert" style="<?= empty($loginError) ? 'display: none;' : '' ?>">
      <span class="material-symbols-outlined" style="font-size: 18px;">error</span>
      <span id="loginAlertText"><?= htmlspecialchars($loginError) ?></span>
    </div>

    <!-- PRIMARY ACTION: Sign in with Passkey -->
    <button type="button" class="btn-passkey-primary" id="btnSignInPasskey">
      <div class="passkey-icon-box">
        <span class="material-symbols-outlined">key</span>
      </div>
      <div class="passkey-text-wrap">
        <span class="passkey-btn-title">Sign in with Passkey</span>
        <span class="passkey-btn-sub">Google Account &bull; iCloud Keychain &bull; Windows Hello</span>
      </div>
      <span class="material-symbols-outlined" style="font-size: 20px;">arrow_forward</span>
    </button>

    <!-- SETUP PASSKEY CARD (If no passkey or adding new device) -->
    <div class="register-passkey-box" id="boxRegisterPasskey">
      <div class="register-text">
        <strong>Create / Add Passkey</strong>
        <span>Save to your Google Account or device</span>
      </div>
      <button type="button" class="btn-setup-passkey" id="btnRegisterNewPasskey">
        <span class="material-symbols-outlined" style="font-size: 16px;">add_circle</span>
        <span>Register Passkey</span>
      </button>
    </div>

    <div class="divider">or emergency master PIN</div>

    <!-- FALLBACK: Master Admin PIN Form -->
    <form method="POST" action="index.php" id="formPinLogin">
      <input type="hidden" name="action" value="login">
      <input type="hidden" name="remember" value="1">
      
      <div class="passkey-field-wrap">
        <span class="material-symbols-outlined passkey-field-icon">lock</span>
        <input 
          type="password" 
          id="inputPin" 
          name="passkey" 
          class="passkey-input" 
          placeholder="Master PIN (Default: 123456)" 
          required
        >
        <button type="button" class="passkey-toggle-btn" id="btnTogglePin" title="Show/Hide PIN">
          <span class="material-symbols-outlined" id="togglePinIcon">visibility</span>
        </button>
      </div>

      <button type="submit" class="btn-pin-unlock" id="btnSubmitPin">
        <span class="material-symbols-outlined" style="font-size: 16px;">vpn_key</span>
        <span>Unlock with Master PIN</span>
      </button>
    </form>

    <div class="gatekeeper-footer">
      Protected with Asymmetric Cryptography (ES256 / WebAuthn)
    </div>
  </div>

  <script>
    // Utility helpers for WebAuthn binary Base64URL conversions
    function bufferToBase64url(buffer) {
      const bytes = new Uint8Array(buffer);
      let binary = '';
      for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
      }
      return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    function base64urlToBuffer(base64url) {
      let base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
      while (base64.length % 4) base64 += '=';
      const binary = atob(base64);
      const bytes = new Uint8Array(binary.length);
      for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
      }
      return bytes.buffer;
    }

    const btnSignInPasskey = document.getElementById("btnSignInPasskey");
    const btnRegisterNewPasskey = document.getElementById("btnRegisterNewPasskey");
    const formPinLogin = document.getElementById("formPinLogin");
    const inputPin = document.getElementById("inputPin");
    const btnTogglePin = document.getElementById("btnTogglePin");
    const togglePinIcon = document.getElementById("togglePinIcon");
    const loginAlert = document.getElementById("loginAlert");
    const loginAlertText = document.getElementById("loginAlertText");

    function showAlert(msg, isSuccess = false) {
      loginAlertText.textContent = msg;
      loginAlert.className = isSuccess ? "alert-banner alert-success" : "alert-banner alert-error";
      loginAlert.style.display = "flex";
      loginAlert.style.animation = "none";
      setTimeout(() => { loginAlert.style.animation = "shake 0.4s ease"; }, 10);
    }

    // Toggle PIN field visibility
    btnTogglePin.addEventListener("click", () => {
      const isPwd = inputPin.type === "password";
      inputPin.type = isPwd ? "text" : "password";
      togglePinIcon.textContent = isPwd ? "visibility_off" : "visibility";
    });

    // =========================================================================
    // 1. SIGN IN WITH FIDO2 / WEBAUTHN PASSKEY
    // =========================================================================
    btnSignInPasskey.addEventListener("click", async () => {
      if (!window.PublicKeyCredential) {
        showAlert("Your browser does not support WebAuthn Passkeys. Please use the Master PIN.");
        return;
      }

      btnSignInPasskey.disabled = true;
      btnSignInPasskey.style.opacity = "0.7";

      try {
        // Step 1: Request challenge & options from server
        const optRes = await fetch("api.php?action=passkey_login_options");
        const optData = await optRes.json();
        if (!optData.success || !optData.data) {
          throw new Error(optData.message || "Failed retrieving passkey options.");
        }

        const opts = optData.data;

        // Convert base64url challenge and allowCredentials to Buffers
        const getOptions = {
          challenge: base64urlToBuffer(opts.challenge),
          rpId: opts.rpId || window.location.hostname,
          userVerification: opts.userVerification || "preferred",
          timeout: opts.timeout || 60000
        };

        if (opts.allowCredentials && opts.allowCredentials.length > 0) {
          getOptions.allowCredentials = opts.allowCredentials.map(c => ({
            type: "public-key",
            id: base64urlToBuffer(c.id)
          }));
        }

        // Step 2: Invoke native OS / Google Password Manager prompt!
        const assertion = await navigator.credentials.get({
          publicKey: getOptions
        });

        if (!assertion) {
          throw new Error("No passkey was selected or verified.");
        }

        // Step 3: Send assertion response to server for cryptographic verification
        const payload = {
          id: assertion.id,
          rawId: bufferToBase64url(assertion.rawId),
          clientDataJSON: bufferToBase64url(assertion.response.clientDataJSON),
          authenticatorData: bufferToBase64url(assertion.response.authenticatorData),
          signature: bufferToBase64url(assertion.response.signature),
          userHandle: assertion.response.userHandle ? bufferToBase64url(assertion.response.userHandle) : null
        };

        const verifyRes = await fetch("api.php?action=passkey_login_verify", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        });
        const verifyData = await verifyRes.json();

        if (verifyData.success) {
          showAlert("Passkey Verified! Welcome Administrator.", true);
          setTimeout(() => {
            window.location.href = "index.php";
          }, 400);
        } else {
          throw new Error(verifyData.message || "Passkey verification failed.");
        }
      } catch (err) {
        console.warn("Passkey authentication notice:", err);
        if (err.name === "NotAllowedError") {
          showAlert("Passkey request was canceled or timed out.");
        } else {
          showAlert(err.message || "Could not sign in with passkey.");
        }
      } finally {
        btnSignInPasskey.disabled = false;
        btnSignInPasskey.style.opacity = "1";
      }
    });

    // =========================================================================
    // 2. REGISTER NEW FIDO2 PASSKEY (Save to Google Account / iCloud / Device)
    // =========================================================================
    btnRegisterNewPasskey.addEventListener("click", async () => {
      if (!window.PublicKeyCredential) {
        showAlert("Your device/browser does not support WebAuthn Passkeys.");
        return;
      }

      // Prompt for Master PIN once to authorize creation
      let pin = inputPin.value.trim();
      if (!pin) {
        pin = prompt("Enter Master Admin PIN (Default: 123456) to authorize registering a new passkey:");
        if (!pin) return;
        inputPin.value = pin;
      }

      const passkeyName = prompt("Name this passkey (e.g. Pixel 8, Mac TouchID, Work PC):", navigator.userAgent.includes("Android") ? "Android Phone" : "My Computer") || "Device Passkey";

      btnRegisterNewPasskey.disabled = true;

      try {
        // Step 1: Request creation options
        const optRes = await fetch("api.php?action=passkey_register_options");
        const optData = await optRes.json();
        if (!optData.success || !optData.data) {
          throw new Error(optData.message || "Failed retrieving registration options.");
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

        // Step 2: Native OS prompt "Save passkey to Google Password Manager / Device"
        const credential = await navigator.credentials.create({
          publicKey: createOptions
        });

        if (!credential) {
          throw new Error("Passkey creation was canceled.");
        }

        // Step 3: Transmit attestation to server
        const payload = {
          pin: pin,
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
          showAlert("✓ Passkey saved to Google Account / Device! Redirecting...", true);
          setTimeout(() => {
            window.location.href = "index.php";
          }, 500);
        } else {
          throw new Error(regData.message || "Failed registering passkey.");
        }
      } catch (err) {
        console.warn("Passkey registration notice:", err);
        if (err.name === "NotAllowedError") {
          showAlert("Passkey setup was canceled.");
        } else {
          showAlert(err.message || "Passkey registration failed.");
        }
      } finally {
        btnRegisterNewPasskey.disabled = false;
      }
    });

    // =========================================================================
    // 3. MASTER PIN FALLBACK FORM SUBMIT
    // =========================================================================
    formPinLogin.addEventListener("submit", async (e) => {
      e.preventDefault();
      const pin = inputPin.value.trim();
      if (!pin) return;

      const btn = document.getElementById("btnSubmitPin");
      btn.disabled = true;
      btn.innerHTML = '<span>Verifying PIN...</span>';

      try {
        const res = await fetch("api.php?action=login", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ passkey: pin, remember: true })
        });
        const data = await res.json();

        if (data && data.success) {
          btn.innerHTML = '<span>Access Granted!</span>';
          setTimeout(() => { window.location.href = "index.php"; }, 300);
        } else {
          showAlert(data?.message || "Incorrect Master PIN. Access denied.");
          btn.disabled = false;
          btn.innerHTML = '<span class="material-symbols-outlined" style="font-size: 16px;">vpn_key</span> <span>Unlock with Master PIN</span>';
        }
      } catch (err) {
        formPinLogin.submit();
      }
    });
  </script>
</body>
</html>
