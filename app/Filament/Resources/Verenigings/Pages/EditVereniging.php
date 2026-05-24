<?php

namespace App\Filament\Resources\Verenigings\Pages;

use App\Filament\Resources\Verenigings\VerenigingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditVereniging extends EditRecord
{
    protected static string $resource = VerenigingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
