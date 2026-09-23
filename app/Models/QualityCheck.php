<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class QualityCheck extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['findings' => 'array', 'checked_at' => 'datetime'];
    }
}
