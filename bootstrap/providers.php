<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\DashboardPanelProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    DashboardPanelProvider::class,
    FortifyServiceProvider::class,
];
