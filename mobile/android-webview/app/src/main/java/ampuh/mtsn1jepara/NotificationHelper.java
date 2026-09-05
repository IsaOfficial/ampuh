package matsantura.ampuh;

import android.Manifest;
import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.graphics.Color;
import android.os.Build;

import java.util.Calendar;

public final class NotificationHelper {
    private static final String CHANNEL_ID = "laporan_harian_reminder";
    private static final String CHANNEL_NAME = "Pengingat Laporan Harian";
    private static final String PREFS_NAME = "ampuh_notification_prefs";
    private static final String KEY_LAST_REPORT_REMINDER_AT = "last_report_reminder_at";
    private static final int NOTIFICATION_ID = 11010;
    private static final int BRAND_GREEN = Color.rgb(0, 132, 61);
    private static final long MIN_REMINDER_INTERVAL_MILLIS = 2L * 60L * 60L * 1000L;

    private NotificationHelper() {
    }

    public static void showReportReminder(Context context, String employeeName) {
        if (!canPostNotifications(context) || !isReminderWindow() || wasRecentlyShown(context)) {
            return;
        }

        NotificationManager manager = (NotificationManager) context.getSystemService(Context.NOTIFICATION_SERVICE);
        if (manager == null) {
            return;
        }

        createChannelIfNeeded(manager);
        if (isChannelBlocked(manager)) {
            return;
        }

        Intent openAppIntent = new Intent(context, MainActivity.class);
        openAppIntent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TOP);

        int flags = PendingIntent.FLAG_UPDATE_CURRENT;
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            flags |= PendingIntent.FLAG_IMMUTABLE;
        }

        PendingIntent contentIntent = PendingIntent.getActivity(context, 0, openAppIntent, flags);

        String greetingName = employeeName == null || employeeName.trim().isEmpty()
                ? "Bapak/Ibu"
                : employeeName.trim();
        String title = "Hai, " + greetingName;
        String message = "Anda belum mengirim laporan hari ini! Segera kirim laporan.";

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
        markShown(context);
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

    private static boolean isChannelBlocked(NotificationManager manager) {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) {
            return false;
        }

        NotificationChannel channel = manager.getNotificationChannel(CHANNEL_ID);
        return channel != null && channel.getImportance() == NotificationManager.IMPORTANCE_NONE;
    }

    private static boolean isReminderWindow() {
        int hour = Calendar.getInstance().get(Calendar.HOUR_OF_DAY);
        return hour >= 10 && hour <= 22;
    }

    private static boolean wasRecentlyShown(Context context) {
        long lastShownAt = prefs(context).getLong(KEY_LAST_REPORT_REMINDER_AT, 0L);
        return lastShownAt > 0L && System.currentTimeMillis() - lastShownAt < MIN_REMINDER_INTERVAL_MILLIS;
    }

    private static void markShown(Context context) {
        prefs(context)
                .edit()
                .putLong(KEY_LAST_REPORT_REMINDER_AT, System.currentTimeMillis())
                .apply();
    }

    private static SharedPreferences prefs(Context context) {
        return context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE);
    }
}
