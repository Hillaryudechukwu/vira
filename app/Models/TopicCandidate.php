<?php

namespace App\Models;

use App\Modules\Research\Enums\TopicStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class TopicCandidate extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => TopicStatus::class,
            'component_scores' => 'array',
            'score_explanation' => 'array',
            'viral_score' => 'decimal:2',
            'risk_score' => 'decimal:2',
            'confidence' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function contentProject(): HasOne
    {
        return $this->hasOne(ContentProject::class);
    }
}
