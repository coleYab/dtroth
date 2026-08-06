<?php

namespace App\Models;

use App\Enums\AgeQueryType;
use Database\Factories\AgeQueryPresetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property AgeQueryType $type
 * @property int $value
 * @property string $label
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, App> $apps
 */
#[Fillable(['type', 'value', 'label', 'description', 'sort_order', 'is_active'])]
class AgeQueryPreset extends Model
{
    /** @use HasFactory<AgeQueryPresetFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AgeQueryType::class,
            'value' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope the query to presets available for selection.
     *
     * @param  Builder<AgeQueryPreset>  $query
     * @return Builder<AgeQueryPreset>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /** @phpstan-return BelongsToMany<App, $this> */
    public function apps(): BelongsToMany
    {
        return $this->belongsToMany(App::class, 'age_query_preset_app', 'preset_id', 'app_id')
            ->withTimestamps();
    }
}
