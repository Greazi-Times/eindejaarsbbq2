<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\Event;
use Carbon\CarbonInterface;

trait ResolvesDashboardEvent
{
    protected function getDashboardEvent(): ?Event
    {
        $now = now();

        return $this->getUpcomingDashboardEvent($now)
            ?? $this->getLastDashboardEvent($now);
    }

    protected function getDashboardEventScopeLabel(?Event $event): string
    {
        if (! $event) {
            return 'Geen event gevonden';
        }

        if ($event->starts_at?->isFuture()) {
            return 'Aankomend event';
        }

        return 'Laatste event';
    }

    protected function getDashboardEventDescription(?Event $event): string
    {
        if (! $event) {
            return 'Er is nog geen aankomend of vorig event beschikbaar.';
        }

        $date = $event->starts_at?->translatedFormat('j F Y') ?? 'datum niet ingesteld';

        return "{$this->getDashboardEventScopeLabel($event)}: {$event->name} ({$date})";
    }

    protected function getUpcomingDashboardEvent(CarbonInterface $now): ?Event
    {
        return Event::query()
            ->whereNotNull('starts_at')
            ->where('starts_at', '>=', $now)
            ->orderBy('starts_at')
            ->first();
    }

    protected function getLastDashboardEvent(CarbonInterface $now): ?Event
    {
        return Event::query()
            ->whereNotNull('starts_at')
            ->where('starts_at', '<', $now)
            ->orderByDesc('starts_at')
            ->first();
    }
}
