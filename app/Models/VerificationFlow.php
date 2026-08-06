<?php

namespace App\Models;

use Database\Factories\VerificationFlowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $app_id
 * @property string|null $logo_path
 * @property string $brand_color
 * @property string|null $success_redirect_url
 * @property string|null $failure_redirect_url
 * @property string|null $callback_url
 * @property string|null $callback_secret
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read App $app
 */
#[Fillable(['app_id', 'logo_path', 'brand_color', 'success_redirect_url', 'failure_redirect_url', 'callback_url', 'callback_secret'])]
class VerificationFlow extends Model
{
    /** @use HasFactory<VerificationFlowFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'callback_secret' => 'encrypted',
        ];
    }

    /** @phpstan-return BelongsTo<App, $this> */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
