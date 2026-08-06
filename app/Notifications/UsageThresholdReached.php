<?php

namespace App\Notifications;

use App\Models\App;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class UsageThresholdReached extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The preference key controlling this notification.
     */
    public const PREFERENCE_KEY = 'usage_threshold';

    /**
     * Create a new notification instance.
     *
     * @param  int  $threshold  the percentage threshold crossed
     */
    public function __construct(
        public App $app,
        public int $threshold,
        public int $usedCalls,
        public int $quotaCalls,
    ) {}

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
            'app_id' => $this->app->id,
            'threshold' => $this->threshold,
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $usageUrl = route('apps.usage', [$this->app->id]);

        return (new MailMessage)
            ->subject('Usage at '.$this->threshold.'% for '.$this->app->name)
            ->markdown('emails.notifications.usage-threshold', [
                'app' => $this->app,
                'threshold' => $this->threshold,
                'usedCalls' => $this->usedCalls,
                'quotaCalls' => $this->quotaCalls,
                'usageUrl' => $usageUrl,
            ]);
    }
}
