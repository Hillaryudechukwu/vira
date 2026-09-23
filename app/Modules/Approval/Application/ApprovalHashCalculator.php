<?php

namespace App\Modules\Approval\Application;

final class ApprovalHashCalculator
{
    public function calculate(array $reviewPayload): string
    {
        return hash('sha256', json_encode(
            $this->sortRecursively($reviewPayload),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    private function sortRecursively(array $value): array
    {
        foreach ($value as &$item) {
            if (is_array($item)) {
                $item = $this->sortRecursively($item);
            }
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
