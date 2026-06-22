<?php

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\Enrollments\Pages\EditEnrollment;
use App\Filament\Resources\Enrollments\Pages\ListEnrollments;
use App\Filament\Resources\Enrollments\Tables\EnrollmentsTable;
use App\Models\Enrollment;
use App\Models\Event;
use Filament\Actions\Testing\TestAction;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

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

test('enrollments table shows the latest enrollments first', function () {
    $user = panelUser([
        'ViewAny:Enrollment',
        'View:Enrollment',
    ]);

    $event = Event::create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addMonth(),
    ]);

    $oldest = Enrollment::create([
        'event_id' => $event->id,
        'full_name' => 'Oldest Person',
        'email' => 'oldest@example.com',
        'type' => 'student',
        'guest_amount' => 1,
    ]);

    $newest = Enrollment::create([
        'event_id' => $event->id,
        'full_name' => 'Newest Person',
        'email' => 'newest@example.com',
        'type' => 'student',
        'guest_amount' => 1,
    ]);

    Enrollment::query()
        ->whereKey($oldest->getKey())
        ->update(['created_at' => now()->subMinute()]);

    Enrollment::query()
        ->whereKey($newest->getKey())
        ->update(['created_at' => now()]);

    $this->actingAs($user);

    Livewire::test(ListEnrollments::class)
        ->assertCanSeeTableRecords([$newest, $oldest], inOrder: true);
});

test('enrollments table can filter by filled in diet wishes', function () {
    $user = panelUser([
        'ViewAny:Enrollment',
        'View:Enrollment',
    ]);

    $event = Event::create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addMonth(),
    ]);

    $withDietWishes = Enrollment::create([
        'event_id' => $event->id,
        'full_name' => 'Diet Person',
        'email' => 'diet@example.com',
        'type' => 'student',
        'guest_amount' => 1,
        'dietary_preferences' => [
            'person-1' => ['vegetarian'],
        ],
    ]);

    $withEmptyDietWishes = Enrollment::create([
        'event_id' => $event->id,
        'full_name' => 'No Diet Person',
        'email' => 'no-diet@example.com',
        'type' => 'student',
        'guest_amount' => 1,
        'dietary_preferences' => [],
    ]);

    $withoutDietWishes = Enrollment::create([
        'event_id' => $event->id,
        'full_name' => 'Null Diet Person',
        'email' => 'null-diet@example.com',
        'type' => 'student',
        'guest_amount' => 1,
        'dietary_preferences' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(ListEnrollments::class)
        ->assertTableFilterExists('dietary_preferences')
        ->assertTableColumnStateSet('dietary_preferences', ['Person 1: Vegetarian'], $withDietWishes)
        ->filterTable('dietary_preferences', true)
        ->assertCanSeeTableRecords([$withDietWishes])
        ->assertCanNotSeeTableRecords([$withEmptyDietWishes, $withoutDietWishes]);

    Livewire::test(ListEnrollments::class)
        ->filterTable('dietary_preferences', false)
        ->assertCanSeeTableRecords([$withEmptyDietWishes, $withoutDietWishes])
        ->assertCanNotSeeTableRecords([$withDietWishes]);
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
        ->assertTableActionHidden('togglePersonalDataVisibility')
        ->assertTableActionHidden('viewPersonalData', $enrollment);
});

