<?php

use App\Models\Enrollment;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('guests are redirected to the login page', function () {
    $response = $this->get(route('filament.dashboard.pages.dashboard'));
    $response->assertRedirect(route('filament.dashboard.auth.login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('filament.dashboard.pages.dashboard'));
    $response->assertOk();
});

test('dashboard displays enrollment totals for the upcoming event', function () {
    Carbon::setTestNow('2026-06-15 12:00:00');

    $user = User::factory()->create();

    $pastEvent = Event::create([
        'name' => 'Vorige BBQ',
        'starts_at' => now()->subMonth(),
        'location' => 'Oude locatie',
    ]);

    $upcomingEvent = Event::create([
        'name' => 'Aankomende BBQ',
        'starts_at' => now()->addMonth(),
        'location' => 'Nieuwe locatie',
    ]);

    Enrollment::create([
        'event_id' => $upcomingEvent->id,
        'full_name' => 'Student Een',
        'email' => 'student@example.com',
        'type' => 'student',
        'student_association' => 'Vereniging Alpha',
        'guest_amount' => 2,
    ]);

    Enrollment::create([
        'event_id' => $upcomingEvent->id,
        'full_name' => 'Partner Een',
        'email' => 'partner@example.com',
        'type' => 'partner-bedrijf',
        'partner_organization_type' => 'partner',
        'partner_organization_name' => 'Partner Beta',
        'company_name' => 'Beta BV',
        'guest_amount' => 3,
    ]);

    Enrollment::create([
        'event_id' => $pastEvent->id,
        'full_name' => 'Oude Aanmelding',
        'email' => 'old@example.com',
        'type' => 'student',
        'student_association' => 'Oude Vereniging',
        'guest_amount' => 3,
    ]);

    $response = $this->actingAs($user)->get(route('filament.dashboard.pages.dashboard'));

    $response
        ->assertOk()
        ->assertSee('Personen aangemeld')
        ->assertSee('Aankomende BBQ')
        ->assertSee('5')
        ->assertSee('Personen per vereniging/partner')
        ->assertSee('Vereniging Alpha')
        ->assertSee('Partner Beta')
        ->assertDontSee('Oude Vereniging');
});

test('dashboard falls back to the last event when no future event is planned', function () {
    Carbon::setTestNow('2026-06-15 12:00:00');

    $user = User::factory()->create();

    $olderEvent = Event::create([
        'name' => 'Oudste BBQ',
        'starts_at' => now()->subMonths(2),
    ]);

    $lastEvent = Event::create([
        'name' => 'Laatste BBQ',
        'starts_at' => now()->subWeek(),
    ]);

    Enrollment::create([
        'event_id' => $olderEvent->id,
        'full_name' => 'Oudste Aanmelding',
        'email' => 'oldest@example.com',
        'type' => 'student',
        'student_association' => 'Oudste Vereniging',
        'guest_amount' => 2,
    ]);

    Enrollment::create([
        'event_id' => $lastEvent->id,
        'full_name' => 'Laatste Aanmelding',
        'email' => 'last@example.com',
        'type' => 'partner-bedrijf',
        'partner_organization_type' => 'vereniging',
        'partner_organization_name' => 'Laatste Vereniging',
        'company_name' => 'Laatste BV',
        'guest_amount' => 3,
    ]);

    $response = $this->actingAs($user)->get(route('filament.dashboard.pages.dashboard'));

    $response
        ->assertOk()
        ->assertSee('Laatste event')
        ->assertSee('Laatste BBQ')
        ->assertSee('Laatste Vereniging')
        ->assertDontSee('Oudste Vereniging');
});
