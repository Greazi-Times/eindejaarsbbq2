<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
        'location',
        'description',
        'default_payment_amount',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'default_payment_amount' => 'decimal:2',
    ];

    public function partners(): BelongsToMany
    {
        return $this->belongsToMany(Partner::class)
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

    public function verenigingen(): BelongsToMany
    {
        return $this->belongsToMany(
            Vereniging::class,
            'event_vereniging',
            'event_id',
            'vereniging_id',
        )
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
            ])
            ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function eventPartners(): HasMany
    {
        return $this->hasMany(EventPartner::class);
    }

    public function eventVerenigingen(): HasMany
    {
        return $this->hasMany(EventVereniging::class);
    }
}
