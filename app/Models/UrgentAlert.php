<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Audit record of dispatched urgent SMS / WhatsApp alerts.
 *
 * @property int $id
 * @property string $phone
 * @property string $message
 * @property string $driver
 * @property string $status
 * @property array<string, mixed>|null $metadata
 * @property string|null $error
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['phone', 'message', 'driver', 'status', 'metadata', 'error'])]
class UrgentAlert extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
