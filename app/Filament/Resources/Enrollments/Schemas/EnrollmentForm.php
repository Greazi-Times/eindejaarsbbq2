<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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
                    ->label('Email address')
                    ->email()
                    ->required(),
                Select::make('type')
                    ->options([
                        'student' => 'Student',
                        'docent' => 'Docent',
                        'partner-bedrijf' => 'Partner / Bedrijf',
                    ])
                    ->required(),
                TextInput::make('student_association'),
                TextInput::make('custom_student_association'),
                TextInput::make('education'),
                TextInput::make('custom_education'),
                TextInput::make('company_name'),
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
