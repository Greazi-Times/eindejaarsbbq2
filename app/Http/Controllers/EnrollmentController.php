<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Event;
use App\Models\Partner;
use App\Models\Vereniging;
use App\Services\MolliePaymentService;
use App\Support\EducationOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnrollmentController extends Controller
{
    public function store(Request $request, MolliePaymentService $mollie): RedirectResponse|Response
    {
        $request->merge($this->normalizedEnrollmentInput($request));

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
                'email' => ['required', 'email:rfc', 'max:255'],

                'type' => ['required', 'string', 'in:student,docent,partner-bedrijf'],

                'student_association' => ['nullable', 'string', 'max:255'],
                'custom_student_association' => ['nullable', 'string', 'max:255'],

                'education' => [
                    'required_unless:type,partner-bedrijf',
                    'nullable',
                    'string',
                    Rule::in(array_keys(EducationOptions::formOptions())),
                ],
                'custom_education' => ['required_if:education,'.EducationOptions::OTHER, 'nullable', 'string', 'max:255'],

                'partner_organization_type' => ['nullable', 'string', 'in:partner,vereniging'],
                'partner_organization_name' => ['nullable', 'string', 'max:255'],
                'is_organization_member' => ['nullable', 'boolean'],
                'company_name' => ['required_if:type,partner-bedrijf', 'nullable', 'string', 'max:255'],

                'guest_amount' => ['required', 'integer', 'min:1', 'max:3'],

                'dietary_preferences' => ['nullable', 'array', 'max:3'],
                'dietary_preferences.*' => ['nullable', 'array', 'max:3'],
                'dietary_preferences.*.*' => ['string', 'in:vegetarian,vegan,halal'],
            ],
            [
                'full_name.required' => 'Volledige naam is verplicht.',
                'email.required' => 'E-mailadres is verplicht.',
                'email.email' => 'Vul een geldig e-mailadres in.',
                'type.required' => 'Selecteer een type.',
                'type.in' => 'Selecteer een geldig type.',
                'education.required_unless' => 'Selecteer een opleiding.',
                'custom_education.required_if' => 'Vul je opleiding in.',
                'partner_organization_type.in' => 'Selecteer een geldig organisatietype.',
                'is_organization_member.boolean' => 'Selecteer een geldige ledenstatus.',
                'company_name.required_if' => 'Vul de bedrijfsnaam in.',
                'guest_amount.required' => 'Aantal personen is verplicht.',
                'guest_amount.integer' => 'Aantal personen moet een getal zijn.',
                'guest_amount.min' => 'Aantal personen moet minimaal 1 zijn.',
                'guest_amount.max' => 'Aantal personen mag maximaal 3 zijn.',
            ],
        );

        [$organizationType, $organizationName] = $this->paymentOrganizationForEnrollment($event, $validated);

        if ($this->shouldAskOrganizationMembership($event, $validated, $organizationType) && ! $request->has('is_organization_member')) {
            throw ValidationException::withMessages([
                'is_organization_member' => 'Selecteer of je lid bent van deze vereniging.',
            ]);
        }

        if (
            ($validated['type'] ?? null) === 'student'
            && $organizationType === 'vereniging'
            && $organizationName
        ) {
            $validated['student_association'] = $organizationName;
        }

        if (
            ($validated['type'] ?? null) === 'student'
            && filled($validated['partner_organization_type'] ?? null)
            && filled($validated['partner_organization_name'] ?? null)
            && ! $this->isEducationVerenigingOrganization($event, $validated)
        ) {
            $validated['is_organization_member'] = null;
        }

        if ($organizationType && $organizationName) {
            $validated['partner_organization_type'] = $organizationType;
            $validated['partner_organization_name'] = $organizationName;
        }

        if (($validated['type'] ?? null) === 'docent') {
            $validated['student_association'] = null;
            $validated['custom_student_association'] = null;
            $validated['is_organization_member'] = null;
        }

        if (($validated['type'] ?? null) === 'partner-bedrijf') {
            $validated['education'] = null;
            $validated['custom_education'] = null;
            $validated['student_association'] = null;
            $validated['custom_student_association'] = null;
            $validated['is_organization_member'] = null;
        }

        if (($validated['type'] ?? null) !== 'partner-bedrijf') {
            $validated['guest_amount'] = 1;
        }

        if (
            $this->shouldValidatePartnerOrganization($validated) &&
            ! $this->eventHasPartnerOrganization($event, $validated)
        ) {
            throw ValidationException::withMessages([
                'partner_organization_name' => 'Selecteer een geldige partner of vereniging van dit event.',
            ]);
        }

        if ($this->eventHasEnrollmentForEmail($event, $validated['email'])) {
            return $this->enrollmentReceivedRedirect();
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

        return $this->enrollmentReceivedRedirect();
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
                'action_url' => $this->safeMollieUrl($enrollment->mollie_payment_link_url),
                'action_label' => 'Betaling afronden',
            ]);
    }

    private function normalizedEnrollmentInput(Request $request): array
    {
        $normalized = [];

        foreach ([
            'full_name',
            'email',
            'type',
            'student_association',
            'custom_student_association',
            'education',
            'custom_education',
            'partner_organization_type',
            'partner_organization_name',
            'company_name',
        ] as $field) {
            if (! $request->has($field)) {
                continue;
            }

            $value = $request->input($field);

            if (! is_string($value)) {
                continue;
            }

            $value = Str::squish($value);

            if ($field === 'email') {
                $value = Str::lower($value);
            }

            $normalized[$field] = $value === '' ? null : $value;
        }

        return $normalized;
    }

    private function enrollmentReceivedRedirect(): RedirectResponse
    {
        return redirect()
            ->route('home')
            ->with('banner', [
                'type' => 'success',
                'title' => 'Aanmelding verwerkt',
                'message' => 'Als dit e-mailadres nog niet eerder voor dit event is gebruikt, staat je aanmelding op de gastenlijst.',
            ]);
    }

    private function safeMollieUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));

        if ($scheme !== 'https') {
            return null;
        }

        if ($host === 'mollie.com' || Str::endsWith($host, '.mollie.com')) {
            return $url;
        }

        return null;
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
        $rolePaymentAmount = $this->organizationRolePaymentAmount($event, $validated);

        if ($rolePaymentAmount !== null) {
            return $rolePaymentAmount;
        }

        $overLimitPaymentAmount = $this->organizationOverLimitPaymentAmount($event, $validated);

        if ($overLimitPaymentAmount !== null) {
            return $overLimitPaymentAmount;
        }

        return $this->defaultPaymentAmountForEnrollment($event, $validated);
    }

    private function organizationRolePaymentAmount(Event $event, array $validated): ?string
    {
        $role = $validated['type'] ?? null;

        if (! in_array($role, ['student', 'docent'], true)) {
            return null;
        }

        $organization = $this->eventOrganizationForPayment($event, $validated);

        if (! $organization && $role === 'docent') {
            $organization = $this->eventVerenigingForEducation($event, $validated);
        }

        if (! $organization) {
            return null;
        }

        $pivotColumn = $role === 'student'
            ? 'student_payment_amount'
            : 'docent_payment_amount';

        $amount = $organization->pivot?->{$pivotColumn};

        $mustPay = ($validated['is_organization_member'] ?? false)
            ? ($organization->pivot?->members_must_pay ?? false)
            : ($amount !== null && (float) $amount > 0);

        if (! $mustPay) {
            return null;
        }

        if (
            ($validated['is_organization_member'] ?? false)
            && ($amount === null || (float) $amount <= 0)
        ) {
            $amount = $organization->pivot?->over_limit_payment_amount;
        }

        if ($amount === null || (float) $amount <= 0) {
            throw ValidationException::withMessages([
                'payment' => ($validated['is_organization_member'] ?? false)
                    ? 'Voor deze partner of vereniging is geen ledenprijs ingesteld.'
                    : ($role === 'student'
                        ? 'Voor deze partner of vereniging is geen studentenprijs ingesteld.'
                        : 'Voor deze partner of vereniging is geen docentenprijs ingesteld.'),
            ]);
        }

        return number_format((float) $amount, 2, '.', '');
    }

    private function organizationOverLimitPaymentAmount(Event $event, array $validated): ?string
    {
        if (! in_array($validated['type'] ?? null, ['student', 'docent', 'partner-bedrijf'], true)) {
            return null;
        }

        [$organizationType, $organizationName] = $this->paymentOrganizationForEnrollment($event, $validated);

        if (! $organizationType || ! $organizationName) {
            return null;
        }

        $organization = $this->eventOrganization($event, $organizationType, $organizationName);

        if (! $organization) {
            return null;
        }

        if (
            ($validated['is_organization_member'] ?? false)
            && ! ($organization->pivot?->members_must_pay ?? false)
        ) {
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
                'payment' => 'Voor deze partner of vereniging is geen prijs voor extra personen ingesteld.',
            ]);
        }

        return number_format((float) $overLimitPaymentAmount, 2, '.', '');
    }

    private function defaultPaymentAmountForEnrollment(Event $event, array $validated): ?string
    {
        if (! $this->shouldUseDefaultPaymentAmount($event, $validated)) {
            return null;
        }

        $amount = $event->default_payment_amount;

        if ($amount === null || (float) $amount <= 0) {
            throw ValidationException::withMessages([
                'payment' => 'Voor dit event is geen standaardprijs ingesteld.',
            ]);
        }

        return number_format((float) $amount, 2, '.', '');
    }

    private function shouldUseDefaultPaymentAmount(Event $event, array $validated): bool
    {
        if (! filled($validated['education'] ?? null)) {
            return false;
        }

        if (filled($validated['partner_organization_type'] ?? null) && filled($validated['partner_organization_name'] ?? null)) {
            return false;
        }

        if (($validated['education'] ?? null) === EducationOptions::OTHER) {
            return true;
        }

        return ! $this->eventVerenigingForEducation($event, $validated);
    }

    private function paymentOrganizationForEnrollment(Event $event, array $validated): array
    {
        if (($validated['type'] ?? null) === 'student') {
            $organizationType = $validated['partner_organization_type'] ?? null;
            $organizationName = $validated['partner_organization_name'] ?? null;

            if ($organizationType && $organizationName) {
                return [$organizationType, $organizationName];
            }

            $verenigingName = $this->eventVerenigingForEducation($event, $validated)?->name
                ?? $validated['student_association']
                ?? null;

            if (! $verenigingName || $verenigingName === 'anders') {
                return [null, null];
            }

            return ['vereniging', $verenigingName];
        }

        if (($validated['type'] ?? null) === 'docent') {
            $organizationType = $validated['partner_organization_type'] ?? null;
            $organizationName = $validated['partner_organization_name'] ?? null;

            if ($organizationType && $organizationName) {
                return [$organizationType, $organizationName];
            }
        }

        if (($validated['type'] ?? null) === 'partner-bedrijf') {
            $organizationType = $validated['partner_organization_type'] ?? null;
            $organizationName = $validated['partner_organization_name'] ?? null;

            if ($organizationType && $organizationName) {
                return [$organizationType, $organizationName];
            }
        }

        return [null, null];
    }

    private function shouldAskOrganizationMembership(Event $event, array $validated, ?string $organizationType): bool
    {
        if (($validated['type'] ?? null) !== 'student') {
            return false;
        }

        if ($this->isEducationVerenigingOrganization($event, $validated)) {
            return true;
        }

        if (filled($validated['partner_organization_type'] ?? null) && filled($validated['partner_organization_name'] ?? null)) {
            return false;
        }

        return $this->eventVerenigingForEducation($event, $validated) !== null;
    }

    private function isEducationVerenigingOrganization(Event $event, array $validated): bool
    {
        return ($validated['partner_organization_type'] ?? null) === 'vereniging'
            && filled($validated['partner_organization_name'] ?? null)
            && $this->eventVerenigingForEducation($event, $validated)?->name === ($validated['partner_organization_name'] ?? null);
    }

    private function eventVerenigingForEducation(Event $event, array $validated): ?Vereniging
    {
        $education = $validated['education'] ?? null;

        if (! $education || $education === EducationOptions::OTHER) {
            return null;
        }

        return $event->verenigingen->firstWhere('education', $education);
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

    private function eventOrganizationForPayment(Event $event, array $validated): Partner|Vereniging|null
    {
        [$organizationType, $organizationName] = $this->paymentOrganizationForEnrollment($event, $validated);

        if (! $organizationType || ! $organizationName) {
            return null;
        }

        return $this->eventOrganization($event, $organizationType, $organizationName);
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
        return ($validated['type'] ?? null) === 'partner-bedrijf'
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
            $partner = $event->partners->firstWhere('name', $name);

            if (! $partner) {
                return false;
            }

            return $this->organizationIsVisibleForEnrollment($partner, $validated);
        }

        if ($type === 'vereniging') {
            $vereniging = $event->verenigingen->firstWhere('name', $name);

            if (! $vereniging) {
                return false;
            }

            if (
                ($validated['type'] ?? null) === 'student'
                && $this->eventVerenigingForEducation($event, $validated)?->name === $name
            ) {
                return true;
            }

            return $this->organizationIsVisibleForEnrollment($vereniging, $validated);
        }

        return false;
    }

    private function organizationIsVisibleForEnrollment(Partner|Vereniging $organization, array $validated): bool
    {
        if (($validated['type'] ?? null) === 'partner-bedrijf') {
            return (bool) ($organization->pivot?->show_for_partner_companies ?? true);
        }

        if (in_array($validated['type'] ?? null, ['student', 'docent'], true)) {
            return (bool) ($organization->pivot?->show_for_students_docents ?? false);
        }

        return false;
    }

    private function eventHasEnrollmentForEmail(Event $event, string $email): bool
    {
        return Enrollment::query()
            ->where('event_id', $event->id)
            ->whereRaw('lower(email) = ?', [Str::lower($email)])
            ->exists();
    }
}
