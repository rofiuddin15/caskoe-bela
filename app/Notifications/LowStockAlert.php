<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Stock;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public $lowStocks;
    public $branch;

    /**
     * Create a new notification instance.
     */
    public function __construct($lowStocks, $branch = null)
    {
        $this->lowStocks = $lowStocks;
        $this->branch = $branch;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $branchName = $this->branch ? $this->branch->name : 'All Branches';
        $itemCount = count($this->lowStocks);

        $mail = (new MailMessage)
            ->subject('Low Stock Alert - ' . $branchName)
            ->line('You have ' . $itemCount . ' item(s) running low on stock.')
            ->line('Please review the following items:');

        foreach ($this->lowStocks as $stock) {
            $mail->line(
                '- ' . $stock['raw_material'] . ' at ' . $stock['branch'] .
                ': ' . $stock['current_quantity'] . ' ' . $stock['unit'] .
                ' (Min: ' . $stock['min_stock'] . ')'
            );
        }

        $mail->action('View Inventory', url('/api/reports/low-stock'))
             ->line('Please restock as soon as possible to avoid disruptions.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Low stock alert',
            'item_count' => count($this->lowStocks),
            'branch' => $this->branch ? $this->branch->name : 'All Branches',
            'items' => $this->lowStocks,
        ];
    }
}
