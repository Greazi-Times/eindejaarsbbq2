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

    protected ?string $heading = 'Aanmeldingen per dag';

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

        return $this->chartData($series['labels'], $series['data']);
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'displayColors' => false,
                ],
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
     * @param  array<int, int>  $data
     */
    private function chartData(array $labels = [], array $data = []): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Aanmeldingen',
                    'data' => $data,
                    'borderRadius' => 6,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
