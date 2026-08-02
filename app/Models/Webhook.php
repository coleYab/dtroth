<?php

namespace App\Models;

use Database\Factories\WebhookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $app_id
 * @property string $url
 * @property string $secret
 * @property string $secret_preview
 * @property string|null $description
 * @property Carbon|null $last_triggered_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read App $app
 * @property-read WebhookEvent[] $events
 * @property-read WebhookDelivery[] $deliveries
 */
#[Fillable(['app_id', 'url', 'secret', 'secret_preview', 'description', 'last_triggered_at'])]
class Webhook extends Model
{
    /** @use HasFactory<WebhookFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'last_triggered_at' => 'datetime',
        ];
    }

    /**
     * Whether the webhook has a failed delivery in the recent window.
     */
    public function isFailing(int $windowHours = 24): bool
    {
        return $this->deliveries()
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHours($windowHours))
            ->exists();
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(WebhookEvent::class, 'webhook_event', 'webhook_id', 'event_id')
            ->withTimestamps();
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }
}
