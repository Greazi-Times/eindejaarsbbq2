<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ResolvesDashboardEvent;
use App\Models\Enrollment;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class OrganizationEnrollmentTotals extends TableWidget
{
    use HasWidgetShield, ResolvesDashboardEvent;

    protected static bool $isLazy = false;

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Personen per vereniging/partner')
            ->description(fn (): string => $this->getDashboardEventDescription($this->getDashboardEvent()))
            ->records(
                fn (?string $sortColumn, ?string $sortDirection): Collection => $this->getOrganizationRows(
                    $sortColumn,
                    $sortDirection,
                ),
            )
            ->columns([
                TextColumn::make('organization_name')
                    ->label('Vereniging / partner')
                    ->sortable(),

                TextColumn::make('organization_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Partner' => 'info',
                        'Vereniging' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('people_count')
                    ->label('Personen')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('enrollment_count')
                    ->label('Aanmeldingen')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Nog geen aanmeldingen')
            ->emptyStateDescription('Er zijn voor dit event nog geen aanmeldingen om te groeperen.');
    }

    /**
     * @return Collection<int, array<string, int | string>>
     */
    private function getOrganizationRows(?string $sortColumn, ?string $sortDirection): Collection
    {
        $event = $this->getDashboardEvent();

        if (! $event) {
            return collect();
        }

        $rows = Enrollment::query()
            ->whereBelongsTo($event)
            ->get([
                'id',
                'type',
                'student_association',
                'custom_student_association',
                'partner_organization_type',
                'partner_organization_name',
                'guest_amount',
            ])
            ->map(fn (Enrollment $enrollment): array => [
                ...$this->getOrganizationForEnrollment($enrollment),
                'people_count' => (int) $enrollment->guest_amount,
                'enrollment_count' => 1,
            ])
            ->groupBy(fn (array $row): string => "{$row['organization_type']}|{$row['organization_name']}")
            ->map(function (Collection $group): array {
                $first = $group->first();

                return [
                    '__key' => md5("{$first['organization_type']}|{$first['organization_name']}"),
                    'organization_name' => $first['organization_name'],
                    'organization_type' => $first['organization_type'],
                    'people_count' => $group->sum('people_count'),
                    'enrollment_count' => $group->sum('enrollment_count'),
                ];
            })
            ->values()
            ->sortBy([
                ['people_count', 'desc'],
                ['organization_name', 'asc'],
            ])
            ->values();

        if (filled($sortColumn) && filled($sortDirection)) {
            return $rows
                ->sortBy($sortColumn, SORT_REGULAR, $sortDirection === 'desc')
                ->values();
        }

        return $rows;
    }

    /**
     * @return array{organization_name: string, organization_type: string}
     */
    private function getOrganizationForEnrollment(Enrollment $enrollment): array
    {
        $partnerOrganizationName = $this->cleanOrganizationName($enrollment->partner_organization_name);

        if ($partnerOrganizationName) {
            return [
                'organization_name' => $partnerOrganizationName,
                'organization_type' => match ($enrollment->partner_organization_type) {
                    'partner' => 'Partner',
                    'vereniging' => 'Vereniging',
                    default => 'Overig',
                },
            ];
        }

        if ($enrollment->type === 'student') {
            $studentAssociation = $enrollment->student_association === 'anders'
                ? $enrollment->custom_student_association
                : $enrollment->student_association;

            if ($studentAssociation = $this->cleanOrganizationName($studentAssociation)) {
                return [
                    'organization_name' => $studentAssociation,
                    'organization_type' => 'Vereniging',
                ];
            }
        }

        return [
            'organization_name' => 'Geen vereniging/partner',
            'organization_type' => 'Overig',
        ];
    }

    private function cleanOrganizationName(?string $name): ?string
    {
        $name = trim((string) $name);

        return $name === '' ? null : $name;
    }
}
