package matsantura.ampuh;

import android.Manifest;
import android.app.Activity;
import android.app.AlertDialog;
import android.app.DownloadManager;
import android.content.ClipData;
import android.content.ActivityNotFoundException;
import android.content.Context;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.content.res.ColorStateList;
import android.graphics.Color;
import android.graphics.Typeface;
import android.graphics.drawable.GradientDrawable;
import android.net.ConnectivityManager;
import android.net.NetworkInfo;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Environment;
import android.os.StrictMode;
import android.provider.MediaStore;
import android.view.Gravity;
import android.view.MotionEvent;
import android.view.View;
import android.webkit.CookieManager;
import android.webkit.DownloadListener;
import android.webkit.PermissionRequest;
import android.webkit.URLUtil;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebResourceResponse;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Button;
import android.widget.FrameLayout;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import java.io.File;
import java.io.IOException;
import java.net.URLEncoder;

public class MainActivity extends Activity {
    private static final String HOME_URL = "https://ampuh.mtsn1jepara.sch.id";
    private static final String DEFAULT_ENTRY_URL = HOME_URL + "/pegawai/dashboard";
    private static final String APP_LOGIN_URL = HOME_URL + "/app-login";
    private static final int FILE_CHOOSER_REQUEST_CODE = 1001;
    private static final int CAMERA_PERMISSION_REQUEST_CODE = 1002;
    private static final int NOTIFICATION_PERMISSION_REQUEST_CODE = 1003;
    private static final int BRAND_GREEN = Color.rgb(0, 132, 61);
    private static final int BRAND_GREEN_DARK = Color.rgb(0, 103, 48);
    private static final int PROGRESS_YELLOW = Color.rgb(246, 194, 62);
    private static final int VIDEO_CAPTURE_DURATION_LIMIT_SECONDS = 15;

    private FrameLayout root;
    private WebView webView;
    private ProgressBar progressBar;
    private FrameLayout splashOverlay;
    private FrameLayout errorOverlay;
    private TextView errorTitle;
    private TextView errorMessage;
    private TextView pullRefreshIndicator;
    private ValueCallback<Uri[]> filePathCallback;
    private LoginCredentialBridge loginCredentialBridge;
    private Uri cameraImageUri;
    private PermissionRequest pendingPermissionRequest;
    private boolean pageLoadErrored;
    private boolean isRefreshing;
    private float pullStartY = -1f;
    private long lastBackPressedAt;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        configureSystemBars();

        root = new FrameLayout(this);
        webView = new WebView(this);
        progressBar = new ProgressBar(this, null, android.R.attr.progressBarStyleHorizontal);
        progressBar.setMax(100);
        tintProgressBar();

        root.addView(webView, new FrameLayout.LayoutParams(
                FrameLayout.LayoutParams.MATCH_PARENT,
                FrameLayout.LayoutParams.MATCH_PARENT
        ));

        pullRefreshIndicator = createPullRefreshIndicator();
        FrameLayout.LayoutParams pullParams = new FrameLayout.LayoutParams(
                FrameLayout.LayoutParams.WRAP_CONTENT,
                FrameLayout.LayoutParams.WRAP_CONTENT
        );
        pullParams.gravity = Gravity.TOP | Gravity.CENTER_HORIZONTAL;
        pullParams.topMargin = dp(12);
        root.addView(pullRefreshIndicator, pullParams);

        FrameLayout.LayoutParams progressParams = new FrameLayout.LayoutParams(
                FrameLayout.LayoutParams.MATCH_PARENT,
                dp(3)
        );
        progressParams.gravity = Gravity.TOP;
        root.addView(progressBar, progressParams);

        splashOverlay = createSplashOverlay();
        root.addView(splashOverlay, new FrameLayout.LayoutParams(
                FrameLayout.LayoutParams.MATCH_PARENT,
                FrameLayout.LayoutParams.MATCH_PARENT
        ));

        errorOverlay = createErrorOverlay();
        root.addView(errorOverlay, new FrameLayout.LayoutParams(
                FrameLayout.LayoutParams.MATCH_PARENT,
                FrameLayout.LayoutParams.MATCH_PARENT
        ));

        setContentView(root);

        StrictMode.setVmPolicy(new StrictMode.VmPolicy.Builder().build());
        loginCredentialBridge = new LoginCredentialBridge(new CredentialStore(this));
        cleanupOldCaptureFiles();
        configureWebView();
        if (!requestCameraPermissionIfNeeded()) {
            requestNotificationPermissionIfNeeded();
        }
        ReminderScheduler.scheduleNext(this);

