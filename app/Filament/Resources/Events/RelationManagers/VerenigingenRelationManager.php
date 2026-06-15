<?php

namespace App\Filament\Resources\Events\RelationManagers;

use App\Filament\Resources\Verenigings\VerenigingResource;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VerenigingenRelationManager extends RelationManager
{
    protected static string $relationship = 'verenigingen';

    protected static ?string $relatedResource = VerenigingResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pivot.free_guest_limit')
                    ->label('Inbegrepen personen')
                    ->numeric()
                    ->placeholder('Geen limiet'),
                TextColumn::make('pivot.over_limit_payment_amount')
                    ->label('Prijs per extra persoon')
                    ->money('EUR')
                    ->placeholder('-'),
            ])
            ->headerActions([
                CreateAction::make(),
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        ...self::capacityFields(),
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Verenigingslimiet bewerken')
                    ->schema(self::capacityFields()),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }

    private static function capacityFields(): array
    {
        return [
            TextInput::make('free_guest_limit')
                ->label('Inbegrepen personen')
                ->helperText('Aantal personen dat zonder extra betaling mag aanmelden. Laat leeg voor geen limiet.')
                ->integer()
                ->minValue(0),
            TextInput::make('over_limit_payment_amount')
                ->label('Prijs per extra persoon')
                ->helperText('Bedrag per persoon boven de limiet.')
                ->numeric()
                ->minValue(0)
                ->prefix('€'),
        ];
    }
}
