<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
