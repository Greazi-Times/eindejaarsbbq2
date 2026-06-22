<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ResolvesDashboardEvent;
use App\Support\DailyEnrollmentSeries;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;

class DailyEnrollmentChart extends ChartWidget
{
    use HasWidgetShield, ResolvesDashboardEvent;

    protected static bool $isLazy = false;

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Aanmeldingen en personen per dag';

    protected ?string $maxHeight = '320px';

    public function getDescription(): ?string
    {
        return $this->getDashboardEventDescription($this->getDashboardEvent());
    }

    protected function getData(): array
    {
        $event = $this->getDashboardEvent();

        if (! $event) {
            return $this->chartData();
        }

        $series = app(DailyEnrollmentSeries::class)->forEvent($event);

        return $this->chartData(
            $series['labels'],
            $series['enrollmentCounts'],
            $series['cumulativePeopleCounts'],
        );
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'labels' => [
                        'usePointStyle' => true,
                    ],
                ],
                'tooltip' => [
                    'displayColors' => true,
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'autoSkip' => true,
                        'maxRotation' => 0,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Aanmeldingen per dag',
                    ],
                    'ticks' => [
                        'precision' => 0,
                        'stepSize' => 1,
                    ],
                ],
                'yPeople' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Totaal personen',
                    ],
                    'ticks' => [
                        'precision' => 0,
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<int, int>  $enrollmentCounts
     * @param  array<int, int>  $cumulativePeopleCounts
     */
    private function chartData(
        array $labels = [],
        array $enrollmentCounts = [],
        array $cumulativePeopleCounts = [],
    ): array {
        return [
            'datasets' => [
                [
                    'type' => 'bar',
                    'label' => 'Aanmeldingen per dag',
                    'data' => $enrollmentCounts,
                    'yAxisID' => 'y',
                    'backgroundColor' => 'rgba(249, 115, 22, 0.7)',
                    'borderColor' => 'rgb(249, 115, 22)',
                    'borderRadius' => 6,
                    'borderWidth' => 0,
                    'order' => 1,
                ],
                [
                    'type' => 'line',
                    'label' => 'Totaal personen',
                    'data' => $cumulativePeopleCounts,
                    'yAxisID' => 'yPeople',
                    'backgroundColor' => 'rgb(16, 185, 129)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'pointBackgroundColor' => 'rgb(16, 185, 129)',
                    'pointBorderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 3,
                    'pointRadius' => 3,
                    'pointHoverRadius' => 5,
                    'tension' => 0.3,
                    'fill' => false,
                    'order' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
