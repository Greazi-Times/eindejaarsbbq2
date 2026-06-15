<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                Repeater::make('eventPartners')
                    ->label('Partners')
                    ->relationship()
                    ->schema([
                        Select::make('partner_id')
                            ->label('Partner')
                            ->relationship('partner', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->columnSpan(2),
                        TextInput::make('free_guest_limit')
                            ->label('Inbegrepen personen')
                            ->helperText('Laat leeg voor geen limiet.')
                            ->integer()
                            ->minValue(0),
                        TextInput::make('over_limit_payment_amount')
                            ->label('Prijs per extra persoon')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('€'),
                    ])
                    ->columns(4)
                    ->defaultItems(0)
                    ->addActionLabel('Partner toevoegen')
                    ->helperText('Stel per partner het aantal inbegrepen personen en de prijs voor extra personen in.')
                    ->columnSpanFull(),
                Repeater::make('eventVerenigingen')
                    ->label('Verenigingen')
                    ->relationship()
                    ->schema([
                        Select::make('vereniging_id')
                            ->label('Vereniging')
                            ->relationship('vereniging', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->columnSpan(2),
                        TextInput::make('free_guest_limit')
                            ->label('Inbegrepen personen')
                            ->helperText('Laat leeg voor geen limiet.')
                            ->integer()
                            ->minValue(0),
                        TextInput::make('over_limit_payment_amount')
                            ->label('Prijs per extra persoon')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('€'),
                    ])
                    ->columns(4)
                    ->defaultItems(0)
                    ->addActionLabel('Vereniging toevoegen')
                    ->helperText('Stel per vereniging het aantal inbegrepen personen en de prijs voor extra personen in.')
                    ->columnSpanFull(),
            ]);
    }
}
