<?php

namespace App\Models;

use App\Enums\AgeQueryType;
use Database\Factories\VerificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $app_id
 * @property string $status
 * @property AgeQueryType|null $query_type
 * @property int|null $query_value
 * @property bool|null $result
 * @property Carbon $created_at
 * @property-read App $app
 */
#[Fillable(['app_id', 'status', 'query_type', 'query_value', 'result', 'created_at'])]
class Verification extends Model
{
    /** @use HasFactory<VerificationFactory> */
    use HasFactory;

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
            'query_type' => AgeQueryType::class,
            'query_value' => 'integer',
            'result' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * The readable age query, e.g. "18+".
     */
    public function ageQuery(): ?string
    {
        if ($this->query_type === null || $this->query_value === null) {
            return null;
        }

        return $this->query_type->describe($this->query_value);
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
