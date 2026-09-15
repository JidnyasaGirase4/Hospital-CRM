<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $itemName,
        public readonly int $stockOnHand,
        public readonly int $reorderLevel
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Low stock: {$this->itemName}")
            ->line("{$this->itemName} is at {$this->stockOnHand}, at or below its reorder level of {$this->reorderLevel}.")
            ->line('Please arrange a purchase.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock_alert',
            'item_name' => $this->itemName,
            'stock_on_hand' => $this->stockOnHand,
            'reorder_level' => $this->reorderLevel,
        ];
    }
}
