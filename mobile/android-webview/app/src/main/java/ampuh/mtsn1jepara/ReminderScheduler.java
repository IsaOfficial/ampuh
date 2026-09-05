package matsantura.ampuh;

import android.app.AlarmManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.os.Build;

import java.util.Calendar;

public final class ReminderScheduler {
    static final String ACTION_CHECK_REPORT = "matsantura.ampuh.action.CHECK_TODAY_REPORT";

    private static final int REQUEST_CODE = 10001;
    private static final int[] REMINDER_HOURS = {10, 12, 14, 16, 18, 20, 22};

    private ReminderScheduler() {
    }

    public static void scheduleNext(Context context) {
        AlarmManager alarmManager = (AlarmManager) context.getSystemService(Context.ALARM_SERVICE);
        if (alarmManager == null) {
            return;
        }

        PendingIntent pendingIntent = reminderPendingIntent(context);
        long triggerAtMillis = nextTriggerAtMillis();

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            alarmManager.setAndAllowWhileIdle(AlarmManager.RTC_WAKEUP, triggerAtMillis, pendingIntent);
        } else {
            alarmManager.set(AlarmManager.RTC_WAKEUP, triggerAtMillis, pendingIntent);
        }
    }

    static PendingIntent reminderPendingIntent(Context context) {
        Intent intent = new Intent(context, ReportReminderReceiver.class);
        intent.setAction(ACTION_CHECK_REPORT);

        int flags = PendingIntent.FLAG_UPDATE_CURRENT;
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            flags |= PendingIntent.FLAG_IMMUTABLE;
        }

        return PendingIntent.getBroadcast(context, REQUEST_CODE, intent, flags);
    }

    private static long nextTriggerAtMillis() {
        Calendar now = Calendar.getInstance();

        for (int hour : REMINDER_HOURS) {
            Calendar candidate = reminderTime(now, hour);
            if (candidate.after(now)) {
                return candidate.getTimeInMillis();
            }
        }

        Calendar tomorrow = reminderTime(now, REMINDER_HOURS[0]);
        tomorrow.add(Calendar.DAY_OF_YEAR, 1);
        return tomorrow.getTimeInMillis();
    }

    private static Calendar reminderTime(Calendar base, int hour) {
        Calendar candidate = (Calendar) base.clone();
        candidate.set(Calendar.HOUR_OF_DAY, hour);
        candidate.set(Calendar.MINUTE, 0);
        candidate.set(Calendar.SECOND, 0);
        candidate.set(Calendar.MILLISECOND, 0);
        return candidate;
    }
}
