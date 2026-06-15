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
    ];

    protected $casts = [
        'free_guest_limit' => 'integer',
        'over_limit_payment_amount' => 'decimal:2',
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
