package matsantura.ampuh;

import android.content.Context;
import android.content.SharedPreferences;
import android.security.keystore.KeyGenParameterSpec;
import android.security.keystore.KeyProperties;
import android.util.Base64;

import org.json.JSONObject;

import java.nio.charset.StandardCharsets;
import java.security.KeyStore;

import javax.crypto.Cipher;
import javax.crypto.KeyGenerator;
import javax.crypto.SecretKey;
import javax.crypto.spec.GCMParameterSpec;

public class CredentialStore {
    private static final String KEY_ALIAS = "ampuh_login_credentials";
    private static final String PREFS_NAME = "ampuh_credentials";
    private static final String PREF_IV = "iv";
    private static final String PREF_DATA = "data";
    private static final String PREF_REMINDER_IV = "reminder_iv";
    private static final String PREF_REMINDER_TOKEN = "reminder_token";
    private static final String PREF_APP_LOGIN_IV = "app_login_iv";
    private static final String PREF_APP_LOGIN_TOKEN = "app_login_token";
    private static final String ANDROID_KEYSTORE = "AndroidKeyStore";
    private static final String TRANSFORMATION = "AES/GCM/NoPadding";

    private final SharedPreferences preferences;

    public CredentialStore(Context context) {
        preferences = context.getApplicationContext().getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE);
    }

    public void save(String identifier, String password) {
        if (identifier == null || identifier.trim().isEmpty() || password == null || password.isEmpty()) {
            return;
        }

        try {
            JSONObject json = new JSONObject();
            json.put("identifier", identifier.trim());
            json.put("password", password);

            Cipher cipher = Cipher.getInstance(TRANSFORMATION);
            cipher.init(Cipher.ENCRYPT_MODE, getOrCreateSecretKey());

            byte[] encrypted = cipher.doFinal(json.toString().getBytes(StandardCharsets.UTF_8));

            preferences.edit()
                    .putString(PREF_IV, Base64.encodeToString(cipher.getIV(), Base64.NO_WRAP))
                    .putString(PREF_DATA, Base64.encodeToString(encrypted, Base64.NO_WRAP))
                    .apply();
        } catch (Exception ignored) {
        }
    }

    public String loadJson() {
        String iv = preferences.getString(PREF_IV, "");
        String data = preferences.getString(PREF_DATA, "");

        if (iv == null || iv.isEmpty() || data == null || data.isEmpty()) {
            return "{}";
        }

        try {
            Cipher cipher = Cipher.getInstance(TRANSFORMATION);
            GCMParameterSpec spec = new GCMParameterSpec(128, Base64.decode(iv, Base64.NO_WRAP));
            cipher.init(Cipher.DECRYPT_MODE, getOrCreateSecretKey(), spec);

            byte[] decrypted = cipher.doFinal(Base64.decode(data, Base64.NO_WRAP));
            return new String(decrypted, StandardCharsets.UTF_8);
        } catch (Exception ignored) {
            clear();
            return "{}";
        }
    }

    public void clear() {
        preferences.edit().remove(PREF_IV).remove(PREF_DATA).apply();
    }

    public boolean hasSavedCredentials() {
        String iv = preferences.getString(PREF_IV, "");
        String data = preferences.getString(PREF_DATA, "");
        return iv != null && !iv.isEmpty() && data != null && !data.isEmpty();
    }

    public void saveReminderToken(String token) {
        if (token == null || token.trim().isEmpty()) {
            return;
        }

        try {
            Cipher cipher = Cipher.getInstance(TRANSFORMATION);
            cipher.init(Cipher.ENCRYPT_MODE, getOrCreateSecretKey());

            byte[] encrypted = cipher.doFinal(token.trim().getBytes(StandardCharsets.UTF_8));

            preferences.edit()
                    .putString(PREF_REMINDER_IV, Base64.encodeToString(cipher.getIV(), Base64.NO_WRAP))
                    .putString(PREF_REMINDER_TOKEN, Base64.encodeToString(encrypted, Base64.NO_WRAP))
                    .apply();
        } catch (Exception ignored) {
        }
    }

    public String getReminderToken() {
        String iv = preferences.getString(PREF_REMINDER_IV, "");
        String data = preferences.getString(PREF_REMINDER_TOKEN, "");

        if (iv == null || iv.isEmpty() || data == null || data.isEmpty()) {
            return "";
        }

        try {
            Cipher cipher = Cipher.getInstance(TRANSFORMATION);
            GCMParameterSpec spec = new GCMParameterSpec(128, Base64.decode(iv, Base64.NO_WRAP));
            cipher.init(Cipher.DECRYPT_MODE, getOrCreateSecretKey(), spec);

            byte[] decrypted = cipher.doFinal(Base64.decode(data, Base64.NO_WRAP));
            return new String(decrypted, StandardCharsets.UTF_8);
        } catch (Exception ignored) {
            clearReminderToken();
            return "";
        }
    }

    public void clearReminderToken() {
        preferences.edit().remove(PREF_REMINDER_IV).remove(PREF_REMINDER_TOKEN).apply();
    }

    public void saveAppLoginToken(String token) {
        saveEncryptedString(PREF_APP_LOGIN_IV, PREF_APP_LOGIN_TOKEN, token);
    }

    public String getAppLoginToken() {
        return loadEncryptedString(PREF_APP_LOGIN_IV, PREF_APP_LOGIN_TOKEN);
    }

    public boolean hasAppLoginToken() {
        String token = getAppLoginToken();
        return token != null && !token.trim().isEmpty();
    }

    public void clearAppLoginToken() {
        preferences.edit().remove(PREF_APP_LOGIN_IV).remove(PREF_APP_LOGIN_TOKEN).apply();
    }

    public void clearAll() {
        clear();
        clearReminderToken();
        clearAppLoginToken();
    }

    private void saveEncryptedString(String ivKey, String dataKey, String value) {
        if (value == null || value.trim().isEmpty()) {
            return;
        }

        try {
            Cipher cipher = Cipher.getInstance(TRANSFORMATION);
            cipher.init(Cipher.ENCRYPT_MODE, getOrCreateSecretKey());

            byte[] encrypted = cipher.doFinal(value.trim().getBytes(StandardCharsets.UTF_8));

            preferences.edit()
                    .putString(ivKey, Base64.encodeToString(cipher.getIV(), Base64.NO_WRAP))
                    .putString(dataKey, Base64.encodeToString(encrypted, Base64.NO_WRAP))
                    .apply();
        } catch (Exception ignored) {
        }
    }

    private String loadEncryptedString(String ivKey, String dataKey) {
        String iv = preferences.getString(ivKey, "");
        String data = preferences.getString(dataKey, "");

        if (iv == null || iv.isEmpty() || data == null || data.isEmpty()) {
            return "";
        }

        try {
            Cipher cipher = Cipher.getInstance(TRANSFORMATION);
            GCMParameterSpec spec = new GCMParameterSpec(128, Base64.decode(iv, Base64.NO_WRAP));
            cipher.init(Cipher.DECRYPT_MODE, getOrCreateSecretKey(), spec);

            byte[] decrypted = cipher.doFinal(Base64.decode(data, Base64.NO_WRAP));
            return new String(decrypted, StandardCharsets.UTF_8);
        } catch (Exception ignored) {
            preferences.edit().remove(ivKey).remove(dataKey).apply();
            return "";
        }
    }

    private SecretKey getOrCreateSecretKey() throws Exception {
        KeyStore keyStore = KeyStore.getInstance(ANDROID_KEYSTORE);
        keyStore.load(null);

        if (keyStore.containsAlias(KEY_ALIAS)) {
            return ((KeyStore.SecretKeyEntry) keyStore.getEntry(KEY_ALIAS, null)).getSecretKey();
        }

        KeyGenerator keyGenerator = KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, ANDROID_KEYSTORE);
        keyGenerator.init(new KeyGenParameterSpec.Builder(
                KEY_ALIAS,
                KeyProperties.PURPOSE_ENCRYPT | KeyProperties.PURPOSE_DECRYPT
        )
                .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
                .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
                .setRandomizedEncryptionRequired(true)
                .build());

        return keyGenerator.generateKey();
    }
}
