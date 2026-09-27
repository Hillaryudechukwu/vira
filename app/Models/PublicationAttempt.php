<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PublicationAttempt extends Model
{
    protected $attributes = ['response_metadata' => '{}'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'response_metadata' => 'array',
            'retryable' => 'boolean',
            'next_retry_at' => 'datetime',
        ];
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }
}
