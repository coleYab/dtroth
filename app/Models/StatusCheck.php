<?php

namespace App\Models;

use App\Enums\ComponentStatus;
use Database\Factories\StatusCheckFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $component
 * @property ComponentStatus $status
 * @property int|null $response_time_ms
 * @property Carbon $checked_at
 */
#[Fillable(['component', 'status', 'response_time_ms', 'checked_at'])]
class StatusCheck extends Model
{
    /** @use HasFactory<StatusCheckFactory> */
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
            'status' => ComponentStatus::class,
            'response_time_ms' => 'integer',
            'checked_at' => 'datetime',
        ];
    }
}
