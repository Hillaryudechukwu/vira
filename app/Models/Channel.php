<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Channel extends Model
{
    use HasUuids;

    protected $attributes = ['settings' => '[]'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function topicCandidates(): HasMany
    {
        return $this->hasMany(TopicCandidate::class);
    }

    public function contentProjects(): HasMany
    {
        return $this->hasMany(ContentProject::class);
    }
}
