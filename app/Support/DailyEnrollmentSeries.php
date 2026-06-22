<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\Event;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

class DailyEnrollmentSeries
{
    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public function forEvent(Event $event): array
    {
        $dailyTotals = Enrollment::query()
            ->whereBelongsTo($event)
            ->orderBy('created_at')
            ->pluck('created_at')
            ->groupBy(fn ($createdAt): string => $createdAt->toDateString())
            ->map(fn ($enrollments): int => $enrollments->count());

        if ($dailyTotals->isEmpty()) {
            return [
                'labels' => [],
                'data' => [],
            ];
        }

        $firstDate = CarbonImmutable::parse($dailyTotals->keys()->first());
        $lastDate = CarbonImmutable::parse($dailyTotals->keys()->last());
        $dates = collect(CarbonPeriod::create($firstDate, $lastDate));

        return [
            'labels' => $dates
                ->map(fn ($date): string => $date->format('d-m-Y'))
                ->all(),
            'data' => $dates
                ->map(fn ($date): int => $dailyTotals->get($date->toDateString(), 0))
                ->all(),
        ];
    }
}
