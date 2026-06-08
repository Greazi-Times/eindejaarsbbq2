<?php

namespace App\Filament\Resources\Enrollments\Tables;

use App\Models\Enrollment;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class EnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event.name')
                    ->label('Event')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('full_name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('E-mailadres')
                    ->searchable(),
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('student_association')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('education')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('company_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('guest_amount')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('requires_payment')
                    ->label('Betaling vereist')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->label('Betalingsstatus')
                    ->badge()
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('payment_amount')
                    ->label('Bedrag')
                    ->formatStateUsing(fn ($state, $record): string => $state
                        ? "{$record->payment_currency} {$state}"
                        : '-')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('paid_at')
                    ->label('Betaald op')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                Action::make('exportCsv')
                    ->label('CSV exporteren')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        return response()->streamDownload(function (): void {
                            $handle = fopen('php://output', 'w');

                            fputcsv($handle, [
                                'Naam',
                                'Type',
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
                                            $enrollment->full_name,
                                            match ($enrollment->type) {
                                                'student' => 'Student',
                                                'docent' => 'Docent',
                                                'partner-bedrijf' => 'Partner',
                                                default => Str::headline((string) $enrollment->type),
                                            },
                                            $enrollment->guest_amount,
                                            $dietaryPreferences ?: '-',
                                        ]);
                                    }
                                });

                            fclose($handle);
                        }, 'enrollments-export.csv', [
                            'Content-Type' => 'text/csv',
                        ]);
                    }),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
