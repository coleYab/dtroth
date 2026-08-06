<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;

class NotificationGate
{
    /**
     * Send a notification to the user unless a preference is disabled or the
     * same notification (matching its dedup data) was sent within 24 hours.
     *
     * @param  array<string, mixed>  $dedupData
     */
    public function send(User $user, Notification $notification, array $dedupData): bool
    {
        if (! method_exists($notification, 'preferenceKey') || ! $user->notificationPreference($notification->preferenceKey())) {
            return false;
        }

        $query = $user->notifications()
            ->where('type', get_class($notification))
            ->where('created_at', '>=', now()->subHours(24));

        foreach ($dedupData as $key => $value) {
            $query->where('data->'.$key, $value);
        }

        $alreadySent = $query->exists();

        if ($alreadySent) {
            return false;
        }

        $user->notify($notification);

        return true;
    }
}
