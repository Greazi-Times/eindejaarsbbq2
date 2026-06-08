<?php

use App\Models\Enrollment;
use App\Models\Event;
use App\Models\Vereniging;
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
        'students_must_pay' => false,
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

it('rejects duplicate email enrollments for the same event', function () {
    $event = Event::query()->create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'location' => 'Hogeschool',
    ]);

    $vereniging = Vereniging::query()->create([
        'name' => 'Free Association',
        'students_must_pay' => false,
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
