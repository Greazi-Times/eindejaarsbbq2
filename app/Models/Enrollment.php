<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $fillable = [
        'event_id',
        'full_name',
        'email',
        'type',
        'student_association',
        'custom_student_association',
        'education',
        'custom_education',
        'company_name',
        'guest_amount',
        'dietary_preferences',
        'notes',
        'requires_payment',
        'payment_status',
        'payment_amount',
        'payment_currency',
        'mollie_payment_link_id',
        'mollie_payment_link_url',
        'mollie_payment_id',
        'paid_at',
    ];

    protected $casts = [
        'dietary_preferences' => 'array',
        'requires_payment' => 'boolean',
        'payment_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
