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
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Fieldset;
use Filament\Tables\Columns\IconColumn;
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
                IconColumn::make('pivot.students_always_pay')
                    ->label('Studenten betalen')
                    ->boolean(),
                TextColumn::make('pivot.student_payment_amount')
                    ->label('Studentenprijs')
                    ->money('EUR')
                    ->placeholder('Gratis'),
                IconColumn::make('pivot.docents_always_pay')
                    ->label('Docenten betalen')
                    ->boolean(),
                TextColumn::make('pivot.docent_payment_amount')
                    ->label('Docentenprijs')
                    ->money('EUR')
                    ->placeholder('Gratis'),
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
            Fieldset::make('Capaciteit')
                ->schema([
                    TextInput::make('free_guest_limit')
                        ->label('Inbegrepen')
                        ->placeholder('Geen limiet')
                        ->integer()
                        ->minValue(0),
                    TextInput::make('over_limit_payment_amount')
                        ->label('Extra persoon')
                        ->placeholder('0.00')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('€'),
                ]),
            Fieldset::make('Studenten')
                ->schema([
                    Toggle::make('students_always_pay')
                        ->label('Altijd betalen')
                        ->default(false),
                    TextInput::make('student_payment_amount')
                        ->label('Prijs')
                        ->placeholder('0.00')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('€'),
                ]),
            Fieldset::make('Docenten')
                ->schema([
                    Toggle::make('docents_always_pay')
                        ->label('Altijd betalen')
                        ->default(false),
                    TextInput::make('docent_payment_amount')
                        ->label('Prijs')
                        ->placeholder('0.00')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('€'),
                ]),
        ];
    }
}
