package matsantura.ampuh;

import android.webkit.JavascriptInterface;

public class LoginCredentialBridge {
    private final CredentialStore credentialStore;
    private String pendingIdentifier = "";
    private String pendingPassword = "";
    private boolean pendingRemember;
    private boolean lastRememberedLogin;

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
            credentialStore.clearAll();
        }
    }

    public boolean savePendingIfRequested() {
        boolean remembered = false;
        if (pendingRemember && !pendingIdentifier.isEmpty() && !pendingPassword.isEmpty()) {
            credentialStore.save(pendingIdentifier, pendingPassword);
            remembered = true;
        }

        lastRememberedLogin = remembered;
        clearPending();
        return remembered;
    }

    public boolean shouldRememberDeviceLogin() {
        return lastRememberedLogin || credentialStore.hasSavedCredentials() || credentialStore.hasAppLoginToken();
    }

    public void clearPending() {
        pendingIdentifier = "";
        pendingPassword = "";
        pendingRemember = false;
    }
}
