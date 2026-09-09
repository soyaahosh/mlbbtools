<?php
/**
 * Ketupat MLBB Admin Gatekeeper - FIDO2 / WebAuthn Passkey Screen
 * True hardware-backed & Google Account / iCloud / Windows Hello Passkeys
 */
if (!defined('CONFIG_FILE')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/webauthn.php';

// If already logged in, redirect directly into the Command Center
if (isAdminAuthenticated()) {
    header('Location: index.php');
    exit;
}

$loginError = $loginError ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#070913">
  <title>Accessing vault...</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
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
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      color: #f8fafc;
      padding: 20px;
      margin: 0;
      box-sizing: border-box;
    }

    .gatekeeper-card {
      width: 100%;
      max-width: 420px;
      background: rgba(15, 23, 42, 0.9);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 45px rgba(5, 150, 105, 0.15);
      border-radius: 22px;
      padding: 38px 32px 34px;
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
      background: linear-gradient(90deg, #059669, #10b981, #ef4444, #059669);
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
      font-size: 36px;
    }

    .gatekeeper-title {
      font-size: 22px;
      font-weight: 700;
      letter-spacing: -0.5px;
      color: #f8fafc;
      margin: 0 0 8px 0;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .gatekeeper-subtitle {
      font-size: 12.5px;
      color: #94a3b8;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
      margin: 0;
      line-height: 1.4;
    }

    .status-dot-pulse {
      width: 8px;
      height: 8px;
      background-color: #ef4444;
      border-radius: 50%;
      box-shadow: 0 0 8px #ef4444;
      animation: pulseRed 2s infinite;
      flex-shrink: 0;
    }

    @keyframes pulseRed {
      0% { opacity: 0.6; transform: scale(0.95); }
      50% { opacity: 1; transform: scale(1.25); }
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

    /* Simple Passkey Button: key icon with "Sign in with passkey" */
    .btn-passkey-primary {
      width: 100%;
      padding: 14px 20px;
      background: linear-gradient(135deg, #059669, #047857);
      border: 1px solid rgba(52, 211, 153, 0.35);
      border-radius: 12px;
      color: #ffffff;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      font-size: 15px;
      font-weight: 600;
      font-family: 'Plus Jakarta Sans', sans-serif;
      box-shadow: 0 8px 24px rgba(5, 150, 105, 0.3);
      transition: all 0.2s ease;
    }

    .btn-passkey-primary:hover:not(:disabled) {
      background: linear-gradient(135deg, #10b981, #059669);
      box-shadow: 0 10px 28px rgba(16, 185, 129, 0.45);
      transform: translateY(-1px);
    }

    .btn-passkey-primary:active:not(:disabled) {
      transform: translateY(0);
    }

    .btn-passkey-primary:disabled {
      opacity: 0.65;
      cursor: not-allowed;
    }

    .btn-passkey-primary .material-symbols-outlined {
      font-size: 20px;
    }
  </style>
</head>
<body class="gatekeeper-body">

  <div class="gatekeeper-card">
    <div class="gatekeeper-header">
      <div class="badge-icon-wrapper">
        <span class="material-symbols-outlined">fingerprint</span>
      </div>
      <h1 class="gatekeeper-title">Accessing vault...</h1>
      <p class="gatekeeper-subtitle">
        <span class="status-dot-pulse"></span>
        <span>Your IP address and location are being tracked.</span>
      </p>
    </div>

    <div class="alert-banner alert-error" id="loginAlert" style="<?= empty($loginError) ? 'display: none;' : '' ?>">
      <span class="material-symbols-outlined" style="font-size: 18px;">error</span>
      <span id="loginAlertText"><?= htmlspecialchars($loginError) ?></span>
    </div>

    <!-- SIMPLE PASSKEY BUTTON: Just a key icon with text "Sign in with passkey" -->
    <button type="button" class="btn-passkey-primary" id="btnSignInPasskey">
      <span class="material-symbols-outlined">key</span>
      <span>Sign in with passkey</span>
    </button>
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
    const loginAlert = document.getElementById("loginAlert");
    const loginAlertText = document.getElementById("loginAlertText");

    function showAlert(msg, isSuccess = false) {
      loginAlertText.textContent = msg;
      loginAlert.className = isSuccess ? "alert-banner alert-success" : "alert-banner alert-error";
      loginAlert.style.display = "flex";
      loginAlert.style.animation = "none";
      setTimeout(() => { loginAlert.style.animation = "shake 0.4s ease"; }, 10);
    }

    if (!window.PublicKeyCredential) {
      showAlert("Your device or browser does not support WebAuthn Passkeys. Please use Chrome, Edge, Safari, or Firefox.");
      btnSignInPasskey.disabled = true;
    }

    // =========================================================================
    // SIGN IN WITH FIDO2 / WEBAUTHN PASSKEY
    // =========================================================================
    btnSignInPasskey.addEventListener("click", async () => {
      if (!window.PublicKeyCredential) {
        showAlert("Your browser does not support WebAuthn Passkeys.");
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
          showAlert("✓ Passkey Verified! Welcome Administrator.", true);
          setTimeout(() => {
            window.location.href = "index.php";
          }, 350);
        } else {
          throw new Error(verifyData.message || "Passkey verification failed.");
        }
      } catch (err) {
        console.warn("Passkey authentication notice:", err);
        if (err.name === "NotAllowedError") {
          showAlert("Passkey prompt was canceled or timed out.");
        } else {
          showAlert(err.message || "Could not sign in with passkey.");
        }
      } finally {
        btnSignInPasskey.disabled = false;
        btnSignInPasskey.style.opacity = "1";
      }
    });
  </script>
</body>
</html>
