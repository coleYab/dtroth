<?php

namespace App\Models;

use Database\Factories\ProviderIdentityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $national_id
 * @property string|null $full_name
 * @property Carbon $birth_date
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['national_id', 'full_name', 'birth_date', 'status'])]
class ProviderIdentity extends Model
{
    /** @use HasFactory<ProviderIdentityFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_LOST = 'lost';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    /**
     * Whether the identity can be used for verification.
     */
    public function isUsable(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
