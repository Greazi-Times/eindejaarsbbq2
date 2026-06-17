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
        'education' => 'mechatronica',
    ]);

    $event->verenigingen()->attach($vereniging);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Student Tester',
            'email' => 'student@example.com',
            'type' => 'student',
            'student_association' => $vereniging->name,
            'education' => 'mechatronica',
            'is_organization_member' => false,
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
            'is_organization_member' => false,
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
        ->and($enrollment->education)->toBeNull()
        ->and($enrollment->custom_education)->toBeNull()
        ->and($enrollment->is_organization_member)->toBeNull()
        ->and($enrollment->company_name)->toBe('External Company')
        ->and($enrollment->guest_amount)->toBe(2)
        ->and($enrollment->requires_payment)->toBeFalse();
});

it('requires a company name for partner enrollments', function () {
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
            'email' => 'partner-no-company@example.com',
            'type' => 'partner-bedrijf',
            'partner_organization_type' => 'partner',
            'partner_organization_name' => $partner->name,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect('/aanmelden')
        ->assertSessionHasErrors([
            'company_name' => 'Vul de bedrijfsnaam in.',
        ]);

    expect(Enrollment::query()->count())->toBe(0);
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
        'education' => 'mechatronica',
    ]);

    $event->verenigingen()->attach($vereniging);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Vereniging Tester',
            'email' => 'vereniging@example.com',
            'type' => 'partner-bedrijf',
            'education' => 'mechatronica',
            'partner_organization_type' => 'vereniging',
            'partner_organization_name' => $vereniging->name,
            'is_organization_member' => false,
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
        'student_payment_amount' => 6.50,
        'show_for_students_docents' => true,
    ]);

    $this->instance(MolliePaymentService::class, fakeEnrollmentMollieService());

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Partner Student',
            'email' => 'partner-student@example.com',
            'type' => 'student',
            'student_association' => $partner->name,
            'education' => 'mechatronica',
            'partner_organization_type' => 'partner',
            'partner_organization_name' => $partner->name,
            'is_organization_member' => false,
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

it('charges docents from the education price without linking them to a vereniging', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Docent Association',
        'education' => 'mechatronica',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'docent_payment_amount' => 4.25,
    ]);

    $this->instance(MolliePaymentService::class, fakeEnrollmentMollieService());

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Education Docent',
            'email' => 'education-docent@example.com',
            'type' => 'docent',
            'education' => 'mechatronica',
            'is_organization_member' => true,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $enrollment = Enrollment::query()
        ->where('email', 'education-docent@example.com')
        ->firstOrFail();

    $response->assertRedirect("https://payments.test/enrollments/{$enrollment->id}");

    expect($enrollment->requires_payment)->toBeTrue()
        ->and((float) $enrollment->payment_amount)->toBe(4.25)
        ->and($enrollment->payment_currency)->toBe('EUR')
        ->and($enrollment->payment_status)->toBe('open')
        ->and($enrollment->student_association)->toBeNull()
        ->and($enrollment->partner_organization_type)->toBeNull()
        ->and($enrollment->partner_organization_name)->toBeNull()
        ->and($enrollment->is_organization_member)->toBeNull();
});

it('stores a selected visible organization for docents', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $partner = Partner::query()->create([
        'name' => 'Visible Partner',
        'show_on_registration_form' => true,
    ]);

    $event->partners()->attach($partner, [
        'show_for_students_docents' => true,
    ]);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Partner Docent',
            'email' => 'partner-docent@example.com',
            'type' => 'docent',
            'education' => 'mechatronica',
            'partner_organization_type' => 'partner',
            'partner_organization_name' => $partner->name,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('banner.type', 'success');

    $enrollment = Enrollment::query()
        ->where('email', 'partner-docent@example.com')
        ->firstOrFail();

    expect($enrollment->partner_organization_type)->toBe('partner')
        ->and($enrollment->partner_organization_name)->toBe($partner->name)
        ->and($enrollment->is_organization_member)->toBeNull()
        ->and($enrollment->requires_payment)->toBeFalse();
});

