package ampuh.mtsn1jepara;

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
