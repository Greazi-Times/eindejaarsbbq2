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
    ];

    protected $casts = [
        'dietary_preferences' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
