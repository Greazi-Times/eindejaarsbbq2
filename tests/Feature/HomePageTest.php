<?php

use App\Models\Event;
use App\Models\Partner;
use App\Models\Vereniging;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

it('passes public logo urls to the home page', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $partner = Partner::query()->create([
        'name' => 'Acme Partner',
        'logo' => 'partners/acme.png',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Study Association',
        'logo' => 'verenigingen/study.png',
    ]);

    $event->partners()->attach($partner);
    $event->verenigingen()->attach($vereniging);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('activeEvent.partners.0.logo', Storage::disk('public')->url('partners/acme.png'))
            ->where('activeEvent.verenigingen.0.logo', Storage::disk('public')->url('verenigingen/study.png'))
        );
});

it('copies existing private logo uploads to public storage', function () {
    Storage::fake('local');
    Storage::fake('public');

    Storage::disk('local')->put('partners/acme.png', 'partner-logo');
    Storage::disk('local')->put('verenigingen/study.png', 'vereniging-logo');

    Partner::query()->create([
        'name' => 'Acme Partner',
        'logo' => 'partners/acme.png',
    ]);

    Vereniging::query()->create([
        'name' => 'Study Association',
        'logo' => 'verenigingen/study.png',
    ]);

    $migration = include database_path('migrations/2026_06_10_000000_move_logo_uploads_to_public_disk.php');

    $migration->up();

    Storage::disk('public')->assertExists('partners/acme.png');
    Storage::disk('public')->assertExists('verenigingen/study.png');

    expect(Storage::disk('public')->get('partners/acme.png'))->toBe('partner-logo')
        ->and(Storage::disk('public')->get('verenigingen/study.png'))->toBe('vereniging-logo');
});
