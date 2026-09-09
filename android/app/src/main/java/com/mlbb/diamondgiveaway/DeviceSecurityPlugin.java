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

    @PluginMethod
    public void getDeviceInfo(PluginCall call) {
        Context ctx = getContext();
        String androidId = "";
        try {
            androidId = Settings.Secure.getString(ctx.getContentResolver(), Settings.Secure.ANDROID_ID);
        } catch (Exception ignored) {}

        String deviceId = (androidId != null && !androidId.trim().isEmpty()) ? ("dev_" + androidId.trim()) : "";
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
        boolean emulator = isEmulator();
        boolean cloned = isCloned(getContext());
        boolean blocked = emulator || cloned;

        JSObject ret = new JSObject();
        ret.put("isLegit", !blocked);
        ret.put("isEmulator", emulator);
        ret.put("isCloned", cloned);
        ret.put("isBlocked", blocked);
        ret.put("reason", emulator ? "Emulator detected" : (cloned ? "Cloned app environment detected" : ""));
        call.resolve(ret);
    }

    private boolean isEmulator() {
        return (Build.BRAND.startsWith("generic") && Build.DEVICE.startsWith("generic"))
            || Build.FINGERPRINT.startsWith("generic")
            || Build.FINGERPRINT.startsWith("unknown")
            || Build.HARDWARE.contains("goldfish")
            || Build.HARDWARE.contains("ranchu")
            || Build.MODEL.contains("google_sdk")
            || Build.MODEL.contains("Emulator")
            || Build.MODEL.contains("Android SDK built for x86")
            || Build.MANUFACTURER.contains("Genymotion")
            || Build.PRODUCT.contains("sdk_google")
            || Build.PRODUCT.contains("google_sdk")
            || Build.PRODUCT.contains("sdk")
            || Build.PRODUCT.contains("vbox86p");
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
