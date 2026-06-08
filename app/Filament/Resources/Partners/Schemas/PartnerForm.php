<?php

namespace App\Filament\Resources\Partners\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
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
                    ->directory('partners')
                    ->imageEditor(),
                TextInput::make('website')
                    ->url(),
                Toggle::make('students_must_pay')
                    ->label('Studenten moeten betalen')
                    ->helperText('Schakel dit in wanneer studenten na het aanmelden moeten betalen.')
                    ->default(false),
                Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }
}
