<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                TextInput::make('location'),
                TextInput::make('student_payment_amount')
                    ->label('Studentenprijs')
                    ->helperText('Bedrag dat studenten moeten betalen wanneer hun vereniging betaling vereist.')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('€'),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('partners')
                    ->label('Partners')
                    ->relationship('partners', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->helperText('Selecteer de partners die verantwoordelijk zijn voor dit event.'),
                Select::make('verenigingen')
                    ->label('Verenigingen')
                    ->relationship('verenigingen', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->helperText('Selecteer de verenigingen die dit event organiseren.'),
            ]);
    }
}
