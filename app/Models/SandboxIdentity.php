<?php

namespace App\Models;

use Database\Factories\SandboxIdentityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string $label
 * @property int $age
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'label', 'age'])]
class SandboxIdentity extends Model
{
    /** @use HasFactory<SandboxIdentityFactory> */
    use HasFactory;
}