it('stores a selected loose vereniging for students without membership', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Loose Association',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'show_for_students_docents' => true,
    ]);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Loose Student',
            'email' => 'loose-student@example.com',
            'type' => 'student',
            'education' => 'mechatronica',
            'partner_organization_type' => 'vereniging',
            'partner_organization_name' => $vereniging->name,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('banner.type', 'success');

    $enrollment = Enrollment::query()
        ->where('email', 'loose-student@example.com')
        ->firstOrFail();

    expect($enrollment->student_association)->toBe($vereniging->name)
        ->and($enrollment->partner_organization_type)->toBe('vereniging')
        ->and($enrollment->partner_organization_name)->toBe($vereniging->name)
        ->and($enrollment->is_organization_member)->toBeNull();
});

it('charges normal role prices when an amount is configured', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Optional Price Association',
        'education' => 'mechatronica',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'student_payment_amount' => 9.75,
        'docent_payment_amount' => 8.25,
    ]);

    $this->instance(MolliePaymentService::class, fakeEnrollmentMollieService());

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Paying Student',
            'email' => 'paying-student@example.com',
            'type' => 'student',
            'student_association' => $vereniging->name,
            'education' => 'mechatronica',
            'partner_organization_type' => 'vereniging',
            'partner_organization_name' => $vereniging->name,
            'is_organization_member' => false,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $enrollment = Enrollment::query()
        ->where('email', 'paying-student@example.com')
        ->firstOrFail();

    $response->assertRedirect("https://payments.test/enrollments/{$enrollment->id}");

    expect($enrollment->requires_payment)->toBeTrue()
        ->and((float) $enrollment->payment_amount)->toBe(9.75)
        ->and($enrollment->payment_currency)->toBe('EUR')
        ->and($enrollment->payment_status)->toBe('open');
});

it('keeps organization members free when members do not have to pay', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Member Association',
        'education' => 'mechatronica',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'student_payment_amount' => 9.75,
        'members_must_pay' => false,
    ]);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Free Member',
            'email' => 'free-member@example.com',
            'type' => 'student',
            'student_association' => $vereniging->name,
            'education' => 'mechatronica',
            'partner_organization_type' => 'vereniging',
            'partner_organization_name' => $vereniging->name,
            'is_organization_member' => true,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('banner.type', 'success');

    $enrollment = Enrollment::query()
        ->where('email', 'free-member@example.com')
        ->firstOrFail();

    expect($enrollment->is_organization_member)->toBeTrue()
        ->and($enrollment->requires_payment)->toBeFalse()
        ->and($enrollment->payment_amount)->toBeNull();
});

it('charges organization members when members have to pay', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Paying Member Association',
        'education' => 'mechatronica',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'student_payment_amount' => 5.25,
        'members_must_pay' => true,
    ]);

    $this->instance(MolliePaymentService::class, fakeEnrollmentMollieService());

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Paying Member',
            'email' => 'paying-member@example.com',
            'type' => 'student',
            'student_association' => $vereniging->name,
            'education' => 'mechatronica',
            'partner_organization_type' => 'vereniging',
            'partner_organization_name' => $vereniging->name,
            'is_organization_member' => true,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $enrollment = Enrollment::query()
        ->where('email', 'paying-member@example.com')
        ->firstOrFail();

    $response->assertRedirect("https://payments.test/enrollments/{$enrollment->id}");

    expect($enrollment->is_organization_member)->toBeTrue()
        ->and($enrollment->requires_payment)->toBeTrue()
        ->and((float) $enrollment->payment_amount)->toBe(5.25)
        ->and($enrollment->payment_status)->toBe('open');
});

it('charges organization members with the vereniging amount when members have to pay', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Fallback Member Association',
        'education' => 'mechatronica',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'free_guest_limit' => 1,
        'over_limit_payment_amount' => 7.50,
        'members_must_pay' => true,
    ]);

    $this->instance(MolliePaymentService::class, fakeEnrollmentMollieService());

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Fallback Paying Member',
            'email' => 'fallback-paying-member@example.com',
            'type' => 'student',
            'education' => 'mechatronica',
            'is_organization_member' => true,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $enrollment = Enrollment::query()
        ->where('email', 'fallback-paying-member@example.com')
        ->firstOrFail();

    $response->assertRedirect("https://payments.test/enrollments/{$enrollment->id}");

    expect($enrollment->requires_payment)->toBeTrue()
        ->and((float) $enrollment->payment_amount)->toBe(7.50)
        ->and($enrollment->student_association)->toBe($vereniging->name)
        ->and($enrollment->payment_status)->toBe('open');
});

