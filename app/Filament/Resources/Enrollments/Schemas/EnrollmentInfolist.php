<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EnrollmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('event.name')
                    ->label('Event'),
                TextEntry::make('full_name')
                    ->label('Name')
                    ->formatStateUsing(fn (?string $state): string => EnrollmentResource::formatFullNamePreview($state)),
                TextEntry::make('email')
                    ->label('Email')
                    ->formatStateUsing(fn (?string $state): string => EnrollmentResource::formatEmailPreview($state)),
                TextEntry::make('type'),
                TextEntry::make('student_association')
                    ->placeholder('-'),
                TextEntry::make('custom_student_association')
                    ->placeholder('-'),
                TextEntry::make('education')
                    ->placeholder('-'),
                TextEntry::make('custom_education')
                    ->placeholder('-'),
                TextEntry::make('partner_organization_type')
                    ->label('Soort BBQ-organisatie')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'partner' => 'Partner',
                        'vereniging' => 'Vereniging',
                        default => '-',
                    })
                    ->placeholder('-'),
                TextEntry::make('partner_organization_name')
                    ->label('Partner / vereniging BBQ')
                    ->placeholder('-'),
                IconEntry::make('is_organization_member')
                    ->label('Lid van organisatie')
                    ->boolean(),
                TextEntry::make('company_name')
                    ->label('Bedrijfsnaam')
                    ->placeholder('-'),
                TextEntry::make('guest_amount')
                    ->numeric(),
                TextEntry::make('dietary_preferences')
                    ->placeholder('-')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : (string) $state)
                    ->columnSpanFull(),
                IconEntry::make('requires_payment')
                    ->label('Betaling vereist')
                    ->boolean(),
                TextEntry::make('payment_status')
                    ->label('Betalingsstatus')
                    ->placeholder('-'),
                TextEntry::make('payment_amount')
                    ->label('Bedrag')
                    ->formatStateUsing(fn ($state, $record): string => $state
                        ? "{$record->payment_currency} {$state}"
                        : '-'),
                TextEntry::make('mollie_payment_link_url')
                    ->label('Betaallink')
                    ->url(fn ($state) => $state)
                    ->openUrlInNewTab()
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('mollie_payment_id')
                    ->label('Mollie betaling')
                    ->placeholder('-'),
                TextEntry::make('paid_at')
                    ->label('Paid at')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
