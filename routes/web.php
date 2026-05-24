<?php

use App\Models\Event;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {

    $activeEvent = Event::query()
        ->with([
            'partners' => fn ($query) => $query->orderBy('name'),
            'verenigingen' => fn ($query) => $query->orderBy('name'),
        ])
        ->whereNotNull('starts_at')
        ->where('starts_at', '>=', now())
        ->orderBy('starts_at')
        ->first();

    return Inertia::render('Home', [
        'activeEvent' => $activeEvent ? [
            'id' => $activeEvent->id,
            'name' => $activeEvent->name,
            'starts_at' => $activeEvent->starts_at?->format('Y-m-d H:i:s'),
            'ends_at' => $activeEvent->ends_at?->format('Y-m-d H:i:s'),
            'location' => $activeEvent->location,
            'description' => $activeEvent->description,
            'partners' => $activeEvent->partners->map(fn ($partner) => [
                'id' => $partner->id,
                'name' => $partner->name,
                'logo' => $partner->logo,
                'website' => $partner->website,
            ])->values(),

            'verenigingen' => $activeEvent->verenigingen->map(fn ($vereniging) => [
                'id' => $vereniging->id,
                'name' => $vereniging->name,
                'logo' => $vereniging->logo,
                'website' => $vereniging->website,
            ])->values(),
        ] : null,
    ]);
});

use App\Http\Controllers\EnrollmentController;

Route::post('/aanmelden', [EnrollmentController::class, 'store'])
    ->name('enrollments.store');
