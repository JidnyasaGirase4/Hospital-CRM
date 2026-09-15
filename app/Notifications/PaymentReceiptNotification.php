<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceiptNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $paymentNumber,
        public readonly string $amount,
        public readonly string $method
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment receipt {$this->paymentNumber}")
            ->line("Payment of {$this->amount} received via {$this->method}.")
            ->line("Receipt number: {$this->paymentNumber}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_receipt',
            'payment_number' => $this->paymentNumber,
            'amount' => $this->amount,
            'method' => $this->method,
        ];
    }
}
