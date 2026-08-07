package ampuh.mtsn1jepara;

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
        Context appContext = context.getApplicationContext();

        new Thread(() -> {
            try {
                ReportStatusClient.TodayReportStatus status = new ReportStatusClient().fetchTodayStatus();
                if (status.available && status.authenticated && !status.submitted) {
                    NotificationHelper.showReportReminder(appContext, status.name);
                }
            } finally {
                ReminderScheduler.scheduleNext(appContext);
                pendingResult.finish();
            }
        }).start();
    }
}
