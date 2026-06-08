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
        'students_must_pay',
    ];

    protected $casts = [
        'students_must_pay' => 'boolean',
    ];

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->withPivot('type')
            ->withTimestamps();
    }
}
