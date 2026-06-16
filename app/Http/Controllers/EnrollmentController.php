<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Event;
use App\Models\Partner;
use App\Models\Vereniging;
use App\Services\MolliePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnrollmentController extends Controller
{
    public function store(Request $request, MolliePaymentService $mollie): RedirectResponse|Response
    {
        $event = Event::query()
            ->with(['partners', 'verenigingen'])
            ->whereNotNull('starts_at')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->first();

        if (! $event) {
            throw ValidationException::withMessages([
                'event' => 'Er is momenteel geen barbecue gepland.',
            ]);
        }

        $validated = $request->validate(
            [
                'full_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],

                'type' => ['required', 'string', 'in:student,docent,partner-bedrijf'],

                'student_association' => ['nullable', 'string', 'max:255'],
                'custom_student_association' => ['nullable', 'string', 'max:255'],

                'education' => ['nullable', 'string', 'max:255'],
                'custom_education' => ['nullable', 'string', 'max:255'],

                'partner_organization_type' => ['required_if:type,partner-bedrijf', 'required_if:type,docent', 'nullable', 'string', 'in:partner,vereniging'],
                'partner_organization_name' => ['required_if:type,partner-bedrijf', 'required_if:type,docent', 'nullable', 'string', 'max:255'],
                'company_name' => ['required_if:type,partner-bedrijf', 'nullable', 'string', 'max:255'],

                'guest_amount' => ['required', 'integer', 'min:1', 'max:3'],

                'dietary_preferences' => ['nullable', 'array'],
            ],
            [
                'full_name.required' => 'Volledige naam is verplicht.',
                'email.required' => 'E-mailadres is verplicht.',
                'email.email' => 'Vul een geldig e-mailadres in.',
                'type.required' => 'Selecteer een type.',
                'type.in' => 'Selecteer een geldig type.',
                'partner_organization_type.required_if' => 'Selecteer of je aanmelding bij een partner of vereniging hoort.',
                'partner_organization_type.in' => 'Selecteer een geldig organisatietype.',
                'partner_organization_name.required_if' => 'Selecteer een partner of vereniging.',
                'company_name.required_if' => 'Naam van het bedrijf is verplicht.',
                'guest_amount.required' => 'Aantal personen is verplicht.',
                'guest_amount.integer' => 'Aantal personen moet een getal zijn.',
                'guest_amount.min' => 'Aantal personen moet minimaal 1 zijn.',
                'guest_amount.max' => 'Aantal personen mag maximaal 3 zijn.',
            ],
        );

        if (
            $this->shouldValidatePartnerOrganization($validated) &&
            ! $this->eventHasPartnerOrganization($event, $validated)
        ) {
            throw ValidationException::withMessages([
                'partner_organization_name' => 'Selecteer een geldige partner of vereniging van dit event.',
            ]);
        }

        if ($this->eventHasEnrollmentForEmail($event, $validated['email'])) {
            $message = 'Dit e-mailadres is al aangemeld voor dit event.';

            return back()
                ->withErrors(['email' => $message])
                ->with('banner', [
                    'type' => 'warning',
                    'title' => 'E-mail al aangemeld',
                    'message' => $message,
                ]);
        }

        $paymentAmount = $this->paymentAmountForEnrollment($event, $validated);
        $requiresPayment = $paymentAmount !== null;

        $enrollment = Enrollment::create([
            ...$validated,
            'event_id' => $event->id,
            'requires_payment' => $requiresPayment,
            'payment_status' => $requiresPayment ? 'payment_pending' : null,
            'payment_amount' => $paymentAmount,
            'payment_currency' => $requiresPayment ? $mollie->currency() : null,
        ]);

        if ($requiresPayment) {
            try {
                $enrollment->load('event');

                $payment = $mollie->createForEnrollment($enrollment);

                $enrollment->update([
                    'payment_status' => $payment['status'],
                    'payment_amount' => $payment['amount'],
                    'payment_currency' => $payment['currency'],
                    'mollie_payment_id' => $payment['id'],
                    'mollie_payment_link_url' => $payment['url'],
                ]);
            } catch (\Throwable $exception) {
                $enrollment->delete();

                report($exception);

                throw ValidationException::withMessages([
                    'payment' => $this->paymentFailureMessage($exception),
                ]);
            }

            return Inertia::location($payment['url']);
        }

        return redirect()
            ->route('home')
            ->with('banner', [
                'type' => 'success',
                'title' => 'Aanmelding ontvangen',
                'message' => 'Je aanmelding is ontvangen. Je hoeft geen betaling te doen.',
            ]);
    }

    public function paymentReturn(Enrollment $enrollment, MolliePaymentService $mollie): RedirectResponse
    {
        if ($enrollment->requires_payment) {
            try {
                $this->syncPaymentStatus($enrollment, $mollie);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        if ($enrollment->payment_status === 'paid') {
            return redirect()
                ->route('home')
                ->with('payment', [
                    'type' => 'success',
                    'title' => 'Betaling gelukt',
                    'message' => 'Je aanmelding en betaling zijn ontvangen.',
                ]);
        }

        return redirect()
            ->route('home')
            ->with('payment', [
                'type' => 'warning',
                'title' => 'Betaling nog niet afgerond',
                'message' => 'Je aanmelding is ontvangen, maar je betaling is nog niet afgerond.',
                'action_url' => $enrollment->mollie_payment_link_url,
                'action_label' => 'Betaling afronden',
            ]);
    }

    private function syncPaymentStatus(Enrollment $enrollment, MolliePaymentService $mollie): void
    {
        $payment = $mollie->refreshStatus($enrollment);

        if (! $payment) {
            return;
        }

        $status = $payment['status'] ?? $enrollment->payment_status;

        $enrollment->update([
            'payment_status' => $status,
            'mollie_payment_id' => $payment['id'] ?? $enrollment->mollie_payment_id,
            'paid_at' => $status === 'paid' ? ($enrollment->paid_at ?? now()) : $enrollment->paid_at,
        ]);
    }

    private function paymentFailureMessage(\Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'Mollie API key')) {
            return 'De betaling kon niet worden gestart omdat Mollie nog niet is ingesteld.';
        }

        if (str_contains($message, 'Payment amount')) {
            return 'De betaling kon niet worden gestart omdat er geen bedrag voor deze aanmelding is ingesteld.';
        }

        if (str_contains($message, 'payment could not be created')) {
            return 'Mollie kon geen betaling aanmaken. Controleer de Mollie instellingen en probeer het opnieuw.';
        }

        if (str_contains($message, 'checkout URL')) {
            return 'Mollie heeft geen geldige betaallink teruggegeven. Probeer het later opnieuw.';
        }

        return 'De betaling kon niet worden gestart. Probeer het later opnieuw.';
    }

    private function paymentAmountForEnrollment(Event $event, array $validated): ?string
    {
        $amounts = array_filter([
            $this->organizationRolePaymentAmount($event, $validated),
            $this->organizationOverLimitPaymentAmount($event, $validated),
        ], fn (?string $amount): bool => $amount !== null);

        if ($amounts === []) {
            return null;
        }

        return number_format(array_sum(array_map('floatval', $amounts)), 2, '.', '');
    }

    private function organizationRolePaymentAmount(Event $event, array $validated): ?string
    {
        $role = $validated['type'] ?? null;

        if (! in_array($role, ['student', 'docent'], true)) {
            return null;
        }

        [$organizationType, $organizationName] = $this->paymentOrganizationForEnrollment($validated);

        if (! $organizationType || ! $organizationName) {
            return null;
        }

        $organization = $this->eventOrganization($event, $organizationType, $organizationName);

        if (! $organization) {
            return null;
        }

        $pivotColumn = $role === 'student'
            ? 'student_payment_amount'
            : 'docent_payment_amount';

        $mustPayColumn = $role === 'student'
            ? 'students_always_pay'
            : 'docents_always_pay';

        if (! ($organization->pivot?->{$mustPayColumn} ?? false)) {
            return null;
        }

        $amount = $organization->pivot?->{$pivotColumn};

        if ($amount === null || (float) $amount <= 0) {
            throw ValidationException::withMessages([
                'payment' => $role === 'student'
                    ? 'Voor deze partner of vereniging is geen studentenprijs ingesteld.'
                    : 'Voor deze partner of vereniging is geen docentenprijs ingesteld.',
            ]);
        }

        return number_format((float) $amount, 2, '.', '');
    }

    private function organizationOverLimitPaymentAmount(Event $event, array $validated): ?string
    {
        if (($validated['type'] ?? null) !== 'student') {
            return null;
        }

        [$organizationType, $organizationName] = $this->paymentOrganizationForEnrollment($validated);

        if (! $organizationType || ! $organizationName) {
            return null;
        }

        $organization = $this->eventOrganization($event, $organizationType, $organizationName);

        if (! $organization) {
            return null;
        }

        $freeGuestLimit = $organization->pivot?->free_guest_limit;

        if ($freeGuestLimit === null) {
            return null;
        }

        $guestAmount = (int) ($validated['guest_amount'] ?? 1);
        $currentGuestAmount = $this->currentOrganizationGuestAmount($event, $organizationType, $organizationName);
        $overLimitGuests = min(
            $guestAmount,
            max(0, ($currentGuestAmount + $guestAmount) - (int) $freeGuestLimit),
        );

        if ($overLimitGuests <= 0) {
            return null;
        }

        $overLimitPaymentAmount = $organization->pivot?->over_limit_payment_amount;

        if ($overLimitPaymentAmount === null || (float) $overLimitPaymentAmount <= 0) {
            throw ValidationException::withMessages([
                'payment' => 'Voor deze partner of vereniging is geen prijs per extra persoon ingesteld.',
            ]);
        }

        return number_format($overLimitGuests * (float) $overLimitPaymentAmount, 2, '.', '');
    }

    private function paymentOrganizationForEnrollment(array $validated): array
    {
        if (($validated['type'] ?? null) === 'student') {
            $organizationType = $validated['partner_organization_type'] ?? null;
            $organizationName = $validated['partner_organization_name'] ?? null;

            if ($organizationType && $organizationName) {
                return [$organizationType, $organizationName];
            }

            $verenigingName = $validated['student_association'] ?? null;

            if (! $verenigingName || $verenigingName === 'anders') {
                return [null, null];
            }

            return ['vereniging', $verenigingName];
        }

        if (($validated['type'] ?? null) === 'docent') {
            $organizationType = $validated['partner_organization_type'] ?? null;
            $organizationName = $validated['partner_organization_name'] ?? null;

            if (! $organizationType || ! $organizationName) {
                return [null, null];
            }

            return [$organizationType, $organizationName];
        }

        return [null, null];
    }

    private function eventOrganization(Event $event, string $organizationType, string $organizationName): Partner|Vereniging|null
    {
        if ($organizationType === 'partner') {
            return $event->partners->firstWhere('name', $organizationName);
        }

        if ($organizationType === 'vereniging') {
            return $event->verenigingen->firstWhere('name', $organizationName);
        }

        return null;
    }

    private function currentOrganizationGuestAmount(Event $event, string $organizationType, string $organizationName): int
    {
        return (int) Enrollment::query()
            ->where('event_id', $event->id)
            ->where(function ($query) use ($organizationType, $organizationName): void {
                if ($organizationType === 'partner') {
                    $query
                        ->where('partner_organization_type', 'partner')
                        ->where('partner_organization_name', $organizationName)
                        ->whereIn('type', ['student', 'docent', 'partner-bedrijf']);

                    return;
                }

                $query
                    ->where(function ($query) use ($organizationName): void {
                        $query
                            ->where('type', 'student')
                            ->where('student_association', $organizationName);
                    })
                    ->orWhere(function ($query) use ($organizationName): void {
                        $query
                            ->whereIn('type', ['student', 'docent'])
                            ->where('partner_organization_type', 'vereniging')
                            ->where('partner_organization_name', $organizationName);
                    })
                    ->orWhere(function ($query) use ($organizationName): void {
                        $query
                            ->where('type', 'partner-bedrijf')
                            ->where('partner_organization_type', 'vereniging')
                            ->where('partner_organization_name', $organizationName);
                    });
            })
            ->sum('guest_amount');
    }

    private function shouldValidatePartnerOrganization(array $validated): bool
    {
        return in_array($validated['type'] ?? null, ['docent', 'partner-bedrijf'], true)
            || filled($validated['partner_organization_type'] ?? null)
            || filled($validated['partner_organization_name'] ?? null);
    }

    private function eventHasPartnerOrganization(Event $event, array $validated): bool
    {
        $type = $validated['partner_organization_type'] ?? null;
        $name = $validated['partner_organization_name'] ?? null;

        if (! $type || ! $name) {
            return false;
        }

        if ($type === 'partner') {
            return $event->partners->contains('name', $name);
        }

        if ($type === 'vereniging') {
            return $event->verenigingen->contains('name', $name);
        }

        return false;
    }

    private function eventHasEnrollmentForEmail(Event $event, string $email): bool
    {
        return Enrollment::query()
            ->where('event_id', $event->id)
            ->whereRaw('lower(email) = ?', [strtolower($email)])
            ->exists();
    }
}
