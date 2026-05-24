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
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function partners(): BelongsToMany
    {
        return $this->belongsToMany(Partner::class)
            ->withPivot('type')
            ->withTimestamps();
    }

    public function verenigingen(): BelongsToMany
    {
        return $this->belongsToMany(
            Vereniging::class,
            'event_vereniging',
            'event_id',
            'vereniging_id',
        )->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