it('keeps normal role payments free when no amount is configured', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Missing Price Association',
        'education' => 'mechatronica',
    ]);

    $event->verenigingen()->attach($vereniging);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'No Role Price Student',
            'email' => 'no-role-price-student@example.com',
            'type' => 'student',
            'student_association' => $vereniging->name,
            'education' => 'mechatronica',
            'partner_organization_type' => 'vereniging',
            'partner_organization_name' => $vereniging->name,
            'is_organization_member' => false,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('banner.type', 'success');

    $enrollment = Enrollment::query()
        ->where('email', 'no-role-price-student@example.com')
        ->firstOrFail();

    expect($enrollment->requires_payment)->toBeFalse()
        ->and($enrollment->payment_amount)->toBeNull()
        ->and($enrollment->payment_currency)->toBeNull()
        ->and($enrollment->payment_status)->toBeNull();
});

it('charges partner enrollments with the vereniging extra persons total price after the free limit', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Limited Association',
        'education' => 'mechatronica',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'free_guest_limit' => 2,
        'over_limit_payment_amount' => 7.50,
    ]);

    Enrollment::query()->create([
        'event_id' => $event->id,
        'full_name' => 'Existing Partner',
        'email' => 'existing@example.com',
        'type' => 'partner-bedrijf',
        'partner_organization_type' => 'vereniging',
        'partner_organization_name' => $vereniging->name,
        'guest_amount' => 2,
        'requires_payment' => false,
    ]);

    $this->instance(MolliePaymentService::class, fakeEnrollmentMollieService());

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Over Limit Partner',
            'email' => 'over-limit@example.com',
            'type' => 'partner-bedrijf',
            'education' => 'mechatronica',
            'partner_organization_type' => 'vereniging',
            'partner_organization_name' => $vereniging->name,
            'is_organization_member' => false,
            'company_name' => 'Over Limit BV',
            'guest_amount' => 2,
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
        ->and($enrollment->guest_amount)->toBe(2)
        ->and($enrollment->mollie_payment_id)->toBe("tr_{$enrollment->id}");
});

it('stores student enrollments as one person even if a higher amount is posted', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Single Student Association',
        'education' => 'mechatronica',
    ]);

    $event->verenigingen()->attach($vereniging);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Single Student',
            'email' => 'single-student@example.com',
            'type' => 'student',
            'education' => 'mechatronica',
            'is_organization_member' => false,
            'guest_amount' => 3,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('banner.type', 'success');

    $enrollment = Enrollment::query()
        ->where('email', 'single-student@example.com')
        ->firstOrFail();

    expect($enrollment->guest_amount)->toBe(1)
        ->and($enrollment->requires_payment)->toBeFalse();
});

it('charges student enrollments with the vereniging total price after the free limit', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Motus',
        'education' => 'mechatronica',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'free_guest_limit' => 1,
        'over_limit_payment_amount' => 32.00,
    ]);

    Enrollment::query()->create([
        'event_id' => $event->id,
        'full_name' => 'Existing Student',
        'email' => 'existing-student@example.com',
        'type' => 'student',
        'education' => 'mechatronica',
        'student_association' => $vereniging->name,
        'partner_organization_type' => 'vereniging',
        'partner_organization_name' => $vereniging->name,
        'guest_amount' => 1,
        'requires_payment' => false,
    ]);

    $this->instance(MolliePaymentService::class, fakeEnrollmentMollieService());

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Over Limit Student',
            'email' => 'over-limit-student@example.com',
            'type' => 'student',
            'education' => 'mechatronica',
            'is_organization_member' => false,
            'guest_amount' => 3,
            'dietary_preferences' => [],
        ]);

    $enrollment = Enrollment::query()
        ->where('email', 'over-limit-student@example.com')
        ->firstOrFail();

    $response->assertRedirect("https://payments.test/enrollments/{$enrollment->id}");

    expect($enrollment->requires_payment)->toBeTrue()
        ->and((float) $enrollment->payment_amount)->toBe(32.00)
        ->and($enrollment->payment_currency)->toBe('EUR')
        ->and($enrollment->payment_status)->toBe('open')
        ->and($enrollment->student_association)->toBe($vereniging->name)
        ->and($enrollment->guest_amount)->toBe(1)
        ->and($enrollment->mollie_payment_id)->toBe("tr_{$enrollment->id}");
});

