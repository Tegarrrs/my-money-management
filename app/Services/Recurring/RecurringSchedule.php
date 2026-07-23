<?php

namespace App\Services\Recurring;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class RecurringSchedule
{
    public function next(CarbonInterface $from, string $frequency): Carbon
    {
        $date = Carbon::instance($from)->copy();

        return match ($frequency) {
            'daily' => $date->addDay(),
            'weekly' => $date->addWeek(),
            'monthly' => $date->addMonthNoOverflow(),
            'yearly' => $date->addYearNoOverflow(),
            default => throw new InvalidArgumentException("Frekuensi {$frequency} tidak didukung."),
        };
    }
}
