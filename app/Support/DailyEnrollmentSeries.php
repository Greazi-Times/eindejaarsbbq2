<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\Event;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

class DailyEnrollmentSeries
{
    /**
     * @return array{
     *     labels: array<int, string>,
     *     enrollmentCounts: array<int, int>,
     *     cumulativePeopleCounts: array<int, int>
     * }
     */
    public function forEvent(Event $event): array
    {
        $enrollments = Enrollment::query()
            ->whereBelongsTo($event)
            ->orderBy('created_at')
            ->get(['created_at', 'guest_amount']);

        if ($enrollments->isEmpty()) {
            return [
                'labels' => [],
                'enrollmentCounts' => [],
                'cumulativePeopleCounts' => [],
            ];
        }

        $dailyTotals = $enrollments
            ->groupBy(fn (Enrollment $enrollment): string => $enrollment->created_at->toDateString())
            ->map(fn ($enrollments): array => [
                'enrollments' => $enrollments->count(),
                'people' => $enrollments->sum('guest_amount'),
            ]);

        $firstDate = CarbonImmutable::parse($dailyTotals->keys()->first());
        $lastDate = CarbonImmutable::parse($dailyTotals->keys()->last());
        $dates = collect(CarbonPeriod::create($firstDate, $lastDate));
        $cumulativePeople = 0;

        return [
            'labels' => $dates
                ->map(fn ($date): string => $date->format('d-m-Y'))
                ->all(),
            'enrollmentCounts' => $dates
                ->map(fn ($date): int => $dailyTotals->get($date->toDateString(), ['enrollments' => 0])['enrollments'])
                ->all(),
            'cumulativePeopleCounts' => $dates
                ->map(function ($date) use (&$cumulativePeople, $dailyTotals): int {
                    $cumulativePeople += $dailyTotals->get($date->toDateString(), ['people' => 0])['people'];

                    return $cumulativePeople;
                })
                ->all(),
        ];
    }
}
