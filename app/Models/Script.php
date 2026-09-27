<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Script extends Model
{
    use HasUuids;

    protected $attributes = ['claims' => '[]', 'model_metadata' => '{}'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'structure' => 'array',
            'claims' => 'array',
            'model_metadata' => 'array',
        ];
    }

    public function contentProject(): BelongsTo
    {
        return $this->belongsTo(ContentProject::class);
    }
}
