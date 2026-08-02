<?php

namespace App\Models;

use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $app_id
 * @property string $action
 * @property string $description
 * @property Carbon $created_at
 * @property-read User $user
 * @property-read App|null $app
 */
#[Fillable(['user_id', 'app_id', 'action', 'description', 'created_at'])]
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    public const ACTION_APP_CREATED = 'app.created';

    public const ACTION_APP_DELETED = 'app.deleted';

    public const ACTION_KEY_CREATED = 'key.created';

    public const ACTION_KEY_ROTATED = 'key.rotated';

    public const ACTION_KEY_REVOKED = 'key.revoked';

    public const ACTION_WEBHOOK_CHANGED = 'webhook.changed';

    public const ACTION_PRESET_CHANGED = 'preset.changed';

    public const ACTION_VERIFICATION_CHANGED = 'verification.changed';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
