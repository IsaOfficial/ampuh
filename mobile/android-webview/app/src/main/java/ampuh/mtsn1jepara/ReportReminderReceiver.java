package matsantura.ampuh;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;

public class ReportReminderReceiver extends BroadcastReceiver {
    @Override
    public void onReceive(Context context, Intent intent) {
        if (intent == null || !ReminderScheduler.ACTION_CHECK_REPORT.equals(intent.getAction())) {
            return;
        }

        PendingResult pendingResult = goAsync();
        checkAndNotifyAsync(context.getApplicationContext(), pendingResult, true);
    }

    public static void checkAndNotifyAsync(Context context) {
        checkAndNotifyAsync(context.getApplicationContext(), null, false);
    }

    private static void checkAndNotifyAsync(Context appContext, PendingResult pendingResult, boolean scheduleNext) {
        new Thread(() -> {
            try {
                ReportStatusClient.TodayReportStatus status = new ReportStatusClient(appContext).fetchTodayStatus();
                if (status.available && status.authenticated && !status.submitted) {
                    NotificationHelper.showReportReminder(appContext, status.name);
                }
            } finally {
                if (scheduleNext) {
                    ReminderScheduler.scheduleNext(appContext);
                }

                if (pendingResult != null) {
                    pendingResult.finish();
                }
            }
        }).start();
    }
}
