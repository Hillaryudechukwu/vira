<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class BudgetReservation extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount_gbp' => 'decimal:4', 'reserved_at' => 'datetime', 'released_at' => 'datetime'];
    }
}
