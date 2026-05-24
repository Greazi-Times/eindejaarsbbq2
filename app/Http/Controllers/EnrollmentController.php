<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $event = Event::query()
            ->whereNotNull('starts_at')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->first();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],

            'type' => ['required', 'string', 'in:student,docent,partner-bedrijf'],

            'student_association' => ['nullable', 'string', 'max:255'],
            'custom_student_association' => ['nullable', 'string', 'max:255'],

            'education' => ['nullable', 'string', 'max:255'],
            'custom_education' => ['nullable', 'string', 'max:255'],

            'company_name' => ['nullable', 'string', 'max:255'],

            'guest_amount' => ['required', 'integer', 'min:1', 'max:3'],

            'dietary_preferences' => ['nullable', 'array'],
        ]);

        Enrollment::create([
            ...$validated,
            'event_id' => $event->id,
        ]);

        return back()->with('success', 'Je aanmelding is ontvangen.');
    }
}
