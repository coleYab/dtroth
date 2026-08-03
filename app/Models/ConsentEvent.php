<?php

namespace App\Models;

use Database\Factories\ConsentEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $consent_id
 * @property string $type
 * @property Carbon $created_at
 * @property-read Consent $consent
 */
#[Fillable(['consent_id', 'type', 'created_at'])]
class ConsentEvent extends Model
{
    /** @use HasFactory<ConsentEventFactory> */
    use HasFactory;

    public const TYPE_GRANTED = 'granted';

    public const TYPE_RENEWED = 'renewed';

    public const TYPE_REVOKED = 'revoked';

    public const TYPE_EXPIRED = 'expired';

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

    public function consent(): BelongsTo
    {
        return $this->belongsTo(Consent::class);
    }
}
