<?php

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\Enrollments\Pages\ListEnrollments;
use App\Models\Enrollment;
use App\Models\Event;
use Filament\Actions\Testing\TestAction;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('enrollments table defaults to the dashboard event and can switch events', function () {
    Carbon::setTestNow('2026-06-15 12:00:00');

    $user = panelUser([
        'ViewAny:Enrollment',
        'View:Enrollment',
        EnrollmentResource::VIEW_PERSONAL_DATA_PERMISSION,
    ]);

    $pastEvent = Event::create([
        'name' => 'Vorige BBQ',
        'starts_at' => now()->subMonth(),
    ]);

    $upcomingEvent = Event::create([
        'name' => 'Aankomende BBQ',
        'starts_at' => now()->addMonth(),
    ]);

    $pastEnrollment = Enrollment::create([
        'event_id' => $pastEvent->id,
        'full_name' => 'Past Person',
        'email' => 'past@example.com',
        'type' => 'student',
        'guest_amount' => 1,
    ]);

    $upcomingEnrollment = Enrollment::create([
        'event_id' => $upcomingEvent->id,
        'full_name' => 'Upcoming Person',
        'email' => 'upcoming@example.com',
        'type' => 'student',
        'guest_amount' => 2,
    ]);

    $this->actingAs($user);

    Livewire::test(ListEnrollments::class)
        ->assertTableFilterExists('event_id')
        ->assertCanSeeTableRecords([$upcomingEnrollment])
        ->assertCanNotSeeTableRecords([$pastEnrollment])
        ->filterTable('event_id', $pastEvent->id)
        ->assertCanSeeTableRecords([$pastEnrollment])
        ->assertCanNotSeeTableRecords([$upcomingEnrollment]);
});

test('enrollments table masks contact fields without the personal data permission', function () {
    $user = panelUser([
        'ViewAny:Enrollment',
        'View:Enrollment',
    ]);

    $event = Event::create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addMonth(),
    ]);

    $enrollment = Enrollment::create([
        'event_id' => $event->id,
        'full_name' => 'Jane Doe',
        'email' => 'jane.doe@example.com',
        'type' => 'student',
        'guest_amount' => 1,
    ]);

    $this->actingAs($user);

    Livewire::test(ListEnrollments::class)
        ->assertSee('J*** D**')
        ->assertSee('j*******@example.com')
        ->assertDontSee('Jane Doe')
        ->assertDontSee('jane.doe@example.com')
        ->assertTableActionHidden('viewPersonalData', $enrollment);
});

test('enrollments table keeps contact fields masked and reveals them through an action with permission', function () {
    $user = panelUser([
        'ViewAny:Enrollment',
        'View:Enrollment',
        EnrollmentResource::VIEW_PERSONAL_DATA_PERMISSION,
    ]);

    $event = Event::create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addMonth(),
    ]);

    $enrollment = Enrollment::create([
        'event_id' => $event->id,
        'full_name' => 'Jane Doe',
        'email' => 'jane.doe@example.com',
        'type' => 'student',
        'guest_amount' => 1,
    ]);

    $this->actingAs($user);

    Livewire::test(ListEnrollments::class)
        ->assertSee('J*** D**')
        ->assertSee('j*******@example.com')
        ->assertDontSee('Jane Doe')
        ->assertDontSee('jane.doe@example.com')
        ->assertTableActionVisible('viewPersonalData', $enrollment)
        ->assertTableActionHasLabel('viewPersonalData', 'View personal data', $enrollment)
        ->mountTableAction('viewPersonalData', $enrollment)
        ->assertActionMounted(TestAction::make('viewPersonalData')->table($enrollment));
});

test('enrollments table displays the selected association or organization', function () {
    $user = panelUser([
        'ViewAny:Enrollment',
        'View:Enrollment',
    ]);

    $event = Event::create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addMonth(),
    ]);

    $studentEnrollment = Enrollment::create([
        'event_id' => $event->id,
        'full_name' => 'Student Person',
        'email' => 'student@example.com',
        'type' => 'student',
        'student_association' => 'Vereniging Alpha',
        'guest_amount' => 1,
    ]);

    $partnerEnrollment = Enrollment::create([
        'event_id' => $event->id,
        'full_name' => 'Partner Person',
        'email' => 'partner@example.com',
        'type' => 'partner-bedrijf',
        'partner_organization_type' => 'partner',
        'partner_organization_name' => 'Partner Beta',
        'guest_amount' => 2,
    ]);

    $verenigingEnrollment = Enrollment::create([
        'event_id' => $event->id,
        'full_name' => 'Vereniging Person',
        'email' => 'vereniging@example.com',
        'type' => 'docent',
        'partner_organization_type' => 'vereniging',
        'partner_organization_name' => 'Vereniging Gamma',
        'guest_amount' => 1,
    ]);

    $this->actingAs($user);

    Livewire::test(ListEnrollments::class)
        ->assertTableColumnFormattedStateSet('organization', 'Vereniging Alpha', $studentEnrollment)
        ->assertTableColumnFormattedStateSet('organization', 'Partner Beta', $partnerEnrollment)
        ->assertTableColumnFormattedStateSet('organization', 'Vereniging Gamma', $verenigingEnrollment)
        ->filterTable('organization', 'Partner Beta')
        ->assertCanSeeTableRecords([$partnerEnrollment])
        ->assertCanNotSeeTableRecords([$studentEnrollment, $verenigingEnrollment]);
});

test('payment status column uses the requested status icons', function () {
    $user = panelUser([
        'ViewAny:Enrollment',
        'View:Enrollment',
        EnrollmentResource::VIEW_PERSONAL_DATA_PERMISSION,
    ]);

    $event = Event::create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addMonth(),
    ]);

    $notNeeded = Enrollment::create([
        'event_id' => $event->id,
        'full_name' => 'Free Person',
        'email' => 'free@example.com',
        'type' => 'student',
        'guest_amount' => 1,
        'requires_payment' => false,
    ]);

    $paid = Enrollment::create([
        'event_id' => $event->id,
        'full_name' => 'Paid Person',
        'email' => 'paid@example.com',
        'type' => 'student',
        'guest_amount' => 1,
        'requires_payment' => true,
        'payment_status' => 'paid',
    ]);

    $failed = Enrollment::create([
        'event_id' => $event->id,
        'full_name' => 'Failed Person',
        'email' => 'failed@example.com',
        'type' => 'student',
        'guest_amount' => 1,
        'requires_payment' => true,
        'payment_status' => 'failed',
    ]);

    $waiting = Enrollment::create([
        'event_id' => $event->id,
        'full_name' => 'Waiting Person',
        'email' => 'waiting@example.com',
        'type' => 'student',
        'guest_amount' => 1,
        'requires_payment' => true,
        'payment_status' => 'open',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ListEnrollments::class);

    $column = $component->instance()->getTable()->getColumn('payment_status');

    expect($column)->toBeInstanceOf(IconColumn::class);

    expect(paymentIconFor($column, $notNeeded))->toBe('heroicon-o-minus');
    expect(paymentIconFor($column, $paid))->toBe('heroicon-o-check');
    expect(paymentIconFor($column, $failed))->toBe('heroicon-o-x-mark');
    expect(paymentIconFor($column, $waiting))->toBe('heroicon-o-exclamation-triangle');
});

function paymentIconFor(IconColumn $column, Enrollment $enrollment): string
{
    $column->record($enrollment);
    $column->clearCachedState();

    return $column->getIcon($column->getState());
}
