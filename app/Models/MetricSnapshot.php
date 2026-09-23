<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MetricSnapshot extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['raw_metrics' => 'array', 'normalised_metrics' => 'array', 'availability' => 'array', 'observed_at' => 'datetime'];
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }
}
