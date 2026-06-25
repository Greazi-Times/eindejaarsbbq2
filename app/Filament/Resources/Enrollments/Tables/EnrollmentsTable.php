<?php

namespace App\Filament\Resources\Enrollments\Tables;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\Enrollments\Pages\ListEnrollments;
use App\Models\Enrollment;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EnrollmentsTable
{
    private const FAILED_PAYMENT_STATUSES = [
        'failed',
        'canceled',
        'cancelled',
        'expired',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('event.name')
                    ->label('Event')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('full_name')
                    ->label('Name')
                    ->formatStateUsing(fn (?string $state, $livewire): string => static::formatFullName($state, $livewire))
                    ->searchable(fn (): bool => EnrollmentResource::canViewPersonalData()),
                TextColumn::make('email')
                    ->label('Email')
                    ->formatStateUsing(fn (?string $state, $livewire): string => static::formatEmail($state, $livewire))
                    ->searchable(fn (): bool => EnrollmentResource::canViewPersonalData()),
                TextColumn::make('organization')
                    ->label('Association / organization')
                    ->state(fn (Enrollment $record): string => static::enrollmentOrganization($record))
                    ->searchable([
                        'student_association',
                        'custom_student_association',
                        'partner_organization_name',
                        'company_name',
                    ]),
                TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (?string $state): string => static::formatType($state))
                    ->searchable(),
                TextColumn::make('student_association')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('education')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('partner_organization_type')
                    ->label('Soort BBQ-organisatie')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'partner' => 'Partner',
                        'vereniging' => 'Vereniging',
                        default => '-',
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('partner_organization_name')
                    ->label('Partner / vereniging BBQ')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_organization_member')
                    ->label('Lid')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('company_name')
                    ->label('Bedrijfsnaam')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('guest_amount')
                    ->label('Guest Amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('dietary_preferences')
                    ->label('Diet wishes')
                    ->state(fn (Enrollment $record): array => static::formatDietaryPreferences($record->dietary_preferences))
                    ->badge()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
                IconColumn::make('requires_payment')
                    ->label('Betaling vereist')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('payment_status')
                    ->label('Payment status')
                    ->icon(fn (Enrollment $record): string => static::paymentStatusIcon($record))
                    ->color(fn (Enrollment $record): string => static::paymentStatusColor($record))
                    ->tooltip(fn (Enrollment $record): string => static::paymentStatusTooltip($record))
                    ->sortable(),
                TextColumn::make('payment_amount')
                    ->label('Bedrag')
                    ->formatStateUsing(fn ($state, $record): string => $state
                        ? "{$record->payment_currency} {$state}"
                        : '-')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('paid_at')
                    ->label('Paid at')
                    ->date()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->when(
                $table->getLivewire() instanceof ListEnrollments,
                fn (Table $table): Table => $table
                    ->filters([
                        SelectFilter::make('event_id')
                            ->label('Event')
                            ->options(fn (): array => Event::query()
                                ->orderByDesc('starts_at')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->default(fn (): ?int => static::defaultEventId())
                            ->selectablePlaceholder(false)
                            ->searchable()
                            ->preload(),
                        SelectFilter::make('organization')
                            ->label('Association / organization')
                            ->options(fn (): array => static::organizationFilterOptions())
                            ->query(fn (Builder $query, array $data): Builder => static::applyOrganizationFilter(
                                $query,
                                $data['value'] ?? null,
                            ))
                            ->searchable()
                            ->preload(),
                        TernaryFilter::make('requires_payment')
                            ->label('Needs payment')
                            ->trueLabel('Yes')
                            ->falseLabel('No'),
                        TernaryFilter::make('dietary_preferences')
                            ->label('Diet wishes filled in')
                            ->trueLabel('Yes')
                            ->falseLabel('No')
                            ->queries(
                                true: fn (Builder $query): Builder => static::applyDietaryPreferencesFilter($query, true),
                                false: fn (Builder $query): Builder => static::applyDietaryPreferencesFilter($query, false),
                            ),
                        SelectFilter::make('payment_status_group')
                            ->label('Payment status')
                            ->options([
                                'not_needed' => 'Not needed',
                                'paid' => 'Success',
                                'failed' => 'Failed',
                                'waiting' => 'Waiting',
                            ])
                            ->query(fn (Builder $query, array $data): Builder => static::applyPaymentStatusFilter(
                                $query,
                                $data['value'] ?? null,
                            )),
                    ], FiltersLayout::AboveContent)
                    ->deferFilters(false)
                    ->filtersFormColumns(4)
                    ->persistFiltersInSession(),
            )
            ->recordActions([
                Action::make('viewPersonalData')
                    ->label('View personal data')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Enrollment personal data')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Name')
                            ->placeholder('-')
                            ->copyable(),
                        TextEntry::make('email')
                            ->label('Email')
                            ->placeholder('-')
                            ->copyable(),
                        TextEntry::make('organization')
                            ->label('Association / organization')
                            ->state(fn (Enrollment $record): string => static::enrollmentOrganization($record)),
                        TextEntry::make('type')
                            ->label('Type')
                            ->formatStateUsing(fn (?string $state): string => static::formatType($state)),
                    ])
                    ->visible(fn (): bool => EnrollmentResource::canViewPersonalData()),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                Action::make('togglePersonalDataVisibility')
                    ->label(fn ($livewire): string => static::isPersonalDataVisible($livewire)
                        ? 'Hide personal details'
                        : 'Show personal details')
                    ->icon(fn ($livewire): string => static::isPersonalDataVisible($livewire)
                        ? 'heroicon-o-eye-slash'
                        : 'heroicon-o-eye')
                    ->color(fn ($livewire): string => static::isPersonalDataVisible($livewire) ? 'warning' : 'gray')
                    ->action(function ($livewire): void {
                        if ($livewire instanceof ListEnrollments) {
                            $livewire->togglePersonalDataVisibility();
                        }
                    })
                    ->visible(fn ($livewire): bool => $livewire instanceof ListEnrollments
                        && EnrollmentResource::canViewPersonalData()),
                Action::make('exportCsv')
                    ->label('CSV exporteren')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        return response()->streamDownload(function (): void {
                            $handle = fopen('php://output', 'w');

                            fputcsv($handle, [
                                'Naam',
                                'Type',
                                'Soort BBQ-organisatie',
                                'Partner / vereniging BBQ',
                                'Bedrijfsnaam',
                                'Personen',
                                'Dieetwensen',
                            ]);

                            Enrollment::query()
                                ->orderBy('full_name')
                                ->chunk(100, function ($enrollments) use ($handle): void {
                                    foreach ($enrollments as $enrollment) {
                                        $dietaryPreferences = $enrollment->dietary_preferences;

                                        if (is_array($dietaryPreferences)) {
                                            $dietaryPreferences = collect($dietaryPreferences)
                                                ->flatten()
                                                ->filter()
                                                ->implode(', ');
                                        }

                                        fputcsv($handle, [
                                            static::escapeCsvCell($enrollment->full_name),
                                            match ($enrollment->type) {
                                                'student' => 'Student',
                                                'docent' => 'Docent',
                                                'partner-bedrijf' => 'Partner',
                                                default => Str::headline((string) $enrollment->type),
                                            },
                                            match ($enrollment->partner_organization_type) {
                                                'partner' => 'Partner',
                                                'vereniging' => 'Vereniging',
                                                default => '-',
                                            },
                                            static::escapeCsvCell($enrollment->partner_organization_name ?: '-'),
                                            static::escapeCsvCell($enrollment->company_name ?: '-'),
                                            $enrollment->guest_amount,
                                            static::escapeCsvCell($dietaryPreferences ?: '-'),
                                        ]);
                                    }
                                });

                            fclose($handle);
                        }, 'enrollments-export.csv', [
                            'Content-Type' => 'text/csv',
                        ]);
                    })
                    ->visible(fn (): bool => (Auth::user()?->can('ExportEnrollments') ?? false)
                        && EnrollmentResource::canViewPersonalData()),
                Action::make('exportEmailList')
                    ->label('E-maillijst exporteren')
                    ->icon('heroicon-o-envelope')
                    ->action(function () {
                        return response()->streamDownload(function (): void {
                            $handle = fopen('php://output', 'w');

                            fputcsv($handle, ['E-mail']);

                            Enrollment::query()
                                ->whereNotNull('email')
                                ->where('email', '!=', '')
                                ->orderBy('email')
                                ->pluck('email')
                                ->unique()
                                ->each(fn (string $email): bool|int => fputcsv($handle, [
                                    static::escapeCsvCell($email),
                                ]));

                            fclose($handle);
                        }, 'enrollments-email-list.csv', [
                            'Content-Type' => 'text/csv',
                        ]);
                    })
                    ->visible(fn (): bool => (Auth::user()?->can('ExportEnrollments') ?? false)
                        && EnrollmentResource::canViewPersonalData()),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function escapeCsvCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        if (preg_match('/^\s*[=+\-@]/', $value) === 1) {
            return "'{$value}";
        }

        return $value;
    }

    private static function defaultEventId(): ?int
    {
        $now = now();

        $event = Event::query()
            ->whereNotNull('starts_at')
            ->where('starts_at', '>=', $now)
            ->orderBy('starts_at')
            ->first()
            ?? Event::query()
                ->whereNotNull('starts_at')
                ->where('starts_at', '<', $now)
                ->orderByDesc('starts_at')
                ->first()
            ?? Event::query()
                ->latest('id')
                ->first();

        return $event?->id;
    }

    private static function formatType(?string $state): string
    {
        return match ($state) {
            'student' => 'Student',
            'docent' => 'Docent',
            'partner-bedrijf' => 'Partner',
            default => Str::headline((string) $state) ?: '-',
        };
    }

    private static function formatFullName(?string $name, mixed $livewire): string
    {
        if (static::isPersonalDataVisible($livewire)) {
            return filled($name) ? $name : '-';
        }

        return EnrollmentResource::formatFullNamePreview($name);
    }

    private static function formatEmail(?string $email, mixed $livewire): string
    {
        if (static::isPersonalDataVisible($livewire)) {
            return filled($email) ? $email : '-';
        }

        return EnrollmentResource::formatEmailPreview($email);
    }

    private static function isPersonalDataVisible(mixed $livewire): bool
    {
        return $livewire instanceof ListEnrollments && $livewire->isPersonalDataVisible();
    }

    private static function enrollmentOrganization(Enrollment $record): string
    {
        if (filled($record->partner_organization_name)) {
            return $record->partner_organization_name;
        }

        if (filled($record->custom_student_association)) {
            return $record->custom_student_association;
        }

        if (filled($record->student_association)) {
            return $record->student_association;
        }

        if ($record->type === 'docent' && filled($record->education)) {
            $verenigingName = $record->event
                ?->verenigingen()
                ->where('education', $record->education)
                ->value('name');

            if (filled($verenigingName)) {
                return $verenigingName;
            }
        }

        if (filled($record->company_name)) {
            return $record->company_name;
        }

        return '-';
    }

    /**
     * @return array<string, string>
     */
    private static function organizationFilterOptions(): array
    {
        return collect([
            ...Enrollment::query()
                ->whereNotNull('partner_organization_name')
                ->pluck('partner_organization_name')
                ->all(),
            ...Enrollment::query()
                ->whereNotNull('custom_student_association')
                ->pluck('custom_student_association')
                ->all(),
            ...Enrollment::query()
                ->whereNotNull('student_association')
                ->pluck('student_association')
                ->all(),
            ...Enrollment::query()
                ->whereNotNull('company_name')
                ->pluck('company_name')
                ->all(),
        ])
            ->filter(fn (?string $value): bool => filled($value))
            ->unique()
            ->sort()
            ->mapWithKeys(fn (string $value): array => [$value => $value])
            ->all();
    }

    private static function applyOrganizationFilter(Builder $query, ?string $value): Builder
    {
        if (blank($value)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($value): void {
            $query
                ->where('partner_organization_name', $value)
                ->orWhere('custom_student_association', $value)
                ->orWhere('student_association', $value)
                ->orWhere('company_name', $value);
        });
    }

    private static function applyPaymentStatusFilter(Builder $query, ?string $value): Builder
    {
        return match ($value) {
            'not_needed' => $query->where('requires_payment', false),
            'paid' => $query
                ->where('requires_payment', true)
                ->where('payment_status', 'paid'),
            'failed' => $query
                ->where('requires_payment', true)
                ->whereIn('payment_status', self::FAILED_PAYMENT_STATUSES),
            'waiting' => $query
                ->where('requires_payment', true)
                ->where(function (Builder $query): void {
                    $query
                        ->whereNull('payment_status')
                        ->orWhereNotIn('payment_status', [
                            'paid',
                            ...self::FAILED_PAYMENT_STATUSES,
                        ]);
                }),
            default => $query,
        };
    }

    private static function applyDietaryPreferencesFilter(Builder $query, bool $filled): Builder
    {
        $emptyValues = ['[]', '{}', 'null'];

        if ($filled) {
            return $query
                ->whereNotNull('dietary_preferences')
                ->whereNotIn('dietary_preferences', $emptyValues);
        }

        return $query->where(function (Builder $query) use ($emptyValues): void {
            $query
                ->whereNull('dietary_preferences')
                ->orWhereIn('dietary_preferences', $emptyValues);
        });
    }

    /**
     * @param  array<string, array<int, string>>|null  $preferences
     * @return array<int, string>
     */
    private static function formatDietaryPreferences(?array $preferences): array
    {
        if (blank($preferences)) {
            return [];
        }

        $labels = [
            'vegetarian' => 'Vegetarian',
            'vegan' => 'Vegan',
            'halal' => 'Halal',
            'gluten-free' => 'Gluten-free',
        ];

        return collect($preferences)
            ->flatMap(function (mixed $personPreferences, string|int $person): array {
                if (! is_array($personPreferences)) {
                    return [];
                }

                $personNumber = preg_match('/(\d+)$/', (string) $person, $matches) === 1
                    ? $matches[1]
                    : (string) $person;

                return collect($personPreferences)
                    ->filter(fn (mixed $preference): bool => filled($preference))
                    ->map(fn (string $preference): string => "Person {$personNumber}: {$preference}")
                    ->all();
            })
            ->map(function (string $preference) use ($labels): string {
                [$person, $value] = array_pad(explode(': ', $preference, 2), 2, '');

                return "{$person}: ".($labels[$value] ?? Str::headline($value));
            })
            ->values()
            ->all();
    }

    private static function paymentStatusIcon(Enrollment $record): string
    {
        if (! $record->requires_payment) {
            return 'heroicon-o-minus';
        }

        return match ($record->payment_status) {
            'paid' => 'heroicon-o-check',
            'failed', 'canceled', 'cancelled', 'expired' => 'heroicon-o-x-mark',
            default => 'heroicon-o-exclamation-triangle',
        };
    }

    private static function paymentStatusColor(Enrollment $record): string
    {
        if (! $record->requires_payment) {
            return 'gray';
        }

        return match ($record->payment_status) {
            'paid' => 'success',
            'failed', 'canceled', 'cancelled', 'expired' => 'danger',
            default => 'warning',
        };
    }

    private static function paymentStatusTooltip(Enrollment $record): string
    {
        if (! $record->requires_payment) {
            return 'Payment not needed';
        }

        return match ($record->payment_status) {
            'paid' => 'Payment successful',
            'failed', 'canceled', 'cancelled', 'expired' => 'Payment failed',
            default => 'Waiting for payment',
        };
    }
}
