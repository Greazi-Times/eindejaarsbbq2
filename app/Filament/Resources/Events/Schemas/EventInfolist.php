<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('starts_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('ends_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('location')
                    ->placeholder('-'),
                TextEntry::make('student_payment_amount')
                    ->label('Studentenprijs')
                    ->money('EUR')
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                Section::make('Partners')
                    ->schema([
                        TextEntry::make('partners.name')
                            ->label('Partners')
                            ->badge()
                            ->separator(','),
                    ])
                    ->columnSpanFull(),
                Section::make('Verenigingen')
                    ->schema([
                        TextEntry::make('verenigingen.name')
                            ->label('Verenigingen')
                            ->badge()
                            ->separator(','),
                    ])
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
