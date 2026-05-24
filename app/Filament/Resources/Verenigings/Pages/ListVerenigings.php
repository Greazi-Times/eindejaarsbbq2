<?php

namespace App\Filament\Resources\Verenigings\Pages;

use App\Filament\Resources\Verenigings\VerenigingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVerenigings extends ListRecords
{
    protected static string $resource = VerenigingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
