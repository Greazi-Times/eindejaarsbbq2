<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EventPartner extends Pivot
{
    protected $table = 'event_partner';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'partner_id',
        'free_guest_limit',
        'over_limit_payment_amount',
        'student_payment_amount',
        'students_always_pay',
        'docent_payment_amount',
        'docents_always_pay',
        'members_must_pay',
    ];

    protected $casts = [
        'free_guest_limit' => 'integer',
        'over_limit_payment_amount' => 'decimal:2',
        'student_payment_amount' => 'decimal:2',
        'students_always_pay' => 'boolean',
        'docent_payment_amount' => 'decimal:2',
        'docents_always_pay' => 'boolean',
        'members_must_pay' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