        if (savedInstanceState != null) {
            splashOverlay.setVisibility(View.GONE);
            webView.restoreState(savedInstanceState);
        } else {
            webView.loadUrl(initialEntryUrl());
        }
    }

    private void configureSystemBars() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
            getWindow().setStatusBarColor(BRAND_GREEN_DARK);
            getWindow().setNavigationBarColor(Color.WHITE);
        }

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            getWindow().getDecorView().setSystemUiVisibility(View.SYSTEM_UI_FLAG_LIGHT_NAVIGATION_BAR);
        }
    }

    private void tintProgressBar() {
        progressBar.setProgressTintList(ColorStateList.valueOf(PROGRESS_YELLOW));
        progressBar.setProgressBackgroundTintList(ColorStateList.valueOf(Color.TRANSPARENT));
        progressBar.setIndeterminateTintList(ColorStateList.valueOf(PROGRESS_YELLOW));
        progressBar.setVisibility(View.GONE);
    }

    private void configureWebView() {
        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setDatabaseEnabled(true);
        settings.setSaveFormData(true);
        settings.setLoadWithOverviewMode(true);
        settings.setUseWideViewPort(true);
        settings.setMediaPlaybackRequiresUserGesture(false);
        settings.setAllowFileAccess(true);
        settings.setAllowContentAccess(true);
        settings.setCacheMode(WebSettings.LOAD_DEFAULT);

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
            settings.setMixedContentMode(WebSettings.MIXED_CONTENT_COMPATIBILITY_MODE);
            CookieManager.getInstance().setAcceptThirdPartyCookies(webView, true);
        }

        CookieManager.getInstance().setAcceptCookie(true);

        webView.setBackgroundColor(Color.WHITE);
        webView.setWebViewClient(new AmpuhWebViewClient());
        webView.setWebChromeClient(new AmpuhWebChromeClient());
        webView.setDownloadListener(new AmpuhDownloadListener());
        webView.setOnTouchListener(new PullToRefreshTouchListener());
        webView.addJavascriptInterface(loginCredentialBridge, "AmpuhCredentialBridge");
    }

    private FrameLayout createSplashOverlay() {
        FrameLayout overlay = new FrameLayout(this);
        overlay.setBackgroundColor(Color.WHITE);
        overlay.setClickable(true);

        LinearLayout content = new LinearLayout(this);
        content.setOrientation(LinearLayout.VERTICAL);
        content.setGravity(Gravity.CENTER);
        content.setPadding(dp(28), dp(28), dp(28), dp(28));

        ImageView icon = new ImageView(this);
        icon.setImageResource(getResources().getIdentifier("ic_launcher", "mipmap", getPackageName()));
        LinearLayout.LayoutParams iconParams = new LinearLayout.LayoutParams(dp(84), dp(84));
        iconParams.bottomMargin = dp(18);
        content.addView(icon, iconParams);

        TextView title = new TextView(this);
        title.setText("AMPUH");
        title.setTextColor(BRAND_GREEN_DARK);
        title.setTextSize(26);
        title.setTypeface(Typeface.DEFAULT_BOLD);
        title.setGravity(Gravity.CENTER);
        content.addView(title);

        TextView subtitle = new TextView(this);
        subtitle.setText("Memuat aplikasi...");
        subtitle.setTextColor(Color.rgb(115, 119, 138));
        subtitle.setTextSize(14);
        subtitle.setGravity(Gravity.CENTER);
        LinearLayout.LayoutParams subtitleParams = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.WRAP_CONTENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
        );
        subtitleParams.topMargin = dp(6);
        content.addView(subtitle, subtitleParams);

        ProgressBar spinner = new ProgressBar(this);
        spinner.setIndeterminateTintList(ColorStateList.valueOf(PROGRESS_YELLOW));
        LinearLayout.LayoutParams spinnerParams = new LinearLayout.LayoutParams(dp(36), dp(36));
        spinnerParams.topMargin = dp(22);
        content.addView(spinner, spinnerParams);

        overlay.addView(content, centeredParams());
        return overlay;
    }

    private FrameLayout createErrorOverlay() {
        FrameLayout overlay = new FrameLayout(this);
        overlay.setBackgroundColor(Color.WHITE);
        overlay.setVisibility(View.GONE);
        overlay.setClickable(true);

        LinearLayout content = new LinearLayout(this);
        content.setOrientation(LinearLayout.VERTICAL);
        content.setGravity(Gravity.CENTER);
        content.setPadding(dp(30), dp(30), dp(30), dp(30));

        TextView icon = new TextView(this);
        icon.setText("!");
        icon.setTextSize(28);
        icon.setTextColor(Color.WHITE);
        icon.setTypeface(Typeface.DEFAULT_BOLD);
        icon.setGravity(Gravity.CENTER);
        icon.setBackground(roundDrawable(PROGRESS_YELLOW, dp(42)));
        LinearLayout.LayoutParams iconParams = new LinearLayout.LayoutParams(dp(54), dp(54));
        iconParams.bottomMargin = dp(18);
        content.addView(icon, iconParams);

        errorTitle = new TextView(this);
        errorTitle.setTextColor(Color.rgb(31, 41, 55));
        errorTitle.setTextSize(22);
        errorTitle.setTypeface(Typeface.DEFAULT_BOLD);
        errorTitle.setGravity(Gravity.CENTER);
        content.addView(errorTitle);

        errorMessage = new TextView(this);
        errorMessage.setTextColor(Color.rgb(107, 114, 128));
        errorMessage.setTextSize(14);
        errorMessage.setGravity(Gravity.CENTER);
        errorMessage.setLineSpacing(2f, 1.1f);
        LinearLayout.LayoutParams messageParams = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
        );
        messageParams.topMargin = dp(10);
        messageParams.bottomMargin = dp(22);
        content.addView(errorMessage, messageParams);

        Button retryButton = new Button(this);
        retryButton.setText("Coba Lagi");
        retryButton.setAllCaps(false);
        retryButton.setTextColor(Color.WHITE);
        retryButton.setBackground(roundDrawable(BRAND_GREEN, dp(8)));
        retryButton.setOnClickListener(v -> reloadCurrentPage());
        content.addView(retryButton, buttonParams());

        Button browserButton = new Button(this);
        browserButton.setText("Buka di Browser");
        browserButton.setAllCaps(false);
        browserButton.setTextColor(BRAND_GREEN_DARK);
        browserButton.setBackground(roundStrokeDrawable(Color.WHITE, BRAND_GREEN, dp(8)));
        browserButton.setOnClickListener(v -> openCurrentPageInBrowser());
        LinearLayout.LayoutParams browserParams = buttonParams();
        browserParams.topMargin = dp(10);
        content.addView(browserButton, browserParams);

        overlay.addView(content, centeredParams());
        return overlay;
    }

    private TextView createPullRefreshIndicator() {
        TextView indicator = new TextView(this);
        indicator.setText("Tarik untuk refresh");
        indicator.setTextSize(13);
        indicator.setTextColor(Color.rgb(55, 65, 81));
        indicator.setGravity(Gravity.CENTER);
        indicator.setPadding(dp(14), dp(8), dp(14), dp(8));
        indicator.setBackground(roundStrokeDrawable(Color.WHITE, PROGRESS_YELLOW, dp(20)));
        indicator.setElevation(dp(4));
        indicator.setAlpha(0f);
        indicator.setVisibility(View.GONE);
        return indicator;
    }

    private FrameLayout.LayoutParams centeredParams() {
        FrameLayout.LayoutParams params = new FrameLayout.LayoutParams(
                FrameLayout.LayoutParams.MATCH_PARENT,
                FrameLayout.LayoutParams.WRAP_CONTENT
        );
        params.gravity = Gravity.CENTER;
        return params;
    }

    private LinearLayout.LayoutParams buttonParams() {
        return new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                dp(46)
        );
    }

    private GradientDrawable roundDrawable(int color, int radius) {
        GradientDrawable drawable = new GradientDrawable();
        drawable.setColor(color);
        drawable.setCornerRadius(radius);
        return drawable;
    }

    private GradientDrawable roundStrokeDrawable(int fillColor, int strokeColor, int radius) {
        GradientDrawable drawable = roundDrawable(fillColor, radius);
        drawable.setStroke(dp(1), strokeColor);
        return drawable;
    }

    private void showProgress(int value) {
        progressBar.setVisibility(View.VISIBLE);
        progressBar.setProgress(Math.max(5, Math.min(100, value)));
    }

    private void finishProgress() {
        progressBar.setProgress(100);
        progressBar.postDelayed(() -> progressBar.setVisibility(View.GONE), 250);
    }

    private void hideSplash() {
        if (splashOverlay.getVisibility() == View.VISIBLE) {
            splashOverlay.animate().alpha(0f).setDuration(220).withEndAction(() -> {
                splashOverlay.setVisibility(View.GONE);
                splashOverlay.setAlpha(1f);
            }).start();
        }
    }

    private void showErrorOverlay(String title, String message) {
        pageLoadErrored = true;
        stopRefreshing();
        hideSplash();
        progressBar.setVisibility(View.GONE);
        errorTitle.setText(title);
        errorMessage.setText(message);
        errorOverlay.setAlpha(0f);
        errorOverlay.setVisibility(View.VISIBLE);
        errorOverlay.animate().alpha(1f).setDuration(180).start();
    }

    private void hideErrorOverlay() {
        errorOverlay.setVisibility(View.GONE);
    }

    private void reloadCurrentPage() {
        hideErrorOverlay();
        pageLoadErrored = false;
        showProgress(10);
        if (webView.getUrl() == null) {
            webView.loadUrl(initialEntryUrl());
        } else {
            webView.reload();
        }
    }

    private void refreshDeviceTokensAndCheck(boolean allowAppLoginToken) {
        Context appContext = getApplicationContext();
        new Thread(() -> {
            ReportStatusClient client = new ReportStatusClient(appContext);
            client.refreshReminderToken();
            CredentialStore credentialStore = new CredentialStore(appContext);
            if (allowAppLoginToken || credentialStore.hasAppLoginToken()) {
                client.refreshAppLoginToken();
            }
            ReportReminderReceiver.checkAndNotifyAsync(appContext);
        }).start();
    }

    private String initialEntryUrl() {
        String token = new CredentialStore(this).getAppLoginToken();
        if (token != null && !token.trim().isEmpty()) {
            try {
                return APP_LOGIN_URL + "?token=" + URLEncoder.encode(token.trim(), "UTF-8");
            } catch (Exception ignored) {
                return APP_LOGIN_URL;
            }
        }

        return DEFAULT_ENTRY_URL;
    }

    private void openCurrentPageInBrowser() {
        String url = webView.getUrl() == null ? HOME_URL : webView.getUrl();
        try {
            startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse(url)));
        } catch (ActivityNotFoundException ignored) {
            Toast.makeText(this, "Tidak ada aplikasi browser yang tersedia.", Toast.LENGTH_SHORT).show();
        }
    }

    private boolean isNetworkAvailable() {
        ConnectivityManager manager = (ConnectivityManager) getSystemService(Context.CONNECTIVITY_SERVICE);
        if (manager == null) {
            return false;
        }

        NetworkInfo networkInfo = manager.getActiveNetworkInfo();
        return networkInfo != null && networkInfo.isConnected();
    }

    private void updatePullRefresh(float distance) {
        if (isRefreshing || progressBar.getVisibility() == View.VISIBLE) {
            return;
        }

        int threshold = dp(88);
        float progress = Math.min(1f, distance / threshold);
        pullRefreshIndicator.setVisibility(View.VISIBLE);
        pullRefreshIndicator.setAlpha(progress);
        pullRefreshIndicator.setTranslationY(dp(18) * progress);
        pullRefreshIndicator.setText(progress >= 1f ? "Lepas untuk refresh" : "Tarik untuk refresh");
    }

    private void triggerPullRefresh() {
        isRefreshing = true;
        pullRefreshIndicator.setText("Memuat ulang...");
        pullRefreshIndicator.setAlpha(1f);
        showProgress(10);
        webView.reload();
    }

    private void stopRefreshing() {
        isRefreshing = false;
        pullStartY = -1f;
        pullRefreshIndicator.animate().alpha(0f).translationY(0f).setDuration(180).withEndAction(() -> {
            pullRefreshIndicator.setVisibility(View.GONE);
            pullRefreshIndicator.setText("Tarik untuk refresh");
        }).start();
    }

    private boolean requestCameraPermissionIfNeeded() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M
                && checkSelfPermission(Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
            requestPermissions(new String[]{Manifest.permission.CAMERA}, CAMERA_PERMISSION_REQUEST_CODE);
            return true;
        }

        return false;
    }

    private void requestNotificationPermissionIfNeeded() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU
                && checkSelfPermission(Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
            requestPermissions(new String[]{Manifest.permission.POST_NOTIFICATIONS}, NOTIFICATION_PERMISSION_REQUEST_CODE);
        }
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, String[] permissions, int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);

        if (requestCode == CAMERA_PERMISSION_REQUEST_CODE) {
            boolean granted = grantResults.length > 0 && grantResults[0] == PackageManager.PERMISSION_GRANTED;
            if (pendingPermissionRequest != null) {
                if (granted) {
                    pendingPermissionRequest.grant(pendingPermissionRequest.getResources());
                } else {
                    pendingPermissionRequest.deny();
                    Toast.makeText(this, "Izin kamera diperlukan untuk mengambil foto atau video bukti.", Toast.LENGTH_LONG).show();
                }
                pendingPermissionRequest = null;
            } else if (!granted) {
                Toast.makeText(this, "Izin kamera dapat diaktifkan nanti jika ingin mengambil foto atau video langsung.", Toast.LENGTH_LONG).show();
            }

            requestNotificationPermissionIfNeeded();
        } else if (requestCode == NOTIFICATION_PERMISSION_REQUEST_CODE) {
            boolean granted = grantResults.length > 0 && grantResults[0] == PackageManager.PERMISSION_GRANTED;
            if (!granted) {
                Toast.makeText(this, "Notifikasi pengingat laporan belum aktif.", Toast.LENGTH_LONG).show();
            }
        }
    }

    @Override
    protected void onSaveInstanceState(Bundle outState) {
        super.onSaveInstanceState(outState);
        webView.saveState(outState);
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);

        if (requestCode != FILE_CHOOSER_REQUEST_CODE || filePathCallback == null) {
            return;
        }

        Uri[] results = null;
        boolean usedCameraResult = false;
        if (resultCode == Activity.RESULT_OK) {
            if (cameraImageUri != null && hasContent(cameraImageUri)) {
                results = new Uri[]{cameraImageUri};
                usedCameraResult = true;
            } else if (data == null || data.getData() == null) {
                if (cameraImageUri != null) {
                    results = new Uri[]{cameraImageUri};
                    usedCameraResult = true;
                }
            } else if (data.getClipData() != null) {
                int count = data.getClipData().getItemCount();
                results = new Uri[count];
                for (int i = 0; i < count; i++) {
                    results[i] = data.getClipData().getItemAt(i).getUri();
                }
            } else {
                results = new Uri[]{data.getData()};
            }
        }

        if (cameraImageUri != null && !usedCameraResult) {
            deleteCameraImageUri(cameraImageUri);
        }

        filePathCallback.onReceiveValue(results);
        filePathCallback = null;
        cameraImageUri = null;
    }

    @Override
    public void onBackPressed() {
        if (errorOverlay.getVisibility() == View.VISIBLE) {
            hideErrorOverlay();
            return;
        }

        if (webView.canGoBack()) {
            webView.goBack();
            return;
        }

        long now = System.currentTimeMillis();
        if (now - lastBackPressedAt < 1800) {
            super.onBackPressed();
            return;
        }

        lastBackPressedAt = now;
        Toast.makeText(this, "Tekan sekali lagi untuk keluar.", Toast.LENGTH_SHORT).show();
    }

    private Intent createFilePickerIntent() {
        Intent contentIntent = new Intent(Intent.ACTION_GET_CONTENT);
        contentIntent.addCategory(Intent.CATEGORY_OPENABLE);
        contentIntent.setType("*/*");
        contentIntent.putExtra(Intent.EXTRA_MIME_TYPES, new String[]{
                "image/*",
                "application/pdf",
                "video/*"
        });
        contentIntent.putExtra(Intent.EXTRA_ALLOW_MULTIPLE, false);

        return Intent.createChooser(contentIntent, "Ambil Berkas");
    }

    private void showEvidencePickerDialog() {
        LinearLayout options = new LinearLayout(this);
        options.setOrientation(LinearLayout.VERTICAL);
        options.setPadding(0, dp(8), 0, dp(8));

        final AlertDialog[] dialogHolder = new AlertDialog[1];

        options.addView(createEvidenceOption("Ambil Gambar", "ic_evidence_camera", v -> {
            dialogHolder[0].dismiss();
            launchEvidenceIntent(createCameraIntent(), "Kamera tidak tersedia.");
        }));

        options.addView(createEvidenceOption("Ambil Video", "ic_evidence_video", v -> {
            dialogHolder[0].dismiss();
            launchEvidenceIntent(createVideoCaptureIntent(), "Perekam video tidak tersedia.");
        }));

        options.addView(createEvidenceOption("Ambil Berkas", "ic_evidence_file", v -> {
            dialogHolder[0].dismiss();
            launchEvidenceIntent(createFilePickerIntent(), "Pemilih file tidak tersedia.");
        }));

        AlertDialog dialog = new AlertDialog.Builder(this)
                .setTitle("Pilih Bukti")
                .setView(options)
                .setOnCancelListener(d -> cancelFileChooser())
                .create();

        dialogHolder[0] = dialog;
        dialog.show();
    }

    private View createEvidenceOption(String label, String iconName, View.OnClickListener listener) {
        LinearLayout row = new LinearLayout(this);
        row.setOrientation(LinearLayout.HORIZONTAL);
        row.setGravity(Gravity.CENTER_VERTICAL);
        row.setPadding(dp(20), dp(14), dp(20), dp(14));
        row.setClickable(true);
        row.setOnClickListener(listener);

        ImageView icon = new ImageView(this);
        int iconResource = getResources().getIdentifier(iconName, "drawable", getPackageName());
        if (iconResource != 0) {
            icon.setImageResource(iconResource);
        }
        LinearLayout.LayoutParams iconParams = new LinearLayout.LayoutParams(dp(28), dp(28));
        iconParams.rightMargin = dp(14);
        row.addView(icon, iconParams);

        TextView text = new TextView(this);
        text.setText(label);
        text.setTextColor(Color.rgb(31, 41, 55));
        text.setTextSize(16);
        text.setTypeface(Typeface.DEFAULT_BOLD);
        row.addView(text, new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.WRAP_CONTENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
        ));

        return row;
    }

    private void launchEvidenceIntent(Intent intent, String unavailableMessage) {
        if (intent == null) {
            Toast.makeText(this, unavailableMessage, Toast.LENGTH_SHORT).show();
            cancelFileChooser();
            return;
        }

        try {
            startActivityForResult(intent, FILE_CHOOSER_REQUEST_CODE);
        } catch (ActivityNotFoundException e) {
            if (cameraImageUri != null) {
                deleteCameraImageUri(cameraImageUri);
                cameraImageUri = null;
            }

            Toast.makeText(this, unavailableMessage, Toast.LENGTH_SHORT).show();
            cancelFileChooser();
        }
    }

    private void cancelFileChooser() {
        if (cameraImageUri != null) {
            deleteCameraImageUri(cameraImageUri);
            cameraImageUri = null;
        }

        if (filePathCallback != null) {
            filePathCallback.onReceiveValue(null);
            filePathCallback = null;
        }
    }

    private Intent createCameraIntent() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M
                && checkSelfPermission(Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
            Toast.makeText(this, "Aktifkan izin kamera untuk mengambil foto atau video langsung.", Toast.LENGTH_SHORT).show();
            return null;
        }

        Intent cameraIntent = new Intent(MediaStore.ACTION_IMAGE_CAPTURE);
        if (cameraIntent.resolveActivity(getPackageManager()) == null) {
            return null;
        }

        cameraImageUri = createCaptureUri("jpg");
        if (cameraImageUri == null) {
            cameraImageUri = null;
            return null;
        }

        cameraIntent.putExtra(MediaStore.EXTRA_OUTPUT, cameraImageUri);
        cameraIntent.setClipData(ClipData.newUri(getContentResolver(), "Ambil Gambar", cameraImageUri));
        cameraIntent.addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION | Intent.FLAG_GRANT_WRITE_URI_PERMISSION);
        return cameraIntent;
    }

    private Intent createVideoCaptureIntent() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M
                && checkSelfPermission(Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
            return null;
        }

        Intent videoIntent = new Intent(MediaStore.ACTION_VIDEO_CAPTURE);
        if (videoIntent.resolveActivity(getPackageManager()) == null) {
            return null;
        }

        cameraImageUri = createCaptureUri("mp4");
        if (cameraImageUri != null) {
            videoIntent.putExtra(MediaStore.EXTRA_OUTPUT, cameraImageUri);
            videoIntent.setClipData(ClipData.newUri(getContentResolver(), "Ambil Video", cameraImageUri));
        }

        videoIntent.putExtra(MediaStore.EXTRA_DURATION_LIMIT, VIDEO_CAPTURE_DURATION_LIMIT_SECONDS);
        videoIntent.addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION | Intent.FLAG_GRANT_WRITE_URI_PERMISSION);
        return videoIntent;
    }

    private Uri createCaptureUri(String extension) {
        try {
            File dir = EvidenceFileProvider.captureDirectory(this);
            if (!dir.exists() && !dir.mkdirs()) {
                return null;
            }

            File file = File.createTempFile("ampuh_upload_", "." + extension, dir);
            return EvidenceFileProvider.uriForFile(this, file);
        } catch (IOException ignored) {
            return null;
        }
    }

    private boolean hasContent(Uri uri) {
        try {
            android.content.res.AssetFileDescriptor descriptor = getContentResolver().openAssetFileDescriptor(uri, "r");
            if (descriptor == null) {
                return false;
            }
            long length = descriptor.getLength();
            descriptor.close();
            return length != 0;
        } catch (Exception ignored) {
            return false;
        }
    }

    private void deleteCameraImageUri(Uri uri) {
        try {
            getContentResolver().delete(uri, null, null);
        } catch (Exception ignored) {
        }
    }

    private void cleanupOldCaptureFiles() {
        File dir = EvidenceFileProvider.captureDirectory(this);
        File[] files = dir.listFiles();
        if (files == null) {
            return;
        }

        long cutoff = System.currentTimeMillis() - (2L * 24L * 60L * 60L * 1000L);
        for (File file : files) {
            if (file.isFile() && file.lastModified() < cutoff) {
                //noinspection ResultOfMethodCallIgnored
                file.delete();
            }
        }
    }

    private boolean isAmpuhUrl(Uri uri) {
        String host = uri.getHost();
        return host != null && (host.equals("ampuh.mtsn1jepara.sch.id") || host.endsWith(".mtsn1jepara.sch.id"));
    }

    private boolean isLoginUrl(String url) {
        if (url == null) {
            return false;
        }

        Uri uri = Uri.parse(url);
        return isAmpuhUrl(uri) && "/login".equals(uri.getPath());
    }

    private boolean isLogoutUrl(Uri uri) {
        return isAmpuhUrl(uri) && "/logout".equals(uri.getPath());
    }

    private boolean isAuthenticatedLandingUrl(String url) {
        if (url == null) {
            return false;
        }

        Uri uri = Uri.parse(url);
        if (!isAmpuhUrl(uri)) {
            return false;
        }

        String path = uri.getPath();
        return "/pegawai/dashboard".equals(path) || "/admin/dashboard".equals(path);
    }

    private boolean isPegawaiLandingUrl(String url) {
        if (url == null) {
            return false;
        }

        Uri uri = Uri.parse(url);
        return isAmpuhUrl(uri) && "/pegawai/dashboard".equals(uri.getPath());
    }

    private void injectCredentialAutofill() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.KITKAT) {
            return;
        }

        webView.evaluateJavascript("""
                (function () {
                  if (window.__ampuhCredentialInjected || !window.AmpuhCredentialBridge) {
                    return;
                  }

                  var form = document.querySelector('form[action="/login"]');
                  var identifier = document.querySelector('input[name="identifier"]');
                  var password = document.querySelector('input[name="password"]');
                  var submitButton = form ? form.querySelector('button[type="submit"]') : null;

                  if (!form || !identifier || !password || !submitButton) {
                    return;
                  }

                  window.__ampuhCredentialInjected = true;

                  var rememberWrap = document.createElement('label');
                  rememberWrap.style.display = 'flex';
                  rememberWrap.style.alignItems = 'center';
                  rememberWrap.style.gap = '8px';
                  rememberWrap.style.margin = '0 0 16px 2px';
                  rememberWrap.style.fontSize = '13px';
                  rememberWrap.style.color = '#5f6478';
                  rememberWrap.style.cursor = 'pointer';

                  var checkbox = document.createElement('input');
                  checkbox.type = 'checkbox';
                  checkbox.id = 'ampuhRememberAccount';
                  checkbox.style.width = '16px';
                  checkbox.style.height = '16px';
                  checkbox.style.accentColor = '#00843d';

                  var labelText = document.createElement('span');
                  labelText.textContent = 'Ingat akun di aplikasi';

                  rememberWrap.appendChild(checkbox);
                  rememberWrap.appendChild(labelText);
                  submitButton.parentNode.insertBefore(rememberWrap, submitButton);

                  try {
                    var raw = window.AmpuhCredentialBridge.getCredentials();
                    var saved = raw ? JSON.parse(raw) : {};
                    if (saved.identifier && saved.password) {
                      identifier.value = saved.identifier;
                      password.value = saved.password;
                      checkbox.checked = true;
                    } else {
                      checkbox.checked = true;
                    }
                  } catch (error) {}

                  form.addEventListener('submit', function () {
                    try {
                      window.AmpuhCredentialBridge.prepareLogin(
                        identifier.value || '',
                        password.value || '',
                        checkbox.checked
                      );
                    } catch (error) {}
                  });
                })();
                """, null);
    }

    private int dp(int value) {
        return (int) (value * getResources().getDisplayMetrics().density + 0.5f);
    }

    private class PullToRefreshTouchListener implements View.OnTouchListener {
        @Override
        public boolean onTouch(View view, MotionEvent event) {
            if (webView.getScrollY() > 0 || errorOverlay.getVisibility() == View.VISIBLE) {
                pullStartY = -1f;
                return false;
            }

            switch (event.getActionMasked()) {
                case MotionEvent.ACTION_DOWN:
                    pullStartY = event.getY();
                    break;
                case MotionEvent.ACTION_MOVE:
                    if (pullStartY >= 0f) {
                        float distance = event.getY() - pullStartY;
                        if (distance > dp(12)) {
                            updatePullRefresh(distance);
                        }
                    }
                    break;
                case MotionEvent.ACTION_UP:
                case MotionEvent.ACTION_CANCEL:
                    if (pullStartY >= 0f) {
                        float distance = event.getY() - pullStartY;
                        if (distance >= dp(88) && !isRefreshing && progressBar.getVisibility() != View.VISIBLE) {
                            triggerPullRefresh();
                        } else {
                            stopRefreshing();
                        }
                    }
                    break;
                default:
                    break;
            }

            return false;
        }
    }

    private class AmpuhWebViewClient extends WebViewClient {
        @Override
        public void onPageStarted(WebView view, String url, android.graphics.Bitmap favicon) {
            super.onPageStarted(view, url, favicon);
            pageLoadErrored = false;
            hideErrorOverlay();
            showProgress(10);
        }

        @Override
        public void onPageFinished(WebView view, String url) {
            super.onPageFinished(view, url);
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                CookieManager.getInstance().flush();
            }

            if (isAuthenticatedLandingUrl(url)) {
                boolean rememberedLogin = loginCredentialBridge.savePendingIfRequested();
                ReminderScheduler.scheduleNext(MainActivity.this);
                if (isPegawaiLandingUrl(url)) {
                    refreshDeviceTokensAndCheck(rememberedLogin || loginCredentialBridge.shouldRememberDeviceLogin());
                }
            } else if (isLoginUrl(url)) {
                injectCredentialAutofill();
            }

            if (!pageLoadErrored) {
                hideSplash();
                finishProgress();
            }
            stopRefreshing();
        }

        @Override
        public void onReceivedError(WebView view, WebResourceRequest request, WebResourceError error) {
            super.onReceivedError(view, request, error);

            if (!request.isForMainFrame()) {
                return;
            }

            String message = isNetworkAvailable()
                    ? "Halaman tidak dapat dimuat. Server mungkin sedang sibuk atau tautan tidak tersedia."
                    : "Koneksi internet tidak tersedia. Periksa jaringan lalu coba lagi.";
            showErrorOverlay("Koneksi Bermasalah", message);
        }

        @Override
        public void onReceivedHttpError(WebView view, WebResourceRequest request, WebResourceResponse errorResponse) {
            super.onReceivedHttpError(view, request, errorResponse);

            if (!request.isForMainFrame()) {
                return;
            }

            int statusCode = errorResponse != null ? errorResponse.getStatusCode() : 0;
            if (statusCode >= 500) {
                showErrorOverlay("Server Bermasalah", "Server AMPUH belum merespons dengan baik. Silakan coba beberapa saat lagi.");
            }
        }

        @Override
        public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
            return handleUrl(request.getUrl());
        }

        @Override
        public boolean shouldOverrideUrlLoading(WebView view, String url) {
            return handleUrl(Uri.parse(url));
        }

        private boolean handleUrl(Uri uri) {
            String scheme = uri.getScheme();
            if (scheme == null || scheme.equals("http") || scheme.equals("https")) {
                if (isAmpuhUrl(uri)) {
                    if (isLogoutUrl(uri)) {
                        new CredentialStore(MainActivity.this).clearAll();
                    }
                    return false;
                }

                openExternal(uri);
                return true;
            }

            openExternal(uri);
            return true;
        }

        private void openExternal(Uri uri) {
            try {
                startActivity(new Intent(Intent.ACTION_VIEW, uri));
            } catch (ActivityNotFoundException ignored) {
                Toast.makeText(MainActivity.this, "Tidak ada aplikasi untuk membuka tautan ini.", Toast.LENGTH_SHORT).show();
            }
        }
    }

    private class AmpuhWebChromeClient extends WebChromeClient {
        @Override
        public void onProgressChanged(WebView view, int newProgress) {
            showProgress(newProgress);
            if (newProgress >= 100 && !pageLoadErrored) {
                finishProgress();
            }
        }

        @Override
        public boolean onShowFileChooser(WebView webView, ValueCallback<Uri[]> filePathCallback, FileChooserParams fileChooserParams) {
            if (MainActivity.this.filePathCallback != null) {
                MainActivity.this.filePathCallback.onReceiveValue(null);
            }

            MainActivity.this.filePathCallback = filePathCallback;
            showEvidencePickerDialog();
            return true;
        }

        @Override
        public void onPermissionRequest(PermissionRequest request) {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M
                    && checkSelfPermission(Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
                pendingPermissionRequest = request;
                requestPermissions(new String[]{Manifest.permission.CAMERA}, CAMERA_PERMISSION_REQUEST_CODE);
                return;
            }

            request.grant(request.getResources());
        }
    }

    private class AmpuhDownloadListener implements DownloadListener {
        @Override
        public void onDownloadStart(String url, String userAgent, String contentDisposition, String mimetype, long contentLength) {
            try {
                DownloadManager.Request request = new DownloadManager.Request(Uri.parse(url));
                request.setMimeType(mimetype);
                request.addRequestHeader("User-Agent", userAgent);
                request.addRequestHeader("Cookie", CookieManager.getInstance().getCookie(url));
                request.setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED);
                request.setDestinationInExternalPublicDir(
                        Environment.DIRECTORY_DOWNLOADS,
                        URLUtil.guessFileName(url, contentDisposition, mimetype)
                );

                DownloadManager manager = (DownloadManager) getSystemService(Context.DOWNLOAD_SERVICE);
                if (manager != null) {
                    manager.enqueue(request);
                    Toast.makeText(MainActivity.this, "Download dimulai. Cek folder Download.", Toast.LENGTH_SHORT).show();
                }
            } catch (Exception e) {
                openDownloadInBrowser(url);
            }
        }

        private void openDownloadInBrowser(String url) {
            try {
                startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse(url)));
            } catch (ActivityNotFoundException ignored) {
                Toast.makeText(MainActivity.this, "File tidak dapat diunduh.", Toast.LENGTH_SHORT).show();
            }
        }
    }
}
