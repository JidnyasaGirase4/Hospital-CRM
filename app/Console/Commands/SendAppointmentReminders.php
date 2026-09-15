<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Notifications\AppointmentReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Notifies each appointment's doctor about scheduled appointments starting
 * within the next hour. Intended to run every few minutes via the
 * scheduler (routes/console.php) in a deployed environment with a queue
 * worker running.
 */
class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Send reminder notifications for appointments starting within the next hour';

    public function handle(): int
    {
        $appointments = Appointment::query()
            ->where('status', 'scheduled')
            ->whereBetween('scheduled_at', [now(), now()->addHour()])
            ->with(['doctor', 'patient'])
            ->get();

        foreach ($appointments as $appointment) {
            if (! $appointment->doctor) {
                continue;
            }

            Notification::send($appointment->doctor, new AppointmentReminderNotification(
                $appointment->id,
                $appointment->patient->fullName(),
                $appointment->scheduled_at->toDateTimeString()
            ));
        }

        $this->info("Sent {$appointments->count()} appointment reminder(s).");

        return self::SUCCESS;
    }
}
