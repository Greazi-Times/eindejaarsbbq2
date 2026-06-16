<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Models\Partner;
use App\Models\Vereniging;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                TextInput::make('location'),
                Textarea::make('description')
                    ->columnSpanFull(),
                Repeater::make('eventPartners')
                    ->label('Partners')
                    ->relationship()
                    ->schema(self::organizationFields(
                        selectName: 'partner_id',
                        selectLabel: 'Partner',
                        relationship: 'partner',
                    ))
                    ->itemLabel(fn (array $state): ?string => self::organizationItemLabel(
                        state: $state,
                        key: 'partner_id',
                        model: Partner::class,
                    ))
                    ->columns(1)
                    ->defaultItems(0)
                    ->addActionLabel('Partner toevoegen')
                    ->helperText('Stel per partner capaciteit, extra-personenprijs en normale rolprijzen in.')
                    ->columnSpanFull(),
                Repeater::make('eventVerenigingen')
                    ->label('Verenigingen')
                    ->relationship()
                    ->schema(self::organizationFields(
                        selectName: 'vereniging_id',
                        selectLabel: 'Vereniging',
                        relationship: 'vereniging',
                    ))
                    ->itemLabel(fn (array $state): ?string => self::organizationItemLabel(
                        state: $state,
                        key: 'vereniging_id',
                        model: Vereniging::class,
                    ))
                    ->columns(1)
                    ->defaultItems(0)
                    ->addActionLabel('Vereniging toevoegen')
                    ->helperText('Stel per vereniging capaciteit, extra-personenprijs en normale rolprijzen in.')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function organizationFields(string $selectName, string $selectLabel, string $relationship): array
    {
        return [
            Select::make($selectName)
                ->label($selectLabel)
                ->relationship($relationship, 'name')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                ->columnSpanFull(),
            Grid::make([
                'default' => 1,
                'xl' => 3,
            ])
                ->schema([
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
                ])
                ->columnSpanFull(),
        ];
    }

    /**
     * @param  class-string<Partner|Vereniging>  $model
     */
    private static function organizationItemLabel(array $state, string $key, string $model): ?string
    {
        $id = $state[$key] ?? null;

        if (! $id) {
            return null;
        }

        return $model::query()->whereKey($id)->value('name');
    }
}
