<?php

namespace App\Modules\Editorial\Contracts;

use App\Modules\Editorial\DTO\ReasoningResult;

interface StructuredReasoner
{
    public function generateScript(array $context): ReasoningResult;
}