it('keeps organization members free after the free limit when members do not have to pay', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Free Members Association',
        'education' => 'mechatronica',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'free_guest_limit' => 1,
        'over_limit_payment_amount' => 32.00,
        'members_must_pay' => false,
    ]);

    Enrollment::query()->create([
        'event_id' => $event->id,
        'full_name' => 'Existing Student',
        'email' => 'existing-free-member-limit@example.com',
        'type' => 'student',
        'education' => 'mechatronica',
        'student_association' => $vereniging->name,
        'partner_organization_type' => 'vereniging',
        'partner_organization_name' => $vereniging->name,
        'guest_amount' => 1,
        'requires_payment' => false,
    ]);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Free Over Limit Member',
            'email' => 'free-over-limit-member@example.com',
            'type' => 'student',
            'education' => 'mechatronica',
            'is_organization_member' => true,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('banner.type', 'success');

    $enrollment = Enrollment::query()
        ->where('email', 'free-over-limit-member@example.com')
        ->firstOrFail();

    expect($enrollment->is_organization_member)->toBeTrue()
        ->and($enrollment->requires_payment)->toBeFalse()
        ->and($enrollment->payment_amount)->toBeNull()
        ->and($enrollment->payment_status)->toBeNull();
});

it('uses the role payment instead of adding the extra persons price', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Always Pay Association',
        'education' => 'mechatronica',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'free_guest_limit' => 0,
        'over_limit_payment_amount' => 10.00,
        'student_payment_amount' => 5.00,
    ]);

    $this->instance(MolliePaymentService::class, fakeEnrollmentMollieService());

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Always Pay Student',
            'email' => 'always-pay-student@example.com',
            'type' => 'student',
            'student_association' => $vereniging->name,
            'education' => 'mechatronica',
            'partner_organization_type' => 'vereniging',
            'partner_organization_name' => $vereniging->name,
            'is_organization_member' => false,
            'guest_amount' => 3,
            'dietary_preferences' => [],
        ]);

    $enrollment = Enrollment::query()
        ->where('email', 'always-pay-student@example.com')
        ->firstOrFail();

    $response->assertRedirect("https://payments.test/enrollments/{$enrollment->id}");

    expect($enrollment->requires_payment)->toBeTrue()
        ->and((float) $enrollment->payment_amount)->toBe(5.00)
        ->and($enrollment->payment_status)->toBe('open');
});

it('charges the event default price for another education', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
        'default_payment_amount' => 11.00,
    ]);

    $this->instance(MolliePaymentService::class, fakeEnrollmentMollieService());

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Other Education Student',
            'email' => 'other-education@example.com',
            'type' => 'student',
            'education' => 'anders',
            'custom_education' => 'Civiele Techniek',
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $enrollment = Enrollment::query()
        ->where('email', 'other-education@example.com')
        ->firstOrFail();

    $response->assertRedirect("https://payments.test/enrollments/{$enrollment->id}");

    expect($enrollment->requires_payment)->toBeTrue()
        ->and((float) $enrollment->payment_amount)->toBe(11.00)
        ->and($enrollment->education)->toBe('anders')
        ->and($enrollment->custom_education)->toBe('Civiele Techniek')
        ->and($enrollment->payment_status)->toBe('open');
});

