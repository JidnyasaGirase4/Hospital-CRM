<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportReadyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $reportType,
        public readonly string $patientName,
        public readonly int $patientId
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->reportType} report ready")
            ->line("The {$this->reportType} report for {$this->patientName} has been approved and is ready to view.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'report_ready',
            'report_type' => $this->reportType,
            'patient_id' => $this->patientId,
            'patient_name' => $this->patientName,
        ];
    }
}
