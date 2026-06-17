<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')
                    ->relationship('event', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('full_name')
                    ->required(),
                TextInput::make('email')
                    ->label('E-mailadres')
                    ->email()
                    ->required(),
                Select::make('type')
                    ->options([
                        'student' => 'Student',
                        'docent' => 'Docent',
                        'partner-bedrijf' => 'Partner / bedrijf',
                    ])
                    ->required(),
                TextInput::make('student_association'),
                TextInput::make('custom_student_association'),
                TextInput::make('education'),
                TextInput::make('custom_education'),
                Select::make('partner_organization_type')
                    ->label('Soort BBQ-organisatie')
                    ->options([
                        'partner' => 'Partner',
                        'vereniging' => 'Vereniging',
                    ]),
                TextInput::make('partner_organization_name')
                    ->label('Partner / vereniging BBQ'),
                Toggle::make('is_organization_member')
                    ->label('Lid van deze organisatie'),
                TextInput::make('company_name')
                    ->label('Bedrijfsnaam'),
                TextInput::make('guest_amount')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(3),
                Textarea::make('dietary_preferences')
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
                ...self::paymentFields(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function paymentFields(): array
    {
        if (! EnrollmentResource::canManagePayments()) {
            return [];
        }

        return [
            Fieldset::make('Betaling')
                ->schema([
                    Toggle::make('requires_payment')
                        ->label('Betaling vereist'),
                    Select::make('payment_status')
                        ->label('Betalingsstatus')
                        ->options([
                            'payment_pending' => 'Betaling wordt gestart',
                            'open' => 'Open',
                            'pending' => 'In behandeling',
                            'paid' => 'Betaald',
                            'failed' => 'Mislukt',
                            'canceled' => 'Geannuleerd',
                            'cancelled' => 'Geannuleerd',
                            'expired' => 'Verlopen',
                        ])
                        ->searchable(),
                    TextInput::make('payment_amount')
                        ->label('Bedrag')
                        ->placeholder('0.00')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('€'),
                    TextInput::make('payment_currency')
                        ->label('Valuta')
                        ->maxLength(3)
                        ->placeholder('EUR'),
                    TextInput::make('mollie_payment_link_id')
                        ->label('Mollie betaallink ID')
                        ->maxLength(255),
                    TextInput::make('mollie_payment_link_url')
                        ->label('Mollie betaallink')
                        ->url()
                        ->columnSpanFull(),
                    TextInput::make('mollie_payment_id')
                        ->label('Mollie betaling ID')
                        ->maxLength(255),
                    DateTimePicker::make('paid_at')
                        ->label('Betaald op'),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ])
                ->columnSpanFull(),
        ];
    }
}