it('charges partner enrollments when their partner cap is exceeded', function () {
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
        'is_organization_member' => false,
        'company_name' => 'Existing BV',
        'guest_amount' => 3,
        'requires_payment' => false,
    ]);

    $this->instance(MolliePaymentService::class, fakeEnrollmentMollieService());

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Over Limit Partner',
            'email' => 'over-limit-partner@example.com',
            'type' => 'partner-bedrijf',
            'education' => 'mechatronica',
            'partner_organization_type' => 'partner',
            'partner_organization_name' => $partner->name,
            'is_organization_member' => false,
            'company_name' => 'Over Limit BV',
            'guest_amount' => 3,
            'dietary_preferences' => [],
        ]);

    $enrollment = Enrollment::query()
        ->where('email', 'over-limit-partner@example.com')
        ->firstOrFail();

    $response->assertRedirect("https://payments.test/enrollments/{$enrollment->id}");

    expect($enrollment->requires_payment)->toBeTrue()
        ->and((float) $enrollment->payment_amount)->toBe(12.50)
        ->and($enrollment->payment_currency)->toBe('EUR')
        ->and($enrollment->payment_status)->toBe('open');
});

it('rejects over-limit partner enrollments when linked to a vereniging without an extra persons price', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Limited Association Partner',
        'education' => 'mechatronica',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'free_guest_limit' => 0,
    ]);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Over Limit Vereniging Partner',
            'email' => 'free-partner@example.com',
            'type' => 'partner-bedrijf',
            'education' => 'mechatronica',
            'partner_organization_type' => 'vereniging',
            'partner_organization_name' => $vereniging->name,
            'is_organization_member' => false,
            'company_name' => 'Free Partner BV',
            'guest_amount' => 3,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect('/aanmelden')
        ->assertSessionHasErrors([
            'payment' => 'Voor deze partner of vereniging is geen prijs voor extra personen ingesteld.',
        ]);

    expect(Enrollment::query()->count())->toBe(0);
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
        'education' => 'mechatronica',
    ]);

    $event->verenigingen()->attach($vereniging, [
        'free_guest_limit' => 0,
    ]);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'No Price Partner',
            'email' => 'no-price@example.com',
            'type' => 'partner-bedrijf',
            'education' => 'mechatronica',
            'partner_organization_type' => 'vereniging',
            'partner_organization_name' => $vereniging->name,
            'is_organization_member' => false,
            'company_name' => 'No Price BV',
            'guest_amount' => 2,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect('/aanmelden')
        ->assertSessionHasErrors([
            'payment' => 'Voor deze partner of vereniging is geen prijs voor extra personen ingesteld.',
        ]);

    expect(Enrollment::query()->count())->toBe(0);
});

it('handles duplicate email enrollments without confirming the address exists', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Free Association',
        'education' => 'mechatronica',
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
            'education' => 'mechatronica',
            'is_organization_member' => false,
            'guest_amount' => 1,
            'dietary_preferences' => [],
        ]);

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('banner.type', 'success')
        ->assertSessionHas('banner.title', 'Aanmelding verwerkt')
        ->assertSessionDoesntHaveErrors();

    expect(Enrollment::query()->count())->toBe(1);
});

it('normalizes enrollment emails and rejects unexpected dietary preferences', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Free Association',
        'education' => 'mechatronica',
    ]);

    $event->verenigingen()->attach($vereniging);

    $invalidResponse = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => 'Student Tester',
            'email' => 'student-invalid@example.com',
            'type' => 'student',
            'education' => 'mechatronica',
            'is_organization_member' => false,
            'guest_amount' => 1,
            'dietary_preferences' => [
                'person-1' => ['vegetarian', '=IMPORTXML("https://example.test")'],
            ],
        ]);

    $invalidResponse
        ->assertRedirect('/aanmelden')
        ->assertSessionHasErrors('dietary_preferences.person-1.1');

    expect(Enrollment::query()->count())->toBe(0);

    $response = $this
        ->from('/aanmelden')
        ->post(route('enrollments.store'), [
            'full_name' => '  Student   Tester  ',
            'email' => '  STUDENT-NORMALIZED@example.com ',
            'type' => 'student',
            'education' => 'mechatronica',
            'is_organization_member' => false,
            'guest_amount' => 1,
            'dietary_preferences' => [
                'person-1' => ['vegetarian', 'halal'],
            ],
        ]);

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('banner.type', 'success');

    $enrollment = Enrollment::query()->firstOrFail();

    expect($enrollment->full_name)->toBe('Student Tester')
        ->and($enrollment->email)->toBe('student-normalized@example.com')
        ->and($enrollment->dietary_preferences)->toBe([
            'person-1' => ['vegetarian', 'halal'],
        ]);
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
