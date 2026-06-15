<?php

namespace App\Services;

use App\Models\Enrollment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

class MolliePaymentService
{
    public function createForEnrollment(Enrollment $enrollment): array
    {
        $amount = $this->eventAmount($enrollment);

        $payload = [
            'amount' => [
                'currency' => $this->currency(),
                'value' => $amount,
            ],
            'description' => Str::limit($this->description($enrollment), 255, ''),
            'redirectUrl' => URL::signedRoute('enrollments.payment.return', ['enrollment' => $enrollment]),
            'metadata' => [
                'enrollment_id' => $enrollment->id,
                'event_id' => $enrollment->event_id,
            ],
        ];

        $response = $this->client()->post('payments', $payload);

        if ($response->failed()) {
            throw new RuntimeException('Mollie payment could not be created: '.$response->body());
        }

        $payment = $response->json();
        $paymentUrl = data_get($payment, '_links.checkout.href');

        if (! $paymentUrl) {
            throw new RuntimeException('Mollie did not return a checkout URL.');
        }

        return [
            'id' => $payment['id'] ?? null,
            'url' => $paymentUrl,
            'amount' => $amount,
            'currency' => $this->currency(),
            'status' => $payment['status'] ?? 'open',
        ];
    }

    public function refreshStatus(Enrollment $enrollment): ?array
    {
        if (! $enrollment->mollie_payment_id) {
            return null;
        }

        $response = $this->client()->get("payments/{$enrollment->mollie_payment_id}");

        if ($response->failed()) {
            throw new RuntimeException('Mollie payment status could not be retrieved: '.$response->body());
        }

        return $response->json();
    }

    public function eventAmount(Enrollment $enrollment): string
    {
        $amount = $enrollment->payment_amount ?? $enrollment->event?->student_payment_amount;

        if ($amount === null || (float) $amount <= 0) {
            throw new RuntimeException('Student payment amount is not configured for this event.');
        }

        return number_format((float) $amount, 2, '.', '');
    }

    public function currency(): string
    {
        return (string) config('services.mollie.student_payment_currency', 'EUR');
    }

    private function client(): PendingRequest
    {
        $apiKey = config('services.mollie.api_key');

        if (! $apiKey) {
            throw new RuntimeException('Mollie API key is not configured.');
        }

        return Http::baseUrl('https://api.mollie.com/v2/')
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson();
    }

    private function description(Enrollment $enrollment): string
    {
        return sprintf(
            '%s - %s',
            config('services.mollie.student_payment_description', 'Eindejaars BBQ aanmelding'),
            $enrollment->full_name,
        );
    }
}
