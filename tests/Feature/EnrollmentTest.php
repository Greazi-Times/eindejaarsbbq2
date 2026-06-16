<?php

use App\Models\Enrollment;
use App\Models\Event;
use App\Models\Partner;
use App\Models\Vereniging;
use App\Services\MolliePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores a student enrollment without payment for a free association', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Free Association',
    ]);

    $event->verenigingen()->attach($vereniging);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Student Tester',
            'email' => 'student@example.com',
            'type' => 'student',
            'student_association' => $vereniging->name,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('banner.type', 'success');

    expect(Enrollment::query()->count())->toBe(1);

    $enrollment = Enrollment::query()->firstOrFail();

    expect($enrollment->requires_payment)->toBeFalse()
        ->and($enrollment->payment_status)->toBeNull()
        ->and($enrollment->student_association)->toBe($vereniging->name);
});

it('stores a partner enrollment for a selected partner organization', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $partner = Partner::query()->create([
        'name' => 'Partner Company',
    ]);

    $event->partners()->attach($partner);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Partner Tester',
            'email' => 'partner@example.com',
            'type' => 'partner-bedrijf',
            'partner_organization_type' => 'partner',
            'partner_organization_name' => $partner->name,
            'company_name' => 'External Company',
            'guest_amount' => 2,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('banner.type', 'success');

    $enrollment = Enrollment::query()->firstOrFail();

    expect($enrollment->partner_organization_type)->toBe('partner')
        ->and($enrollment->partner_organization_name)->toBe($partner->name)
        ->and($enrollment->company_name)->toBe('External Company')
        ->and($enrollment->guest_amount)->toBe(2)
        ->and($enrollment->requires_payment)->toBeFalse();
});

it('stores a partner enrollment for a selected vereniging organization', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Study Association',
    ]);

    $event->verenigingen()->attach($vereniging);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Vereniging Tester',
            'email' => 'vereniging@example.com',
            'type' => 'partner-bedrijf',
            'partner_organization_type' => 'vereniging',
            'partner_organization_name' => $vereniging->name,
            'company_name' => 'Vereniging Sponsor BV',
            'guest_amount' => 2,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('banner.type', 'success');

    $enrollment = Enrollment::query()->firstOrFail();

    expect($enrollment->partner_organization_type)->toBe('vereniging')
        ->and($enrollment->partner_organization_name)->toBe($vereniging->name)
        ->and($enrollment->company_name)->toBe('Vereniging Sponsor BV')
        ->and($enrollment->guest_amount)->toBe(2)
        ->and($enrollment->requires_payment)->toBeFalse();
});

it('charges students with the selected partner price', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $partner = Partner::query()->create([
        'name' => 'Paying Partner',
    ]);

    $event->partners()->attach($partner, [
        'students_always_pay' => true,
        'student_payment_amount' => 6.50,
    ]);

    $this->instance(MolliePaymentService::class, fakeEnrollmentMollieService());

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Partner Student',
            'email' => 'partner-student@example.com',
            'type' => 'student',
            'student_association' => $partner->name,
            'partner_organization_type' => 'partner',
            'partner_organization_name' => $partner->name,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $enrollment = Enrollment::query()
        ->where('email', 'partner-student@example.com')
        ->firstOrFail();

    $response->assertRedirect("https://payments.test/enrollments/{$enrollment->id}");

    expect($enrollment->requires_payment)->toBeTrue()
        ->and((float) $enrollment->payment_amount)->toBe(6.50)
        ->and($enrollment->payment_currency)->toBe('EUR')
        ->and($enrollment->payment_status)->toBe('open')
        ->and($enrollment->partner_organization_type)->toBe('partner')
        ->and($enrollment->partner_organization_name)->toBe($partner->name);
});

it('charges docents with the selected vereniging price', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Docent Association',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'docents_always_pay' => true,
        'docent_payment_amount' => 4.25,
    ]);

    $this->instance(MolliePaymentService::class, fakeEnrollmentMollieService());

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Paying Docent',
            'email' => 'paying-docent@example.com',
            'type' => 'docent',
            'education' => 'mechatronica',
            'partner_organization_type' => 'vereniging',
            'partner_organization_name' => $vereniging->name,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $enrollment = Enrollment::query()
        ->where('email', 'paying-docent@example.com')
        ->firstOrFail();

    $response->assertRedirect("https://payments.test/enrollments/{$enrollment->id}");

    expect($enrollment->requires_payment)->toBeTrue()
        ->and((float) $enrollment->payment_amount)->toBe(4.25)
        ->and($enrollment->payment_currency)->toBe('EUR')
        ->and($enrollment->payment_status)->toBe('open')
        ->and($enrollment->partner_organization_type)->toBe('vereniging')
        ->and($enrollment->partner_organization_name)->toBe($vereniging->name);
});

it('keeps normal role prices inactive until the always pay field is enabled', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Optional Price Association',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'student_payment_amount' => 9.75,
        'docent_payment_amount' => 8.25,
    ]);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Free Student',
            'email' => 'free-student@example.com',
            'type' => 'student',
            'student_association' => $vereniging->name,
            'partner_organization_type' => 'vereniging',
            'partner_organization_name' => $vereniging->name,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('banner.type', 'success');

    $enrollment = Enrollment::query()
        ->where('email', 'free-student@example.com')
        ->firstOrFail();

    expect($enrollment->requires_payment)->toBeFalse()
        ->and($enrollment->payment_amount)->toBeNull()
        ->and($enrollment->payment_currency)->toBeNull()
        ->and($enrollment->payment_status)->toBeNull();
});

