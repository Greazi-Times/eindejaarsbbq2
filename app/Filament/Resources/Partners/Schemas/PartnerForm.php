<?php

namespace App\Filament\Resources\Partners\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(1024)
                    ->disk('public')
                    ->directory('partners')
                    ->visibility('public')
                    ->imageEditor(),
                TextInput::make('website')
                    ->url()
                    ->rules(['nullable', 'url:https'])
                    ->maxLength(255),
                Toggle::make('show_on_registration_form')
                    ->label('Toon in aanmeldformulier')
                    ->helperText('Alleen partners met deze optie aan zijn zichtbaar voor studenten en docenten.')
                    ->default(false),
                Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }
}
