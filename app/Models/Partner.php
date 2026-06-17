<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Partner extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'website',
        'description',
        'show_on_registration_form',
    ];

    protected $casts = [
        'show_on_registration_form' => 'boolean',
    ];

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->withPivot([
                'free_guest_limit',
                'over_limit_payment_amount',
                'student_payment_amount',
                'students_always_pay',
                'docent_payment_amount',
                'docents_always_pay',
                'members_must_pay',
                'show_for_students_docents',
                'show_for_partner_companies',
            ]);
    }
}
