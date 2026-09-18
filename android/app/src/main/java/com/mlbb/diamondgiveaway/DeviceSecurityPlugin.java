package com.mlbb.diamondgiveaway;

import android.content.Context;
import android.os.Build;
import android.provider.Settings;
import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;

@CapacitorPlugin(name = "DeviceSecurity")
public class DeviceSecurityPlugin extends Plugin {

    public static synchronized String getOrGenerateDeviceId(Context ctx) {
        if (ctx == null) return "dev_" + java.util.UUID.randomUUID().toString().replace("-", "").substring(0, 16);
        android.content.SharedPreferences prefs = ctx.getSharedPreferences("ketupat_device_identity", Context.MODE_PRIVATE);
        String cached = prefs.getString("unique_hardware_id", "");

        String androidId = "";
        try {
            androidId = Settings.Secure.getString(ctx.getContentResolver(), Settings.Secure.ANDROID_ID);
        } catch (Exception ignored) {}

        if (androidId != null && !androidId.trim().isEmpty() && !androidId.equalsIgnoreCase("9774d56d682e549c")) {
            String hwId = "dev_" + androidId.trim().toLowerCase();
            if (!hwId.equals(cached)) {
                prefs.edit().putString("unique_hardware_id", hwId).apply();
            }
            return hwId;
        }

        if (cached != null && !cached.trim().isEmpty()) {
            return cached.trim();
        }

        String newId = "dev_" + java.util.UUID.randomUUID().toString().replace("-", "").substring(0, 16);
        prefs.edit().putString("unique_hardware_id", newId).apply();
        return newId;
    }

    @PluginMethod
    public void getDeviceInfo(PluginCall call) {
        Context ctx = getContext();
        String deviceId = getOrGenerateDeviceId(ctx);
        String model = Build.MODEL != null ? Build.MODEL.trim() : "Android Device";
        String manufacturer = Build.MANUFACTURER != null ? Build.MANUFACTURER.trim() : "";
        String deviceName = (!manufacturer.isEmpty() && !model.toLowerCase().startsWith(manufacturer.toLowerCase()))
            ? (manufacturer + " " + model)
            : model;
        String fingerprint = Build.FINGERPRINT != null ? Build.FINGERPRINT : "";

        JSObject ret = new JSObject();
        ret.put("deviceId", deviceId);
        ret.put("deviceName", deviceName);
        ret.put("model", model);
        ret.put("manufacturer", manufacturer);
        ret.put("fingerprint", fingerprint);
        call.resolve(ret);
    }

    @PluginMethod
    public void isDeviceLegit(PluginCall call) {
        checkDeviceIntegrity(call);
    }

    @PluginMethod
    public void checkDeviceIntegrity(PluginCall call) {
        JSObject ret = new JSObject();
        ret.put("isLegit", true);
        ret.put("isEmulator", false);
        ret.put("isCloned", false);
        ret.put("isBlocked", false);
        ret.put("reason", "");
        call.resolve(ret);
    }

    private boolean isEmulator() {
        // Emulators explicitly allowed
        return false;
    }

    private boolean isCloned(Context context) {
        try {
            String path = context.getFilesDir().getAbsolutePath();
            return path.contains("virtual") || path.contains("parallel") || path.contains("dual") || path.contains("clone");
        } catch (Exception e) {
            return false;
        }
    }
}
