<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Naam'),

                TextEntry::make('email')
                    ->label('E-mailadres'),

                TextEntry::make('roles.name')
                    ->label('Rollen')
                    ->badge(),

                TextEntry::make('created_at')
                    ->label('Aangemaakt')
                    ->dateTime(),
            ]);
    }
}
