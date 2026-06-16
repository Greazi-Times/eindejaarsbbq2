<?php

namespace App\Filament\Resources\Partners\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                FileUpload::make('logo')
                    ->image()
                    ->disk('public')
                    ->directory('partners')
                    ->visibility('public')
                    ->imageEditor(),
                TextInput::make('website')
                    ->url(),
                Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }
}
