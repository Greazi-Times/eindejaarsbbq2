<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EventVereniging extends Pivot
{
    protected $table = 'event_vereniging';

    public $incrementing = true;

    protected $fillable = [
        'event_id',
        'vereniging_id',
        'free_guest_limit',
        'over_limit_payment_amount',
        'student_payment_amount',
        'students_always_pay',
        'docent_payment_amount',
        'docents_always_pay',
    ];

    protected $casts = [
        'free_guest_limit' => 'integer',
        'over_limit_payment_amount' => 'decimal:2',
        'student_payment_amount' => 'decimal:2',
        'students_always_pay' => 'boolean',
        'docent_payment_amount' => 'decimal:2',
        'docents_always_pay' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function vereniging(): BelongsTo
    {
        return $this->belongsTo(Vereniging::class);
    }
}
