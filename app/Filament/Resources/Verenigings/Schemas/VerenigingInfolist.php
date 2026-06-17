<?php

namespace App\Filament\Resources\Verenigings\Schemas;

use App\Support\EducationOptions;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VerenigingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('education')
                    ->label('Opleiding')
                    ->formatStateUsing(fn (?string $state): ?string => EducationOptions::label($state))
                    ->placeholder('-'),
                ImageEntry::make('logo')
                    ->label('Logo')
                    ->disk('public')
                    ->placeholder('-'),
                TextEntry::make('website')
                    ->url(fn (?string $state): ?string => self::safeHttpsUrl($state))
                    ->openUrlInNewTab()
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    private static function safeHttpsUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $url = trim($url);

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        return $scheme === 'https' && $host ? $url : null;
    }
}
