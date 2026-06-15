<?php

namespace App\Filament\Resources\Verenigings;

use App\Filament\Resources\Verenigings\Pages\CreateVereniging;
use App\Filament\Resources\Verenigings\Pages\EditVereniging;
use App\Filament\Resources\Verenigings\Pages\ListVerenigings;
use App\Filament\Resources\Verenigings\Pages\ViewVereniging;
use App\Filament\Resources\Verenigings\Schemas\VerenigingForm;
use App\Filament\Resources\Verenigings\Schemas\VerenigingInfolist;
use App\Filament\Resources\Verenigings\Tables\VerenigingsTable;
use App\Models\Vereniging;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VerenigingResource extends Resource
{
    protected static ?string $model = Vereniging::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|\UnitEnum|null $navigationGroup = 'Organizations & Partners';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return VerenigingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VerenigingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VerenigingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVerenigings::route('/'),
            'create' => CreateVereniging::route('/create'),
            'view' => ViewVereniging::route('/{record}'),
            'edit' => EditVereniging::route('/{record}/edit'),
        ];
    }
}
