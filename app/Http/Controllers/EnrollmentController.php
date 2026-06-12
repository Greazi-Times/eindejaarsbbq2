<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Event;
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

                'partner_organization_type' => ['required_if:type,partner-bedrijf', 'nullable', 'string', 'in:partner,vereniging'],
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
                'company_name.required_if' => 'Organisatienaam is verplicht voor partners en verenigingen.',
                'guest_amount.required' => 'Aantal personen is verplicht.',
                'guest_amount.integer' => 'Aantal personen moet een getal zijn.',
                'guest_amount.min' => 'Aantal personen moet minimaal 1 zijn.',
                'guest_amount.max' => 'Aantal personen mag maximaal 3 zijn.',
            ],
        );

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

        $requiresPayment = $this->studentRequiresPayment($event, $validated);

        $enrollment = Enrollment::create([
            ...$validated,
            'event_id' => $event->id,
            'requires_payment' => $requiresPayment,
            'payment_status' => $requiresPayment ? 'payment_pending' : null,
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

        if (str_contains($message, 'Student payment amount')) {
            return 'De betaling kon niet worden gestart omdat er geen studentenprijs voor dit event is ingesteld.';
        }

        if (str_contains($message, 'payment could not be created')) {
            return 'Mollie kon geen betaling aanmaken. Controleer de Mollie instellingen en probeer het opnieuw.';
        }

        if (str_contains($message, 'checkout URL')) {
            return 'Mollie heeft geen geldige betaallink teruggegeven. Probeer het later opnieuw.';
        }

        return 'De betaling kon niet worden gestart. Probeer het later opnieuw.';
    }

    private function studentRequiresPayment(Event $event, array $validated): bool
    {
        if (($validated['type'] ?? null) !== 'student') {
            return false;
        }

        $verenigingName = $validated['student_association'] ?? null;

        if (! $verenigingName || $verenigingName === 'anders') {
            return false;
        }

        return $event->verenigingen
            ->firstWhere('name', $verenigingName)
            ?->students_must_pay ?? false;
    }

    private function eventHasEnrollmentForEmail(Event $event, string $email): bool
    {
        return Enrollment::query()
            ->where('event_id', $event->id)
            ->whereRaw('lower(email) = ?', [strtolower($email)])
            ->exists();
    }
}
