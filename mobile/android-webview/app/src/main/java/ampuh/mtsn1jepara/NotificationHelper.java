package ampuh.mtsn1jepara;

import android.Manifest;
import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.graphics.Color;
import android.os.Build;

public final class NotificationHelper {
    private static final String CHANNEL_ID = "laporan_harian_reminder";
    private static final String CHANNEL_NAME = "Pengingat Laporan Harian";
    private static final int NOTIFICATION_ID = 11010;
    private static final int BRAND_GREEN = Color.rgb(0, 132, 61);

    private NotificationHelper() {
    }

    public static void showReportReminder(Context context, String employeeName) {
        if (!canPostNotifications(context)) {
            return;
        }

        NotificationManager manager = (NotificationManager) context.getSystemService(Context.NOTIFICATION_SERVICE);
        if (manager == null) {
            return;
        }

        createChannelIfNeeded(manager);

        Intent openAppIntent = new Intent(context, MainActivity.class);
        openAppIntent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TOP);

        int flags = PendingIntent.FLAG_UPDATE_CURRENT;
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            flags |= PendingIntent.FLAG_IMMUTABLE;
        }

        PendingIntent contentIntent = PendingIntent.getActivity(context, 0, openAppIntent, flags);

        String title = "AMPUH: laporan hari ini belum dikirim";
        String message = employeeName == null || employeeName.trim().isEmpty()
                ? "Silakan kirim laporan harian sebelum hari berakhir."
                : employeeName + ", silakan kirim laporan harian sebelum hari berakhir.";

        Notification.Builder builder = Build.VERSION.SDK_INT >= Build.VERSION_CODES.O
                ? new Notification.Builder(context, CHANNEL_ID)
                : new Notification.Builder(context);

        builder
                .setSmallIcon(android.R.drawable.ic_dialog_info)
                .setContentTitle(title)
                .setContentText(message)
                .setStyle(new Notification.BigTextStyle().bigText(message))
                .setContentIntent(contentIntent)
                .setAutoCancel(true)
                .setColor(BRAND_GREEN)
                .setWhen(System.currentTimeMillis())
                .setShowWhen(true);

        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) {
            builder.setPriority(Notification.PRIORITY_HIGH);
        }

        manager.notify(NOTIFICATION_ID, builder.build());
    }

    private static void createChannelIfNeeded(NotificationManager manager) {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) {
            return;
        }

        NotificationChannel channel = new NotificationChannel(
                CHANNEL_ID,
                CHANNEL_NAME,
                NotificationManager.IMPORTANCE_HIGH
        );
        channel.setDescription("Pengingat untuk mengirim laporan harian AMPUH.");
        manager.createNotificationChannel(channel);
    }

    private static boolean canPostNotifications(Context context) {
        return Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU
                || context.checkSelfPermission(Manifest.permission.POST_NOTIFICATIONS) == PackageManager.PERMISSION_GRANTED;
    }
}
