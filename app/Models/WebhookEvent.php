<?php

namespace App\Models;

use Database\Factories\WebhookEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $label
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Webhook[] $webhooks
 */
#[Fillable(['name', 'label'])]
class WebhookEvent extends Model
{
    /** @use HasFactory<WebhookEventFactory> */
    use HasFactory;

    public const VERIFICATION_COMPLETED = 'verification.completed';

    public const VERIFICATION_EXPIRED = 'verification.expired';

    public const CONSENT_REVOKED = 'consent.revoked';

    public function webhooks(): BelongsToMany
    {
        return $this->belongsToMany(Webhook::class, 'webhook_event', 'event_id', 'webhook_id')
            ->withTimestamps();
    }
}
