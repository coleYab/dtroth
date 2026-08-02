<?php

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $number
 * @property string|null $tx_ref
 * @property int $amount
 * @property string $currency
 * @property string $status
 * @property string|null $channel
 * @property Carbon|null $paid_at
 * @property Carbon|null $period_start
 * @property Carbon|null $period_end
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['user_id', 'number', 'tx_ref', 'amount', 'currency', 'status', 'channel', 'paid_at', 'period_start', 'period_end'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
        ];
    }

    /**
     * The amount formatted for display, e.g. "ETB 499.00".
     */
    public function formattedAmount(): string
    {
        return $this->currency.' '.number_format($this->amount / 100, 2);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
