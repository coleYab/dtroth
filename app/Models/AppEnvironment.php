<?php

namespace App\Models;

use App\Enums\EnvironmentName;
use Database\Factories\AppEnvironmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $app_id
 * @property EnvironmentName $name
 * @property bool $is_enabled
 * @property bool $is_live
 * @property array<int, string>|null $redirect_uris
 * @property array<int, string>|null $allowed_origins
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read App $app
 */
#[Fillable(['app_id', 'name', 'is_enabled', 'is_live', 'redirect_uris', 'allowed_origins'])]
class AppEnvironment extends Model
{
    /** @use HasFactory<AppEnvironmentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => EnvironmentName::class,
            'is_enabled' => 'boolean',
            'is_live' => 'boolean',
            'redirect_uris' => 'array',
            'allowed_origins' => 'array',
        ];
    }

    /** @phpstan-return BelongsTo<App, $this> */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
