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

    public const ACTION_TEAM_INVITED = 'team.invited';

    public const ACTION_TEAM_ROLE_CHANGED = 'team.role_changed';

    public const ACTION_TEAM_MEMBER_REMOVED = 'team.member_removed';

    public const ACTION_TEAM_INVITE_CANCELLED = 'team.invite_cancelled';

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

    /** @phpstan-return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @phpstan-return BelongsTo<App, $this> */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
