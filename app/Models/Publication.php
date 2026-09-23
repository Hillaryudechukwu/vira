<?php

namespace App\Models;

use App\Modules\Publishing\Enums\PublicationStatus;
use App\Modules\Shared\Enums\Platform;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Publication extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'status' => PublicationStatus::class,
            'metadata' => 'array',
            'scheduled_for' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function contentProject(): BelongsTo
    {
        return $this->belongsTo(ContentProject::class);
    }

    public function approvalRequest(): HasOne
    {
        return $this->hasOne(ApprovalRequest::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PublicationAttempt::class);
    }

    public function metricSnapshots(): HasMany
    {
        return $this->hasMany(MetricSnapshot::class);
    }

    public function performanceReport(): HasOne
    {
        return $this->hasOne(PerformanceReport::class);
    }
}
