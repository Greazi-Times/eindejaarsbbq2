<?php

namespace App\Filament\Resources\Verenigings\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VerenigingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('logo')
                    ->image()
                    ->disk('public')
                    ->directory('verenigingen')
                    ->visibility('public')
                    ->imageEditor(),
                TextInput::make('website')
                    ->url()
                    ->maxLength(255),
                Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }
}
