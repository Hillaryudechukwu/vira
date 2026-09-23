<?php

namespace App\Modules\Production\Application;

use InvalidArgumentException;

final class SrtCaptionBuilder
{
    public function build(array $beats): string
    {
        $blocks = [];

        foreach (array_values($beats) as $index => $beat) {
            foreach (['start_ms', 'end_ms', 'caption'] as $key) {
                if (! array_key_exists($key, $beat)) {
                    throw new InvalidArgumentException("Caption beat is missing {$key}.");
                }
            }

            if ((int) $beat['end_ms'] <= (int) $beat['start_ms']) {
                throw new InvalidArgumentException('Caption end time must be later than start time.');
            }

            $blocks[] = implode("\n", [
                (string) ($index + 1),
                $this->timestamp((int) $beat['start_ms']).' --> '.$this->timestamp((int) $beat['end_ms']),
                trim((string) $beat['caption']),
            ]);
        }

        return implode("\n\n", $blocks)."\n";
    }

    private function timestamp(int $milliseconds): string
    {
        $hours = intdiv($milliseconds, 3_600_000);
        $milliseconds %= 3_600_000;
        $minutes = intdiv($milliseconds, 60_000);
        $milliseconds %= 60_000;
        $seconds = intdiv($milliseconds, 1000);
        $milliseconds %= 1000;

        return sprintf('%02d:%02d:%02d,%03d', $hours, $minutes, $seconds, $milliseconds);
    }
}
