<?php

namespace App\Notifications;

use App\Models\ApiKey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class ApiKeyExpiring extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The preference key controlling this notification.
     */
    public const PREFERENCE_KEY = 'key_expiring';

    /**
     * Create a new notification instance.
     */
    public function __construct(public ApiKey $key) {}

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
            'key_id' => $this->key->id,
            'app_id' => $this->key->app_id,
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $app = $this->key->app;
        $keysUrl = route('apps.keys.index', [$app->id]);

        return (new MailMessage)
            ->subject('API key expiring for '.$app->name)
            ->markdown('emails.notifications.key-expiring', [
                'key' => $this->key,
                'app' => $app,
                'keysUrl' => $keysUrl,
            ]);
    }
}