test('enrollments table keeps contact fields masked and can reveal them globally or per row with permission', function () {
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
        ->assertTableActionVisible('togglePersonalDataVisibility')
        ->assertTableActionHasLabel('togglePersonalDataVisibility', 'Show personal details')
        ->callTableAction('togglePersonalDataVisibility')
        ->assertSee('Jane Doe')
        ->assertSee('jane.doe@example.com')
        ->assertTableActionHasLabel('togglePersonalDataVisibility', 'Hide personal details')
        ->callTableAction('togglePersonalDataVisibility')
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

test('only super admins or payment managers can edit enrollment payment details', function () {
    $event = Event::create([
        'name' => 'Eindejaars BBQ',
        'starts_at' => now()->addMonth(),
    ]);

    $enrollment = Enrollment::create([
        'event_id' => $event->id,
        'full_name' => 'Payment Person',
        'email' => 'payment@example.com',
        'type' => 'student',
        'guest_amount' => 1,
        'requires_payment' => false,
    ]);

    $editor = panelUser([
        'ViewAny:Enrollment',
        'View:Enrollment',
        'Update:Enrollment',
    ]);

    $this->actingAs($editor);

    Livewire::test(EditEnrollment::class, ['record' => $enrollment->getRouteKey()])
        ->assertFormFieldDoesNotExist('requires_payment')
        ->assertFormFieldDoesNotExist('payment_status')
        ->assertFormFieldDoesNotExist('payment_amount');

    $superAdmin = panelUser([
        'ViewAny:Enrollment',
        'View:Enrollment',
        'Update:Enrollment',
    ]);

    $superAdminRole = Role::firstOrCreate([
        'name' => config('filament-shield.super_admin.name', 'super_admin'),
        'guard_name' => config('auth.defaults.guard', 'web'),
    ]);

    $superAdmin->assignRole($superAdminRole);

    $this->actingAs($superAdmin);

    Livewire::test(EditEnrollment::class, ['record' => $enrollment->getRouteKey()])
        ->assertFormFieldExists('requires_payment')
        ->assertFormFieldExists('payment_status')
        ->assertFormFieldExists('payment_amount')
        ->fillForm([
            'requires_payment' => true,
            'payment_status' => 'paid',
            'payment_amount' => 12.50,
            'payment_currency' => 'EUR',
            'mollie_payment_id' => 'tr_test',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $enrollment->refresh();

    expect($enrollment->requires_payment)->toBeTrue()
        ->and($enrollment->payment_status)->toBe('paid')
        ->and((float) $enrollment->payment_amount)->toBe(12.50)
        ->and($enrollment->payment_currency)->toBe('EUR')
        ->and($enrollment->mollie_payment_id)->toBe('tr_test');
});

test('enrollments table can filter by payment requirement and payment status', function () {
    $user = panelUser([
        'ViewAny:Enrollment',
        'View:Enrollment',
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

    Livewire::test(ListEnrollments::class)
        ->filterTable('requires_payment', true)
        ->assertCanSeeTableRecords([$paid, $failed, $waiting])
        ->assertCanNotSeeTableRecords([$notNeeded]);

    Livewire::test(ListEnrollments::class)
        ->filterTable('payment_status_group', 'paid')
        ->assertCanSeeTableRecords([$paid])
        ->assertCanNotSeeTableRecords([$notNeeded, $failed, $waiting]);

    Livewire::test(ListEnrollments::class)
        ->filterTable('payment_status_group', 'failed')
        ->assertCanSeeTableRecords([$failed])
        ->assertCanNotSeeTableRecords([$notNeeded, $paid, $waiting]);

    Livewire::test(ListEnrollments::class)
        ->filterTable('payment_status_group', 'waiting')
        ->assertCanSeeTableRecords([$waiting])
        ->assertCanNotSeeTableRecords([$notNeeded, $paid, $failed]);
});

test('enrollment csv export escapes spreadsheet formulas', function () {
    expect(EnrollmentsTable::escapeCsvCell('=IMPORTXML("https://example.test")'))
        ->toBe('\'=IMPORTXML("https://example.test")')
        ->and(EnrollmentsTable::escapeCsvCell(' +SUM(1,2)'))
        ->toBe("' +SUM(1,2)")
        ->and(EnrollmentsTable::escapeCsvCell('Normal value'))
        ->toBe('Normal value')
        ->and(EnrollmentsTable::escapeCsvCell(3))
        ->toBe(3);
});

function paymentIconFor(IconColumn $column, Enrollment $enrollment): string
{
    $column->record($enrollment);
    $column->clearCachedState();

    return $column->getIcon($column->getState());
}
