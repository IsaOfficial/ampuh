package ampuh.mtsn1jepara;

import android.webkit.CookieManager;

import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;

public final class ReportStatusClient {
    private static final String HOME_URL = "https://ampuh.mtsn1jepara.sch.id";
    private static final String STATUS_URL = HOME_URL + "/pegawai/laporan/status-hari-ini";

    public TodayReportStatus fetchTodayStatus() {
        HttpURLConnection connection = null;

        try {
            String cookie = CookieManager.getInstance().getCookie(HOME_URL);
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

            String body = readStream(connection.getInputStream());
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
        } catch (Exception ignored) {
            return TodayReportStatus.notAvailable();
        } finally {
            if (connection != null) {
                connection.disconnect();
            }
        }
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
