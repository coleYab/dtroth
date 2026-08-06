<?php

namespace App\Models;

use Database\Factories\VerificationSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $app_id
 * @property string|null $consent_text
 * @property int $re_verify_months
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read App $app
 */
#[Fillable(['app_id', 'consent_text', 're_verify_months'])]
class VerificationSetting extends Model
{
    /** @use HasFactory<VerificationSettingFactory> */
    use HasFactory;

    /**
     * The default consent text shown when no custom text is set.
     */
    public const DEFAULT_CONSENT_TEXT = 'I consent to a one-time age verification performed by a trusted third party. My age will be shared as a single value (e.g. "18+") and never my date of birth.';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            're_verify_months' => 'integer',
        ];
    }

    /** @phpstan-return BelongsTo<App, $this> */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
