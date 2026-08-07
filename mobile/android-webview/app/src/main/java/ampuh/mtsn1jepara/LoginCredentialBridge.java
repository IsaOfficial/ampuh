package ampuh.mtsn1jepara;

import android.webkit.JavascriptInterface;

public class LoginCredentialBridge {
    private final CredentialStore credentialStore;
    private String pendingIdentifier = "";
    private String pendingPassword = "";
    private boolean pendingRemember;

    public LoginCredentialBridge(CredentialStore credentialStore) {
        this.credentialStore = credentialStore;
    }

    @JavascriptInterface
    public String getCredentials() {
        return credentialStore.loadJson();
    }

    @JavascriptInterface
    public void prepareLogin(String identifier, String password, boolean remember) {
        pendingIdentifier = identifier == null ? "" : identifier.trim();
        pendingPassword = password == null ? "" : password;
        pendingRemember = remember;

        if (!remember) {
            credentialStore.clear();
        }
    }

    public void savePendingIfRequested() {
        if (pendingRemember && !pendingIdentifier.isEmpty() && !pendingPassword.isEmpty()) {
            credentialStore.save(pendingIdentifier, pendingPassword);
        }

        clearPending();
    }

    public void clearPending() {
        pendingIdentifier = "";
        pendingPassword = "";
        pendingRemember = false;
    }
}