it('rejects normal role payments when always pay is enabled without a price', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Missing Price Association',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'students_always_pay' => true,
    ]);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Missing Price Student',
            'email' => 'missing-price-student@example.com',
            'type' => 'student',
            'student_association' => $vereniging->name,
            'partner_organization_type' => 'vereniging',
            'partner_organization_name' => $vereniging->name,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect('/aanmelden')
        ->assertSessionHasErrors([
            'payment' => 'Voor deze partner of vereniging is geen studentenprijs ingesteld.',
        ]);

    expect(Enrollment::query()->count())->toBe(0);
});

it('charges students with the vereniging extra person price after the free limit', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Limited Association',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'free_guest_limit' => 2,
        'over_limit_payment_amount' => 7.50,
    ]);

    Enrollment::query()->create([
        'event_id' => $event->id,
        'full_name' => 'Existing Student',
        'email' => 'existing@example.com',
        'type' => 'student',
        'student_association' => $vereniging->name,
        'guest_amount' => 2,
        'requires_payment' => false,
    ]);

    $this->instance(MolliePaymentService::class, fakeEnrollmentMollieService());

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Over Limit Student',
            'email' => 'over-limit@example.com',
            'type' => 'student',
            'student_association' => $vereniging->name,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $enrollment = Enrollment::query()
        ->where('email', 'over-limit@example.com')
        ->firstOrFail();

    $response->assertRedirect("https://payments.test/enrollments/{$enrollment->id}");

    expect($enrollment->requires_payment)->toBeTrue()
        ->and((float) $enrollment->payment_amount)->toBe(7.50)
        ->and($enrollment->payment_currency)->toBe('EUR')
        ->and($enrollment->payment_status)->toBe('open')
        ->and($enrollment->mollie_payment_id)->toBe("tr_{$enrollment->id}");
});

it('keeps partner enrollments free even when their partner cap is exceeded', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $partner = Partner::query()->create([
        'name' => 'Limited Partner',
    ]);

    $event->partners()->attach($partner, [
        'free_guest_limit' => 4,
        'over_limit_payment_amount' => 12.50,
    ]);

    Enrollment::query()->create([
        'event_id' => $event->id,
        'full_name' => 'Existing Partner',
        'email' => 'existing-partner@example.com',
        'type' => 'partner-bedrijf',
        'partner_organization_type' => 'partner',
        'partner_organization_name' => $partner->name,
        'company_name' => 'Existing BV',
        'guest_amount' => 3,
        'requires_payment' => false,
    ]);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Over Limit Partner',
            'email' => 'over-limit-partner@example.com',
            'type' => 'partner-bedrijf',
            'partner_organization_type' => 'partner',
            'partner_organization_name' => $partner->name,
            'company_name' => 'Over Limit BV',
            'guest_amount' => 3,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('banner.type', 'success');

    $enrollment = Enrollment::query()
        ->where('email', 'over-limit-partner@example.com')
        ->firstOrFail();

    expect($enrollment->requires_payment)->toBeFalse()
        ->and($enrollment->payment_amount)->toBeNull()
        ->and($enrollment->payment_currency)->toBeNull()
        ->and($enrollment->payment_status)->toBeNull();
});

it('keeps partner enrollments free even when linked to an over-limit vereniging', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Limited Association Partner',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'free_guest_limit' => 0,
    ]);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Free Partner',
            'email' => 'free-partner@example.com',
            'type' => 'partner-bedrijf',
            'partner_organization_type' => 'vereniging',
            'partner_organization_name' => $vereniging->name,
            'company_name' => 'Free Partner BV',
            'guest_amount' => 3,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('banner.type', 'success');

    $enrollment = Enrollment::query()
        ->where('email', 'free-partner@example.com')
        ->firstOrFail();

    expect($enrollment->requires_payment)->toBeFalse()
        ->and($enrollment->payment_amount)->toBeNull()
        ->and($enrollment->payment_currency)->toBeNull()
        ->and($enrollment->payment_status)->toBeNull();
});

it('rejects over-limit enrollments when the extra person price is missing', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'No Price Association',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'free_guest_limit' => 0,
    ]);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'No Price Student',
            'email' => 'no-price@example.com',
            'type' => 'student',
            'student_association' => $vereniging->name,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect('/aanmelden')
        ->assertSessionHasErrors([
            'payment' => 'Voor deze partner of vereniging is geen prijs per extra persoon ingesteld.',
        ]);

    expect(Enrollment::query()->count())->toBe(0);
});

it('rejects duplicate email enrollments for the same event', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Free Association',
    ]);

    $event->verenigingen()->attach($vereniging);

    Enrollment::query()->create([
        'event_id' => $event->id,
        'full_name' => 'Existing Student',
        'email' => 'student@example.com',
        'type' => 'student',
        'student_association' => $vereniging->name,
        'guest_amount' => 1,
        'requires_payment' => false,
    ]);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Duplicate Student',
            'email' => 'STUDENT@example.com',
            'type' => 'student',
            'student_association' => $vereniging->name,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect('/aanmelden')
        ->assertSessionHas('banner.type', 'warning')
        ->assertSessionHas('banner.title', 'E-mail al aangemeld')
        ->assertSessionHasErrors([
            'email' => 'Dit e-mailadres is al aangemeld voor dit event.',
        ]);

    expect(Enrollment::query()->count())->toBe(1);
});

function fakeEnrollmentMollieService(): MolliePaymentService
{
    return new class extends MolliePaymentService
    {
        public function createForEnrollment(Enrollment $enrollment): array
        {
            return [
                'id' => "tr_{$enrollment->id}",
                'url' => "https://payments.test/enrollments/{$enrollment->id}",
                'amount' => number_format((float) $enrollment->payment_amount, 2, '.', ''),
                'currency' => $this->currency(),
                'status' => 'open',
            ];
        }

        public function currency(): string
        {
            return 'EUR';
        }
    };
}
