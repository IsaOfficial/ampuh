package matsantura.ampuh;

import android.content.Context;
import android.webkit.CookieManager;

import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;

public final class ReportStatusClient {
    private static final String HOME_URL = "https://ampuh.mtsn1jepara.sch.id";
    private static final String STATUS_URL = HOME_URL + "/pegawai/laporan/status-hari-ini";
    private static final String TOKEN_URL = HOME_URL + "/pegawai/reminder-token";
    private static final String TOKEN_STATUS_URL = HOME_URL + "/api/reminder/status-hari-ini";
    private static final String APP_LOGIN_TOKEN_URL = HOME_URL + "/pegawai/app-login-token";

    private final CredentialStore credentialStore;

    public ReportStatusClient(Context context) {
        credentialStore = new CredentialStore(context);
    }

    public TodayReportStatus fetchTodayStatus() {
        String reminderToken = credentialStore.getReminderToken();
        if (reminderToken != null && !reminderToken.trim().isEmpty()) {
            TodayReportStatus tokenStatus = fetchStatusWithToken(reminderToken.trim());
            if (tokenStatus.available && tokenStatus.authenticated) {
                return tokenStatus;
            }
        }

        return fetchStatusWithSession();
    }

    public boolean refreshReminderToken() {
        return refreshTokenFromSession(
                TOKEN_URL,
                credentialStore.getReminderToken(),
                token -> credentialStore.saveReminderToken(token)
        );
    }

    public boolean refreshAppLoginToken() {
        return refreshTokenFromSession(
                APP_LOGIN_TOKEN_URL,
                credentialStore.getAppLoginToken(),
                token -> credentialStore.saveAppLoginToken(token)
        );
    }

    private boolean refreshTokenFromSession(String endpointUrl, String existingToken, TokenSaver saver) {
        HttpURLConnection connection = null;

        try {
            CookieManager cookieManager = CookieManager.getInstance();
            cookieManager.flush();
            String cookie = cookieManager.getCookie(endpointUrl);
            if (cookie == null || cookie.trim().isEmpty()) {
                cookie = cookieManager.getCookie(HOME_URL);
            }

            if (cookie == null || cookie.trim().isEmpty()) {
                return false;
            }

            String tokenQuery = existingToken == null || existingToken.trim().isEmpty()
                    ? ""
                    : "?current_token=" + URLEncoder.encode(existingToken.trim(), "UTF-8");
            URL url = new URL(endpointUrl + tokenQuery);
            connection = (HttpURLConnection) url.openConnection();
            connection.setInstanceFollowRedirects(false);
            connection.setConnectTimeout(10000);
            connection.setReadTimeout(10000);
            connection.setRequestMethod("GET");
            connection.setRequestProperty("Accept", "application/json");
            connection.setRequestProperty("X-Requested-With", "XMLHttpRequest");
            connection.setRequestProperty("Cookie", cookie);

            int responseCode = connection.getResponseCode();
            if (responseCode != HttpURLConnection.HTTP_OK) {
                return false;
            }

            String body = readStream(connection.getInputStream());
            JSONObject json = new JSONObject(body);

            if (!json.optBoolean("authenticated", false)) {
                return false;
            }

            String token = json.optString("token", "");
            if (token.trim().isEmpty()) {
                return false;
            }

            saver.save(token);
            return true;
        } catch (Exception ignored) {
            return false;
        } finally {
            if (connection != null) {
                connection.disconnect();
            }
        }
    }

    private interface TokenSaver {
        void save(String token);
    }

    private TodayReportStatus fetchStatusWithToken(String reminderToken) {
        HttpURLConnection connection = null;

        try {
            String encodedToken = URLEncoder.encode(reminderToken, "UTF-8");
            URL url = new URL(TOKEN_STATUS_URL + "?token=" + encodedToken);
            connection = (HttpURLConnection) url.openConnection();
            connection.setInstanceFollowRedirects(false);
            connection.setConnectTimeout(10000);
            connection.setReadTimeout(10000);
            connection.setRequestMethod("GET");
            connection.setRequestProperty("Accept", "application/json");
            connection.setRequestProperty("X-Requested-With", "XMLHttpRequest");

            int responseCode = connection.getResponseCode();
            if (responseCode == HttpURLConnection.HTTP_UNAUTHORIZED || responseCode == HttpURLConnection.HTTP_FORBIDDEN) {
                credentialStore.clearReminderToken();
                return TodayReportStatus.notAuthenticated();
            }
            if (responseCode != HttpURLConnection.HTTP_OK) {
                return TodayReportStatus.notAvailable();
            }

            return parseStatus(readStream(connection.getInputStream()));
        } catch (Exception ignored) {
            return TodayReportStatus.notAvailable();
        } finally {
            if (connection != null) {
                connection.disconnect();
            }
        }
    }

    private TodayReportStatus fetchStatusWithSession() {
        HttpURLConnection connection = null;

        try {
            CookieManager cookieManager = CookieManager.getInstance();
            cookieManager.flush();
            String cookie = cookieManager.getCookie(STATUS_URL);
            if (cookie == null || cookie.trim().isEmpty()) {
                cookie = cookieManager.getCookie(HOME_URL);
            }

            if (cookie == null || cookie.trim().isEmpty()) {
                return TodayReportStatus.notAuthenticated();
            }

            URL url = new URL(STATUS_URL);
            connection = (HttpURLConnection) url.openConnection();
            connection.setInstanceFollowRedirects(false);
            connection.setConnectTimeout(10000);
            connection.setReadTimeout(10000);
            connection.setRequestMethod("GET");
            connection.setRequestProperty("Accept", "application/json");
            connection.setRequestProperty("X-Requested-With", "XMLHttpRequest");
            connection.setRequestProperty("Cookie", cookie);

            int responseCode = connection.getResponseCode();
            if (responseCode != HttpURLConnection.HTTP_OK) {
                return TodayReportStatus.notAvailable();
            }

            return parseStatus(readStream(connection.getInputStream()));
        } catch (Exception ignored) {
            return TodayReportStatus.notAvailable();
        } finally {
            if (connection != null) {
                connection.disconnect();
            }
        }
    }

    private TodayReportStatus parseStatus(String body) throws Exception {
        JSONObject json = new JSONObject(body);

        boolean authenticated = json.optBoolean("authenticated", false);
        if (!authenticated) {
            return TodayReportStatus.notAuthenticated();
        }

        return new TodayReportStatus(
                true,
                true,
                json.optBoolean("sudah_lapor", true),
                json.optString("nama", "")
        );
    }

    private static String readStream(InputStream stream) throws Exception {
        StringBuilder builder = new StringBuilder();
        BufferedReader reader = new BufferedReader(new InputStreamReader(stream, StandardCharsets.UTF_8));
        String line;

        while ((line = reader.readLine()) != null) {
            builder.append(line);
        }

        return builder.toString();
    }

    public static final class TodayReportStatus {
        public final boolean available;
        public final boolean authenticated;
        public final boolean submitted;
        public final String name;

        TodayReportStatus(boolean available, boolean authenticated, boolean submitted, String name) {
            this.available = available;
            this.authenticated = authenticated;
            this.submitted = submitted;
            this.name = name;
        }

        static TodayReportStatus notAuthenticated() {
            return new TodayReportStatus(true, false, true, "");
        }

        static TodayReportStatus notAvailable() {
            return new TodayReportStatus(false, false, true, "");
        }
    }
}
