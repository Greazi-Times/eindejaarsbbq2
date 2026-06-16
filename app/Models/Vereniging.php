<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Vereniging extends Model
{
    protected $table = 'verenigingen';

    protected $fillable = [
        'name',
        'logo',
        'website',
        'description',
    ];

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(
            Event::class,
            'event_vereniging',
            'vereniging_id',
            'event_id',
        )
            ->withPivot([
                'free_guest_limit',
                'over_limit_payment_amount',
                'student_payment_amount',
                'students_always_pay',
                'docent_payment_amount',
                'docents_always_pay',
            ])
            ->withTimestamps();
    }
}
