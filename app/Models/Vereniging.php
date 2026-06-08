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
        'students_must_pay',
    ];

    protected $casts = [
        'students_must_pay' => 'boolean',
    ];

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(
            Event::class,
            'event_vereniging',
            'vereniging_id',
            'event_id',
        )->withTimestamps();
    }
}
