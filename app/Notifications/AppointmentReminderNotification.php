<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $appointmentId,
        public readonly string $patientName,
        public readonly string $scheduledAt
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Upcoming appointment reminder')
            ->line("Reminder: appointment with {$this->patientName} at {$this->scheduledAt}.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'appointment_reminder',
            'appointment_id' => $this->appointmentId,
            'patient_name' => $this->patientName,
            'scheduled_at' => $this->scheduledAt,
        ];
    }
}
