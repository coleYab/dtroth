<?php

namespace App\Notifications;

use App\Models\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class WebhookFailing extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The preference key controlling this notification.
     */
    public const PREFERENCE_KEY = 'webhook_failing';

    /**
     * Create a new notification instance.
     */
    public function __construct(public Webhook $webhook) {}

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
            'webhook_id' => $this->webhook->id,
            'app_id' => $this->webhook->app_id,
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $app = $this->webhook->app;
        $logsUrl = route('apps.webhooks.logs', [$app->id, $this->webhook->id]);

        return (new MailMessage)
            ->subject('Webhook failing for '.$app->name)
            ->markdown('emails.notifications.webhook-failing', [
                'webhook' => $this->webhook,
                'app' => $app,
                'logsUrl' => $logsUrl,
            ]);
    }
}
