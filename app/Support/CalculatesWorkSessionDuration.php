<?php

namespace App\Support;

use Carbon\CarbonInterface;
use InvalidArgumentException;

class CalculatesWorkSessionDuration
{
    public static function forPeriod(CarbonInterface $startTime, CarbonInterface $endTime, int $breakMinutes = 0): int
    {
        $durationMinutes = $startTime->diffInMinutes($endTime, false) - $breakMinutes;

        if ($durationMinutes < 0) {
            throw new InvalidArgumentException('The calculated duration must be zero or greater.');
        }

        return $durationMinutes;
    }
}
