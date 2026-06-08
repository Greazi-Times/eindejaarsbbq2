<?php

namespace App\Filament\Resources\Verenigings\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
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
                    ->directory('verenigingen')
                    ->imageEditor(),
                TextInput::make('website')
                    ->url()
                    ->maxLength(255),
                Toggle::make('students_must_pay')
                    ->label('Studenten moeten betalen')
                    ->helperText('Schakel dit in wanneer studenten na het aanmelden moeten betalen.')
                    ->default(false),
                Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }
}
