<?php

use App\Http\Controllers\EnrollmentController;
use App\Models\Event;
use App\Support\EducationOptions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

Route::get('/', function () {
    $logoUrl = static function (?string $logo): ?string {
        if (! $logo) {
            return null;
        }

        $logo = trim($logo);

        if ($logo === '' || str_starts_with($logo, '//')) {
            return null;
        }

        if (str_starts_with($logo, '/')) {
            return $logo;
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $logo)) {
            return null;
        }

        return Storage::disk('public')->url($logo);
    };

    $websiteUrl = static function (?string $website): ?string {
        if (! $website) {
            return null;
        }

        $website = trim($website);

        if (! filter_var($website, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = parse_url($website, PHP_URL_SCHEME);
        $host = parse_url($website, PHP_URL_HOST);

        if ($scheme !== 'https' || ! $host) {
            return null;
        }

        return $website;
    };

    $activeEvent = Event::query()
        ->with([
            'partners' => fn ($query) => $query->orderBy('name'),
            'verenigingen' => fn ($query) => $query->orderBy('name'),
            'enrollments:id,event_id,type,student_association,partner_organization_type,partner_organization_name,guest_amount',
        ])
        ->whereNotNull('starts_at')
        ->where('starts_at', '>=', now())
        ->orderBy('starts_at')
        ->first();

    return Inertia::render('Home', [
        'activeEvent' => $activeEvent ? [
            'id' => $activeEvent->id,
            'name' => $activeEvent->name,
            'starts_at' => $activeEvent->starts_at?->format('Y-m-d H:i:s'),
            'ends_at' => $activeEvent->ends_at?->format('Y-m-d H:i:s'),
            'location' => $activeEvent->location,
            'description' => $activeEvent->description,
            'default_payment_amount' => $activeEvent->default_payment_amount,
            'partners' => $activeEvent->partners->map(fn ($partner) => [
                'id' => $partner->id,
                'name' => $partner->name,
                'logo' => $logoUrl($partner->logo),
                'website' => $websiteUrl($partner->website),
                'show_for_students_docents' => (bool) ($partner->pivot?->show_for_students_docents ?? false),
                'show_for_partner_companies' => (bool) ($partner->pivot?->show_for_partner_companies ?? true),
                'current_guest_amount' => (int) $activeEvent->enrollments
                    ->filter(fn ($enrollment): bool => (
                        in_array($enrollment->type, ['student', 'docent', 'partner-bedrijf'], true)
                        && $enrollment->partner_organization_type === 'partner'
                        && $enrollment->partner_organization_name === $partner->name
                    ))
                    ->sum('guest_amount'),
                'free_guest_limit' => $partner->pivot?->free_guest_limit,
                'over_limit_payment_amount' => $partner->pivot?->over_limit_payment_amount,
                'student_payment_amount' => $partner->pivot?->student_payment_amount,
                'docent_payment_amount' => $partner->pivot?->docent_payment_amount,
            ])->values(),

            'verenigingen' => $activeEvent->verenigingen->map(fn ($vereniging) => [
                'id' => $vereniging->id,
                'name' => $vereniging->name,
                'education' => $vereniging->education,
                'logo' => $logoUrl($vereniging->logo),
                'website' => $websiteUrl($vereniging->website),
                'show_for_students_docents' => (bool) ($vereniging->pivot?->show_for_students_docents ?? false),
                'show_for_partner_companies' => (bool) ($vereniging->pivot?->show_for_partner_companies ?? true),
                'current_guest_amount' => (int) $activeEvent->enrollments
                    ->filter(fn ($enrollment): bool => (
                        $enrollment->type === 'student'
                        && $enrollment->student_association === $vereniging->name
                    ) || (
                        in_array($enrollment->type, ['student', 'docent', 'partner-bedrijf'], true)
                        && $enrollment->partner_organization_type === 'vereniging'
                        && $enrollment->partner_organization_name === $vereniging->name
                    ))
                    ->sum('guest_amount'),
                'free_guest_limit' => $vereniging->pivot?->free_guest_limit,
                'over_limit_payment_amount' => $vereniging->pivot?->over_limit_payment_amount,
                'student_payment_amount' => $vereniging->pivot?->student_payment_amount,
                'docent_payment_amount' => $vereniging->pivot?->docent_payment_amount,
                'members_must_pay' => (bool) ($vereniging->pivot?->members_must_pay ?? false),
            ])->values(),
        ] : null,
        'educationOptions' => collect(EducationOptions::formOptions())
            ->map(fn (string $label, string $value) => [
                'label' => $label,
                'value' => $value,
            ])
            ->values(),
    ]);
})->name('home');

Route::get('/legal', function () {
    $contactEmail = 'info@eindejaarsbbq.nl';
    $privacyEmail = 'privacy@eindejaarsbbq.nl';

    return Inertia::render('Legal', [
        'updatedAt' => 'March-02-2026',
        'contactEmail' => $privacyEmail,
        'policies' => [
            [
                'slug' => 'terms',
                'eyebrow' => 'Terms',
                'title' => 'Terms of service',
                'summary' => 'These Terms govern your use of the Eindejaars BBQ website and event services.',
                'sections' => [
                    [
                        'title' => '1. General Information',
                        'body' => [
                            'Eindejaars BBQ is located at Lovensdijkstraat 61, Breda, 4818 AJ, The Netherlands.',
                            "For general questions, please contact us at {$contactEmail}.",
                        ],
                    ],
                    [
                        'title' => '2. Use of the Website',
                        'body' => [
                            'You may use this website for information, registration, and participation related to the Eindejaars BBQ event.',
                            'You agree not to use the website for unlawful purposes, attempt unauthorized access, upload unlawful content, violate intellectual property or privacy rights, or interfere with the operation of our services.',
                        ],
                    ],
                    [
                        'title' => '3. Account Registration',
                        'body' => [
                            'Certain features may require an account. You are responsible for keeping your login credentials confidential and for all activity under your account.',
                            'We may suspend or terminate accounts that violate these Terms or are used for abusive, fraudulent, or unauthorized purposes.',
                        ],
                    ],
                    [
                        'title' => '4. Event Participation',
                        'body' => [
                            'Companies, associations, and participants who register for Eindejaars BBQ must provide accurate and truthful information.',
                            'We may use submitted company descriptions, logos, association information, and media for promotional and organizational purposes related to the event.',
                        ],
                    ],
                    [
                        'title' => '5. Intellectual Property',
                        'body' => [
                            'All website content, including text, graphics, logos, images, and software, belongs to Eindejaars BBQ or its partners unless stated otherwise.',
                            'You may not reproduce, distribute, or modify website material without prior written permission.',
                        ],
                    ],
                    [
                        'title' => '6. Privacy and Data Protection',
                        'body' => [
                            'Your use of this website is also governed by our Privacy Policy, which explains how we collect, use, and protect personal data.',
                        ],
                    ],
                    [
                        'title' => '7. Liability Disclaimer',
                        'body' => [
                            'We try to keep the information on this website accurate and up to date, but we do not guarantee that all content is complete, current, or error-free.',
                            'We are not liable for direct or indirect damages resulting from use of, or inability to use, the website, except where liability cannot legally be excluded.',
                        ],
                    ],
                    [
                        'title' => '8. External Links',
                        'body' => [
                            'This website may contain links to external websites. We are not responsible for the content, availability, or practices of third-party websites.',
                        ],
                    ],
                    [
                        'title' => '9. Changes to These Terms',
                        'body' => [
                            'We may update these Terms from time to time. The latest version will be published on this page.',
                            'Continued use of the website after changes take effect means you accept the revised Terms.',
                        ],
                    ],
                    [
                        'title' => '10. Governing Law',
                        'body' => [
                            'These Terms are governed by the laws of the Netherlands. Disputes shall be resolved by the competent courts in Breda, The Netherlands.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'privacy',
                'eyebrow' => 'Privacy',
                'title' => 'Privacy policy',
                'summary' => 'This Privacy Policy explains how Eindejaars BBQ collects, uses, and protects personal data when you visit or interact with our website.',
                'sections' => [
                    [
                        'title' => '1. Who We Are',
                        'body' => [
                            'Eindejaars BBQ is located at Lovensdijkstraat 61, Breda, 4818 AJ, The Netherlands.',
                            "Email: {$privacyEmail}. Chamber of Commerce (KvK): N/A.",
                            'We currently do not have a formally appointed Data Protection Officer (DPO).',
                        ],
                    ],
                    [
                        'title' => '2. Data We Collect',
                        'body' => [
                            'We collect only the information needed to operate our website, services, and events.',
                            'This may include account registration data such as name, email address, and password; event registration data such as role, association, education, company name, guest count, and dietary preferences; company or partner information such as names, descriptions, logos, and related data; media uploaded by authorized editors or administrators; contact form submissions; payment status information when payment is required; and essential cookies for login sessions and website functionality.',
                        ],
                    ],
                    [
                        'title' => '3. Purpose of Processing',
                        'body' => [
                            'We process personal data to manage user accounts, authenticate access, facilitate participation in the Eindejaars BBQ event, handle event registration and participant communication, process required payments, send newsletters or promotional materials about upcoming events where applicable, and maintain operational and security-related website functions.',
                        ],
                    ],
                    [
                        'title' => '4. Legal Basis for Processing',
                        'body' => [
                            'We process personal data under one or more legal bases under the EU General Data Protection Regulation (GDPR) and the Dutch Algemene Verordening Gegevensbescherming (AVG).',
                            'These include your consent for registration, newsletter subscriptions, and media use; performance of a contract when you participate in our event; and legitimate interest to keep the website secure and functioning properly.',
                        ],
                    ],
                    [
                        'title' => '5. Storage, Access, and Retention',
                        'body' => [
                            'Personal data is stored in a dedicated server environment hosted by OVH Cloud and managed through Proxmox and Pterodactyl.',
                            'Only one administrator has access to the back-end environment and server. Passwords and sensitive credentials are encrypted using Laravel’s built-in encryption, and HTTPS is enforced via Certbot.',
                            'User data is retained indefinitely unless the account holder explicitly requests deletion. Each year, registered accounts are manually reviewed for relevance and accuracy. Financial records may be retained longer when legally required.',
                        ],
                    ],
                    [
                        'title' => '6. Cookies',
                        'body' => [
                            'We only use cookies that are strictly necessary for website operation, such as authentication and session cookies.',
                            'We do not use third-party tracking or analytics cookies, so no cookie consent banner is required for those purposes.',
                        ],
                    ],
                    [
                        'title' => '7. Data Sharing',
                        'body' => [
                            'We do not share personal data with external third parties for marketing or profiling purposes.',
                            'External processing may involve email services such as Google, Outlook, or Roundcube, depending on our operational setup. When payment is required, payment-related data may be processed by Mollie.',
                        ],
                    ],
                    [
                        'title' => '8. Your Rights',
                        'body' => [
                            'Under the GDPR and Dutch privacy law, you have the right to access your data, correct inaccurate data, request erasure, request data portability, and restrict or object to processing.',
                            "To exercise your rights, please contact us via the website contact form or email us at {$privacyEmail}.",
                        ],
                    ],
                    [
                        'title' => '9. Security',
                        'body' => [
                            'We use modern encryption and access control practices to safeguard personal data.',
                            'Administrative access is limited to one authorized individual and protected with two-factor authentication.',
                        ],
                    ],
                    [
                        'title' => '10. Media and Event Photography',
                        'body' => [
                            'During Eindejaars BBQ events, photos and videos may be taken and used on our website or in promotional materials.',
                            'Participants are informed that photos may be used for publication and promotional purposes when attending the event.',
                        ],
                    ],
                    [
                        'title' => '11. Changes to This Policy',
                        'body' => [
                            'We may update this Privacy Policy from time to time to reflect operational, legal, or regulatory changes.',
                            'Updates will be published on this page with a revised “Last updated” date.',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'cookies',
                'eyebrow' => 'Cookies',
                'title' => 'Cookie policy',
                'summary' => 'This Cookie Policy explains how Eindejaars BBQ uses cookies and similar technologies on our website.',
                'sections' => [
                    [
                        'title' => '1. What Are Cookies?',
                        'body' => [
                            'Cookies are small text files placed on your device when you visit a website.',
                            'They help the website recognize your browser and store information about preferences or past actions.',
                        ],
                    ],
                    [
                        'title' => '2. How We Use Cookies',
                        'body' => [
                            'Eindejaars BBQ uses cookies that are strictly necessary for website operation and secure access.',
                            'These cookies support core features such as authentication, session management, form security, and consistent website functionality.',
                            'We do not use third-party tracking or analytics cookies, nor do we use advertising or marketing cookies.',
                        ],
                    ],
                    [
                        'title' => '3. Types of Cookies We Use',
                        'body' => [
                            'Essential cookies are required for logging in, managing sessions, protecting forms, and ensuring the website works correctly.',
                            'Functional cookies may be used to remember user preferences and provide a consistent experience across visits.',
                        ],
                    ],
                    [
                        'title' => '4. Managing Cookies',
                        'body' => [
                            'Because our cookies are essential for website operation, disabling them may cause parts of the website to stop working properly.',
                            'You can manage or delete cookies through your browser settings. For instructions, please consult your browser’s help documentation.',
                        ],
                    ],
                    [
                        'title' => '5. Changes to This Cookie Policy',
                        'body' => [
                            'We may update this Cookie Policy to reflect changes in technology, legal requirements, or our operational practices.',
                            'The updated version will be published on this page with a new “Last updated” date.',
                        ],
                    ],
                    [
                        'title' => '6. Contact Us',
                        'body' => [
                            'If you have questions about this Cookie Policy, please contact us at Eindejaars BBQ, Lovensdijkstraat 61, Breda, 4818 AJ, The Netherlands.',
                            "Email: {$privacyEmail}.",
                        ],
                    ],
                ],
            ],
        ],
    ]);
})->name('legal');

Route::get('/terms', fn () => redirect('/legal#terms'))->name('terms');
Route::get('/privacy', fn () => redirect('/legal#privacy'))->name('privacy');
Route::get('/cookies', fn () => redirect('/legal#cookies'))->name('cookies');

Route::post('/aanmelden', [EnrollmentController::class, 'store'])
    ->middleware('throttle:enrollments')
    ->name('enrollments.store');

Route::get('/aanmelden/{enrollment}/betaling', [EnrollmentController::class, 'paymentReturn'])
    ->middleware(['signed', 'throttle:payment-returns'])
    ->name('enrollments.payment.return');
