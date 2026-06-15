<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ResolvesDashboardEvent;
use App\Models\Enrollment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class EventEnrollmentOverview extends StatsOverviewWidget
{
    use ResolvesDashboardEvent;

    protected static bool $isLazy = false;

    protected static ?int $sort = -3;

    protected ?string $heading = 'Aanmeldingen';

    protected function getDescription(): ?string
    {
        return $this->getDashboardEventDescription($this->getDashboardEvent());
    }

    protected function getStats(): array
    {
        $event = $this->getDashboardEvent();

        if (! $event) {
            return [
                Stat::make('Personen aangemeld', 0)
                    ->description('Maak eerst een event met een startdatum aan.')
                    ->icon('heroicon-o-users')
                    ->color('gray'),
            ];
        }

        $totals = Enrollment::query()
            ->whereBelongsTo($event)
            ->selectRaw('COALESCE(SUM(guest_amount), 0) as people_count')
            ->selectRaw('COUNT(*) as enrollment_count')
            ->first();

        return [
            Stat::make('Personen aangemeld', Number::format((int) $totals->people_count))
                ->description($this->getDashboardEventScopeLabel($event))
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Aanmeldingen', Number::format((int) $totals->enrollment_count))
                ->description($event->name)
                ->icon('heroicon-o-clipboard-document-list')
                ->color('info'),

            Stat::make('Event datum', $event->starts_at?->translatedFormat('d-m-Y') ?? '-')
                ->description($event->location ?: 'Locatie volgt')
                ->icon('heroicon-o-calendar-days')
                ->color('success'),
        ];
    }
}
