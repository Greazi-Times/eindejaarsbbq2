<?php

namespace App\Filament\Resources\Events\RelationManagers;

use App\Filament\Resources\Partners\PartnerResource;
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

class PartnersRelationManager extends RelationManager
{
    protected static string $relationship = 'partners';

    protected static ?string $relatedResource = PartnerResource::class;

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
                    ->label('Prijs extra personen')
                    ->money('EUR')
                    ->placeholder('-'),
                IconColumn::make('student_payment_active')
                    ->label('Studenten betalen')
                    ->boolean()
                    ->state(fn ($record): bool => (float) ($record->pivot?->student_payment_amount ?? 0) > 0),
                TextColumn::make('pivot.student_payment_amount')
                    ->label('Studentenprijs')
                    ->money('EUR')
                    ->placeholder('Gratis'),
                IconColumn::make('docent_payment_active')
                    ->label('Docenten betalen')
                    ->boolean()
                    ->state(fn ($record): bool => (float) ($record->pivot?->docent_payment_amount ?? 0) > 0),
                TextColumn::make('pivot.docent_payment_amount')
                    ->label('Docentenprijs')
                    ->money('EUR')
                    ->placeholder('Gratis'),
                IconColumn::make('pivot.members_must_pay')
                    ->label('Leden betalen')
                    ->boolean(),
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
                    ->modalHeading('Partnerlimiet bewerken')
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
                        ->label('Extra personen')
                        ->placeholder('0.00')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('€'),
                ]),
            Fieldset::make('Studenten')
                ->schema([
                    TextInput::make('student_payment_amount')
                        ->label('Prijs')
                        ->placeholder('0.00')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('€'),
                ]),
            Fieldset::make('Docenten')
                ->schema([
                    TextInput::make('docent_payment_amount')
                        ->label('Prijs')
                        ->placeholder('0.00')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('€'),
                ]),
            Fieldset::make('Leden')
                ->schema([
                    Toggle::make('members_must_pay')
                        ->label('Leden betalen')
                        ->helperText('Uit: leden zijn gratis. Aan: leden betalen de studenten- of docentenprijs, of anders het bedrag bij Extra personen.')
                        ->default(false),
                ]),
        ];
    }
}
