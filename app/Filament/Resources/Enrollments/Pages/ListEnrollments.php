<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEnrollments extends ListRecords
{
    protected static string $resource = EnrollmentResource::class;

    public bool $isPersonalDataVisible = false;

    public function togglePersonalDataVisibility(): void
    {
        if (! EnrollmentResource::canViewPersonalData()) {
            $this->isPersonalDataVisible = false;

            return;
        }

        $this->isPersonalDataVisible = ! $this->isPersonalDataVisible;
    }

    public function isPersonalDataVisible(): bool
    {
        return $this->isPersonalDataVisible && EnrollmentResource::canViewPersonalData();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
