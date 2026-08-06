<?php

namespace App\Models;

use Database\Factories\StatusIncidentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string|null $component
 * @property Carbon $started_at
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, IncidentUpdate> $updates
 */
#[Fillable(['title', 'component', 'started_at', 'resolved_at'])]
class StatusIncident extends Model
{
    /** @use HasFactory<StatusIncidentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /** @phpstan-return HasMany<IncidentUpdate, $this> */
    public function updates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class, 'incident_id')->orderByDesc('created_at');
    }
}
