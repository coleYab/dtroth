<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class InvoiceFailed extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The preference key controlling this notification.
     */
    public const PREFERENCE_KEY = 'invoice_failed';

    /**
     * Create a new notification instance.
     */
    public function __construct(public Invoice $invoice) {}

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
     * The preference key controlling this notification.
     */
    public static function preferenceKey(): string
    {
        return self::PREFERENCE_KEY;
    }

    /**
     * The data stored alongside the notification, used for deduplication.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $billingUrl = route('billing.index');

        return (new MailMessage)
            ->subject('Payment failed for invoice '.$this->invoice->number)
            ->markdown('emails.notifications.invoice-failed', [
                'invoice' => $this->invoice,
                'billingUrl' => $billingUrl,
            ]);
    }
}
