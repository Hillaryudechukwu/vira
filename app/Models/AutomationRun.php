<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AutomationRun extends Model
{
    use HasUuids;

    protected $attributes = ['context' => '{}'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['context' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function contentProject(): BelongsTo
    {
        return $this->belongsTo(ContentProject::class);
    }
}
