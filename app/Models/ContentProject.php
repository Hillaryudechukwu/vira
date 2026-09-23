<?php

namespace App\Models;

use App\Modules\Editorial\Enums\ContentProjectStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ContentProject extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ContentProjectStatus::class,
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function topicCandidate(): BelongsTo
    {
        return $this->belongsTo(TopicCandidate::class);
    }

    public function scripts(): HasMany
    {
        return $this->hasMany(Script::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }
}
