<?php

namespace App\Filament\Resources\Verenigings\Pages;

use App\Filament\Resources\Verenigings\VerenigingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewVereniging extends ViewRecord
{
    protected static string $resource = VerenigingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
