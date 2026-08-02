<?php

namespace App\Models;

use Database\Factories\UsageDailyRollupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $app_id
 * @property string $date
 * @property int $calls_total
 * @property int $verifications_total
 * @property int $failed_calls
 * @property float $success_rate
 * @property string|null $most_used_query
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read App $app
 */
#[Fillable(['app_id', 'date', 'calls_total', 'verifications_total', 'failed_calls', 'success_rate', 'most_used_query'])]
class UsageDailyRollup extends Model
{
    /** @use HasFactory<UsageDailyRollupFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'calls_total' => 'integer',
            'verifications_total' => 'integer',
            'failed_calls' => 'integer',
            'success_rate' => 'float',
        ];
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
